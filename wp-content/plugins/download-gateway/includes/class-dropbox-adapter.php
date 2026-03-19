<?php
/**
 * DropboxAdapter — resolves Dropbox shared URLs to 4-hour temporary download links.
 *
 * Uses the Dropbox API to:
 *   1. Resolve a shared URL to a file path (sharing/get_shared_link_metadata)
 *   2. Issue a temporary download link (files/get_temporary_link)
 *
 * Credentials are injected via constructor for testability, defaulting to
 * wp-config.php constants via SettingsRepository.
 *
 * Caching (WordPress transients):
 *   - Access token:  3.5 hours  (gateway_dbx_access_token)
 *   - File path:     7 days     (gateway_dbx_path_{md5(shared_url)})
 *   - Temp link:     3.5 hours  (gateway_dbx_link_{md5(file_path)})
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class DropboxAdapter {

	const TRANSIENT_ACCESS_TOKEN = 'gateway_dbx_access_token';

	private string $app_key;
	private string $app_secret;
	private string $refresh_token;

	/**
	 * Credentials injected for testability; fall back to wp-config.php constants
	 * via SettingsRepository when null.
	 */
	public function __construct(
		?string $app_key = null,
		?string $app_secret = null,
		?string $refresh_token = null
	) {
		$this->app_key       = $app_key ?? SettingsRepository::get_dropbox_app_key();
		$this->app_secret    = $app_secret ?? SettingsRepository::get_dropbox_app_secret();
		$this->refresh_token = $refresh_token ?? SettingsRepository::get_dropbox_refresh_token();
	}

	/**
	 * Returns a 4-hour temporary download URL for the given Dropbox shared URL,
	 * or null if credentials are missing/invalid or any API call fails.
	 */
	public function get_temporary_link( string $shared_url ): ?string {
		if ( '' === $this->app_key || '' === $this->app_secret || '' === $this->refresh_token ) {
			Logger::error( 'DropboxAdapter: credentials not configured.' );
			return null;
		}

		$access_token = $this->get_access_token();
		if ( null === $access_token ) {
			return null;
		}

		$file_path = $this->get_file_path( $shared_url, $access_token );
		if ( null === $file_path ) {
			return null;
		}

		$link_key    = 'gateway_dbx_link_' . md5( $file_path );
		$cached_link = get_transient( $link_key );
		if ( false !== $cached_link ) {
			return (string) $cached_link;
		}

		$result = $this->api_post(
			'https://api.dropboxapi.com/2/files/get_temporary_link',
			array( 'path' => $file_path ),
			$access_token
		);

		if ( null === $result || ! isset( $result['link'] ) ) {
			Logger::error( "DropboxAdapter: failed to get temporary link for path: {$file_path}." );
			return null;
		}

		$link = $result['link'];
		set_transient( $link_key, $link, (int) round( 3.5 * HOUR_IN_SECONDS ) );
		return $link;
	}

	/**
	 * Resolves a Dropbox shared URL to a file path via sharing/get_shared_link_metadata.
	 * Result is cached for 7 days (the path is stable for a given shared URL).
	 */
	private function get_file_path( string $shared_url, string $access_token ): ?string {
		$path_key    = 'gateway_dbx_path_' . md5( $shared_url );
		$cached_path = get_transient( $path_key );
		if ( false !== $cached_path ) {
			return (string) $cached_path;
		}

		$result = $this->api_post(
			'https://api.dropboxapi.com/2/sharing/get_shared_link_metadata',
			array( 'url' => $shared_url ),
			$access_token
		);

		if ( null === $result || ! isset( $result['path_lower'] ) ) {
			Logger::error( "DropboxAdapter: failed to resolve shared URL to path: {$shared_url}." );
			return null;
		}

		$path = $result['path_lower'];
		set_transient( $path_key, $path, 7 * DAY_IN_SECONDS );
		return $path;
	}

	/**
	 * Returns a valid access token from cache or by refreshing via OAuth2.
	 */
	private function get_access_token(): ?string {
		$cached = get_transient( self::TRANSIENT_ACCESS_TOKEN );
		if ( false !== $cached ) {
			return (string) $cached;
		}
		return $this->refresh_access_token();
	}

	/**
	 * Exchanges the refresh token for a new access token via the Dropbox OAuth2 endpoint.
	 * Caches the result for 3.5 hours.
	 */
	private function refresh_access_token(): ?string {
		$response = wp_remote_post(
			'https://api.dropboxapi.com/oauth2/token',
			array(
				'body' => array(
					'grant_type'    => 'refresh_token',
					'refresh_token' => $this->refresh_token,
					'client_id'     => $this->app_key,
					'client_secret' => $this->app_secret,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			Logger::error( 'DropboxAdapter: token refresh failed: ' . $response->get_error_message() );
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			Logger::error( "DropboxAdapter: token refresh returned HTTP {$code}." );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['access_token'] ) ) {
			Logger::error( 'DropboxAdapter: token refresh response missing access_token.' );
			return null;
		}

		$token = $body['access_token'];
		set_transient( self::TRANSIENT_ACCESS_TOKEN, $token, (int) round( 3.5 * HOUR_IN_SECONDS ) );
		return $token;
	}

	/**
	 * Makes a JSON POST request to a Dropbox API endpoint.
	 * Returns the decoded response body array on HTTP 200, or null on any failure.
	 *
	 * @param string   $endpoint    Full Dropbox API URL.
	 * @param array<string,mixed> $body Request body (will be JSON-encoded).
	 * @param string   $access_token Bearer token.
	 * @return array<string,mixed>|null
	 */
	private function api_post( string $endpoint, array $body, string $access_token ): ?array {
		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => (string) json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			Logger::error( 'DropboxAdapter: API call to ' . $endpoint . ' failed: ' . $response->get_error_message() );
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			Logger::error( "DropboxAdapter: API call to {$endpoint} returned HTTP {$code}." );
			return null;
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $decoded ) ) {
			Logger::error( 'DropboxAdapter: could not decode JSON response from ' . $endpoint );
			return null;
		}

		return $decoded;
	}
}
