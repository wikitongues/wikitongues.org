<?php
/**
 * IpHasher — one-way hash of a request IP address for privacy-safe storage.
 *
 * Raw IPs are never written to the DB. Instead, a SHA-256 hex digest of the
 * normalized address is stored in wp_gateway_download_events.ip_hash, allowing
 * session-level correlation without retaining PII.
 *
 * Normalization before hashing:
 *   - Trim surrounding whitespace
 *   - Lowercase (makes IPv6 representations canonical)
 *   - Strip IPv6 zone IDs (e.g. fe80::1%eth0 → fe80::1)
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class IpHasher {

	/**
	 * Returns a SHA-256 hex digest of the normalized IP address.
	 *
	 * @param string $ip Raw IP string from $_SERVER['REMOTE_ADDR'] or similar.
	 * @return string 64-character lowercase hex string.
	 */
	public static function hash( string $ip ): string {
		return hash( 'sha256', self::normalize( $ip ) );
	}

	/**
	 * Returns the IP address as it will be hashed (useful for debugging).
	 *
	 * @param string $ip Raw IP string.
	 * @return string Normalized IP string.
	 */
	public static function normalize( string $ip ): string {
		$ip = trim( $ip );
		$ip = strtolower( $ip );

		// Strip IPv6 zone ID (e.g. "fe80::1%eth0" → "fe80::1").
		$zone_pos = strpos( $ip, '%' );
		if ( false !== $zone_pos ) {
			$ip = substr( $ip, 0, $zone_pos );
		}

		return $ip;
	}

	/**
	 * Extracts and hashes the best available client IP from a server array.
	 *
	 * Checks X-Forwarded-For first (leftmost address = original client),
	 * falling back to REMOTE_ADDR. Only use X-Forwarded-For if your server
	 * is behind a trusted reverse proxy — there is no forgery protection here.
	 *
	 * @param array $server $_SERVER superglobal or equivalent.
	 * @return string 64-character hex hash, or hash of empty string if no IP found.
	 */
	public static function hash_from_server( array $server ): string {
		if ( ! empty( $server['HTTP_X_FORWARDED_FOR'] ) ) {
			// X-Forwarded-For may be a comma-separated list; take the first.
			$forwarded = explode( ',', $server['HTTP_X_FORWARDED_FOR'] );
			$ip        = trim( $forwarded[0] );
			if ( '' !== $ip ) {
				return self::hash( $ip );
			}
		}

		return self::hash( (string) ( $server['REMOTE_ADDR'] ?? '' ) );
	}
}
