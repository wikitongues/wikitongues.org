<?php
/**
 * Google_Docs — reads Docs as the rights-exporter service account.
 *
 * Credentials come from the WT_RIGHTS_GOOGLE_CREDENTIALS_JSON environment
 * variable, which `op run` fills from 1Password for the length of one command.
 * They never touch disk or wp-config. The only scope is documents.readonly.
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * A read-only Google Docs client.
 */
final class Google_Docs {

	private const SCOPE     = 'https://www.googleapis.com/auth/documents.readonly';
	private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
	private const DOCS_URL  = 'https://docs.googleapis.com/v1/documents/';

	/**
	 * The service account key.
	 *
	 * @var array<string, mixed>
	 */
	private array $key;

	/**
	 * The transport.
	 *
	 * @var callable(string, string, array<string, string>, string|null): array{status: int, body: string}
	 */
	private $http;

	/**
	 * The access token, once fetched.
	 *
	 * @var string|null
	 */
	private ?string $token = null;

	/**
	 * Builds a client.
	 *
	 * @param array<string, mixed>                                                                $key  A service account key (type, client_email, private_key).
	 * @param callable(string, string, array<string, string>, string|null): array{status: int, body: string} $http The transport.
	 */
	public function __construct( array $key, callable $http ) {
		if ( 'service_account' !== ( $key['type'] ?? '' ) || empty( $key['client_email'] ) || empty( $key['private_key'] ) ) {
			throw new \InvalidArgumentException( 'The Google credentials are not a service account key.' );
		}
		$this->key  = $key;
		$this->http = $http;
	}

	/**
	 * A client from the environment, or an exception explaining how to run it.
	 *
	 * @param callable(string, string, array<string, string>, string|null): array{status: int, body: string} $http The transport.
	 */
	public static function from_env( callable $http ): self {
		$json = (string) getenv( 'WT_RIGHTS_GOOGLE_CREDENTIALS_JSON' );
		if ( '' === $json ) {
			throw new \RuntimeException( 'WT_RIGHTS_GOOGLE_CREDENTIALS_JSON is not set. Run through 1Password: op run --env-file ~/.config/wikitongues/rights.env -- wp …' );
		}
		$key = json_decode( $json, true );
		if ( ! is_array( $key ) ) {
			throw new \RuntimeException( 'WT_RIGHTS_GOOGLE_CREDENTIALS_JSON is not JSON.' );
		}
		return new self( $key, $http );
	}

	/**
	 * The service account's email, for messages.
	 */
	public function account(): string {
		return (string) $this->key['client_email'];
	}

	/**
	 * A Doc with every tab, open suggestions shown as rejected.
	 *
	 * @param string $document_id The ID from the Doc's URL.
	 * @return array<string, mixed> The `documents.get` response.
	 */
	public function document( string $document_id ): array {
		$url = self::DOCS_URL . rawurlencode( $document_id ) . '?includeTabsContent=true&suggestionsViewMode=PREVIEW_WITHOUT_SUGGESTIONS';
		return Http::json(
			( $this->http )( 'GET', $url, array( 'Authorization' => 'Bearer ' . $this->token() ), null ),
			'Google Docs'
		);
	}

	/**
	 * The Doc ID in a Google Docs URL, or '' when the URL isn't one.
	 *
	 * @param string $url A Doc link, e.g. https://docs.google.com/document/d/{id}/edit.
	 */
	public static function document_id( string $url ): string {
		return preg_match( '#docs\.google\.com/document/(?:u/\d+/)?d/([A-Za-z0-9_-]{20,})#', $url, $m ) ? $m[1] : '';
	}

	/**
	 * An access token, from a signed JWT (fetched once per run).
	 */
	private function token(): string {
		if ( null !== $this->token ) {
			return $this->token;
		}
		$now       = time();
		$header    = self::base64url(
			(string) wp_json_encode(
				array(
					'alg' => 'RS256',
					'typ' => 'JWT',
				)
			)
		);
		$claims    = self::base64url(
			(string) wp_json_encode(
				array(
					'iss'   => $this->key['client_email'],
					'scope' => self::SCOPE,
					'aud'   => self::TOKEN_URL,
					'iat'   => $now,
					'exp'   => $now + 3600,
				)
			)
		);
		$signature = '';
		if ( ! openssl_sign( $header . '.' . $claims, $signature, (string) $this->key['private_key'], OPENSSL_ALGO_SHA256 ) ) {
			throw new \RuntimeException( 'Could not sign with the Google key.' );
		}
		$jwt         = $header . '.' . $claims . '.' . self::base64url( $signature );
		$data        = Http::json(
			( $this->http )(
				'POST',
				self::TOKEN_URL,
				array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
				http_build_query(
					array(
						'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
						'assertion'  => $jwt,
					)
				)
			),
			'Google sign-in'
		);
		$this->token = (string) ( $data['access_token'] ?? '' );
		if ( '' === $this->token ) {
			throw new \RuntimeException( 'Google sign-in returned no access token.' );
		}
		return $this->token;
	}

	/**
	 * URL-safe base64 without padding.
	 *
	 * @param string $data Bytes.
	 */
	private static function base64url( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- JWT encoding.
	}
}
