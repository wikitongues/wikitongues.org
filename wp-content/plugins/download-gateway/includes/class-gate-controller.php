<?php
/**
 * GateController — handles gate form submissions.
 *
 * Registers POST /wp-json/gateway/v1/gate. The testable logic lives in
 * submit() (same pattern as DownloadController::resolve()). handle() is an
 * untested wrapper that reads the REST request and returns a WP_REST_Response.
 *
 * Gate flow:
 *   1. JS sends POST with {post_id, email, name, consent_download, nonce, _hp}
 *   2. Server validates nonce, honeypot, rate limit, and field values.
 *   3. Person is upserted into wp_gateway_people.
 *   4. A one-time download token is created and returned.
 *   5. JS redirects to GET /gateway/v1/download/{token}.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class GateController {

	/** Maximum gate submissions per IP per hour. */
	private const RATE_LIMIT = 10;

	/** Rate-limit window in seconds. */
	private const RATE_WINDOW = 3600;

	public function register_routes(): void {
		register_rest_route(
			GATEWAY_REST_NAMESPACE,
			'/gate',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * REST callback — not unit tested.
	 *
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$result = $this->submit(
			(int) ( $request->get_param( 'post_id' ) ?? 0 ),
			(string) ( $request->get_param( 'email' ) ?? '' ),
			(string) ( $request->get_param( 'name' ) ?? '' ),
			(bool) ( $request->get_param( 'consent_download' ) ?? false ),
			(string) ( $request->get_param( 'nonce' ) ?? '' ),
			(string) ( $request->get_param( '_hp' ) ?? '' ),
			$_COOKIE,
			$_SERVER
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * Process a gate submission. Returns an array with the download token on
	 * success, or a WP_Error on failure.
	 *
	 * Honeypot hits return a silent success (fake token = null) to avoid
	 * giving bots any signal about the rejection.
	 *
	 * @param int                  $post_id          Post ID of the resource.
	 * @param string               $email            Submitted email address.
	 * @param string               $name             Submitted name.
	 * @param bool                 $consent_download Whether the user consented.
	 * @param string               $nonce            WP nonce value.
	 * @param string               $honeypot         Hidden spam-trap field value.
	 * @param array<string,mixed>  $cookies          Cookie values (injected for testing).
	 * @param array<string,mixed>  $server           Server vars (injected for testing).
	 * @return array<string,mixed>|\WP_Error
	 */
	public function submit(
		int $post_id,
		string $email,
		string $name,
		bool $consent_download,
		string $nonce,
		string $honeypot,
		array $cookies = [],
		array $server = []
	): array|\WP_Error {

		// Honeypot — bots fill hidden fields; silently succeed.
		if ( '' !== $honeypot ) {
			return [ 'token' => null ];
		}

		// Nonce verification.
		if ( ! wp_verify_nonce( $nonce, 'gateway_gate' ) ) {
			return new \WP_Error( 'invalid_nonce', 'Request could not be verified.', [ 'status' => 403 ] );
		}

		// Rate limiting by IP hash.
		$ip_hash  = IpHasher::hash_from_server( $server );
		$rate_key = 'gw_rate_' . substr( $ip_hash, 0, 28 );
		$count    = (int) get_transient( $rate_key );
		if ( $count >= self::RATE_LIMIT ) {
			return new \WP_Error( 'rate_limited', 'Too many requests. Please try again later.', [ 'status' => 429 ] );
		}
		set_transient( $rate_key, $count + 1, self::RATE_WINDOW );

		// Field validation.
		if ( $post_id <= 0 ) {
			return new \WP_Error( 'invalid_post_id', 'Invalid resource.', [ 'status' => 400 ] );
		}
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', 'A valid email address is required.', [ 'status' => 400 ] );
		}
		$name = trim( $name );
		if ( '' === $name ) {
			return new \WP_Error( 'invalid_name', 'Your name is required.', [ 'status' => 400 ] );
		}

		// Upsert person.
		$person_id = PeopleRepository::upsert( $email, $name, $consent_download );
		if ( false === $person_id ) {
			return new \WP_Error( 'db_error', 'Could not save your information. Please try again.', [ 'status' => 500 ] );
		}

		// Create one-time download token tied to this person.
		$visitor_id = VisitorId::from_cookies( $cookies );
		$token      = TokenRepository::create( $post_id, TokenRepository::TTL_DEFAULT, $visitor_id, $person_id );

		return [ 'token' => $token ];
	}
}
