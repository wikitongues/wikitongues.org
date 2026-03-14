<?php
/**
 * DownloadController — REST endpoint for the download gateway.
 *
 * Route: GET /wp-json/gateway/v1/download/{id}
 *
 * {id} is either:
 *   - A 64-char hex token  (issued by a previous request or gate submission)
 *   - A numeric post ID    (for policy=none resources; token is issued on-the-fly)
 *
 * The public surface is deliberately split:
 *   handle()  — sends the HTTP 302 redirect; not unit-tested (side effect)
 *   resolve() — accepts injected $cookies and $server; returns the target URL
 *               string on success or WP_Error on failure; fully unit-testable
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class DownloadController {

	public function register_routes(): void {
		register_rest_route(
			GATEWAY_REST_NAMESPACE,
			'/download/(?P<id>[a-zA-Z0-9]+)',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'validate_callback' => fn( $v ) => is_string( $v ) && strlen( $v ) > 0,
					],
				],
			]
		);
	}

	/**
	 * REST callback — resolves the download and sends a 302 redirect.
	 *
	 * Not unit-tested: this method's only job is to call resolve() and
	 * redirect. All business logic lives in resolve().
	 *
	 * @param \WP_REST_Request $request
	 * @return never|\WP_Error
	 */
	public function handle( \WP_REST_Request $request ): \WP_Error {
		$id     = $request->get_param( 'id' );
		$result = $this->resolve( $id, $_COOKIE, $_SERVER );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$visitor_id = VisitorId::from_cookies( $_COOKIE ) ?? VisitorId::generate();
		VisitorId::set_cookie( $visitor_id );

		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );
		wp_redirect( $result, 302 );
		exit;
	}

	/**
	 * Resolve a download ID to a file URL.
	 *
	 * Accepts $cookies and $server as parameters so tests can inject values
	 * without touching superglobals.
	 *
	 * @param string $id      64-char hex token or numeric post ID string.
	 * @param array  $cookies Cookie array (typically $_COOKIE).
	 * @param array  $server  Server array (typically $_SERVER).
	 * @return string|\WP_Error File URL on success, WP_Error on failure.
	 */
	public function resolve( string $id, array $cookies = [], array $server = [] ): string|\WP_Error {
		if ( ctype_digit( $id ) ) {
			return $this->resolve_post_id( (int) $id, $cookies, $server );
		}

		if ( ctype_xdigit( $id ) && strlen( $id ) === 64 ) {
			return $this->resolve_token( $id, $cookies, $server );
		}

		return new \WP_Error( 'invalid_id', 'Invalid download ID.', [ 'status' => 400 ] );
	}

	// -------------------------------------------------------------------------
	// Private
	// -------------------------------------------------------------------------

	private function resolve_post_id( int $post_id, array $cookies, array $server ): string|\WP_Error {
		$resolver = FileResolverRegistry::for_post( $post_id );
		if ( null === $resolver ) {
			return new \WP_Error( 'not_found', 'No file resolver for this resource.', [ 'status' => 404 ] );
		}

		$policy = PolicyResolver::resolve( $post_id );
		if ( SettingsRepository::POLICY_HARD === $policy ) {
			return new \WP_Error( 'gate_required', 'Email address required to download this resource.', [ 'status' => 403 ] );
		}

		$visitor_id = VisitorId::from_cookies( $cookies ) ?? VisitorId::generate();
		$token_str  = TokenRepository::create( $post_id, TokenRepository::TTL_DEFAULT, $visitor_id );
		$ip_hash    = IpHasher::hash_from_server( $server );
		$post_type  = get_post_type( $post_id );

		DownloadEventRepository::log( [
			'post_id'      => $post_id,
			'post_type'    => $post_type,
			'event_type'   => DownloadEventRepository::EVENT_CLICK,
			'visitor_id'   => $visitor_id,
			'storage_type' => $resolver->storage_type(),
			'ip_hash'      => $ip_hash,
		] );

		EventBus::dispatch( 'download/click', [ 'post_id' => $post_id, 'visitor_id' => $visitor_id ] );

		return $this->get_file_url( $post_id, $resolver, $visitor_id, $token_str, $server );
	}

	private function resolve_token( string $token, array $cookies, array $server ): string|\WP_Error {
		$row = TokenRepository::find_by_token( $token );
		if ( null === $row ) {
			return new \WP_Error( 'token_invalid', 'Token not found.', [ 'status' => 410 ] );
		}

		if ( ! TokenRepository::is_valid( $row ) ) {
			return new \WP_Error( 'token_expired', 'Token has expired or already been used.', [ 'status' => 410 ] );
		}

		TokenRepository::mark_used( $token );

		$resolver = FileResolverRegistry::for_post( (int) $row->post_id );
		if ( null === $resolver ) {
			return new \WP_Error( 'not_found', 'No file resolver for this resource.', [ 'status' => 404 ] );
		}

		$visitor_id = $row->visitor_id ?? VisitorId::from_cookies( $cookies );

		return $this->get_file_url( (int) $row->post_id, $resolver, $visitor_id, $token, $server );
	}

	private function get_file_url(
		int $post_id,
		FileResolver $resolver,
		?string $visitor_id,
		string $token_str,
		array $server
	): string|\WP_Error {
		$url = $resolver->resolve( $post_id );
		if ( null === $url ) {
			Logger::error( "DownloadController: resolver returned null for post {$post_id}." );
			return new \WP_Error( 'file_not_found', 'File could not be resolved.', [ 'status' => 404 ] );
		}

		DownloadEventRepository::log( [
			'post_id'      => $post_id,
			'post_type'    => get_post_type( $post_id ),
			'event_type'   => DownloadEventRepository::EVENT_REDIRECT,
			'visitor_id'   => $visitor_id,
			'storage_type' => $resolver->storage_type(),
			'ip_hash'      => IpHasher::hash_from_server( $server ),
		] );

		EventBus::dispatch( 'download/redirect', [ 'post_id' => $post_id, 'url' => $url ] );

		return $url;
	}
}
