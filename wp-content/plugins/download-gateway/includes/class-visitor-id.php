<?php
/**
 * VisitorId — manages the gateway_vid anonymous visitor cookie.
 *
 * The visitor ID is a random 32-character hex string stored in a first-party
 * cookie. It links download events across requests without requiring a login
 * or storing PII. When a gate is completed, the person_id is linked to events
 * that share the same visitor_id.
 *
 * generate() and from_cookies() are pure functions — no side effects, fully
 * testable. set_cookie() is a side-effect wrapper intentionally excluded from
 * unit tests.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class VisitorId {

	const COOKIE_NAME = 'gateway_vid';
	const COOKIE_TTL  = YEAR_IN_SECONDS; // ~1 year; refreshed on each download.
	const LENGTH      = 32; // hex chars = 16 bytes of entropy.

	/**
	 * Generate a new visitor ID.
	 *
	 * @return string 32-character lowercase hex string.
	 */
	public static function generate(): string {
		return bin2hex( random_bytes( 16 ) );
	}

	/**
	 * Extract and validate a visitor ID from a cookies array.
	 *
	 * Returns null if the cookie is absent or its value does not match the
	 * expected format — prevents injected values from reaching the DB.
	 *
	 * @param array $cookies $_COOKIE or equivalent.
	 * @return string|null Valid visitor ID, or null if absent/invalid.
	 */
	public static function from_cookies( array $cookies ): ?string {
		$value = $cookies[ self::COOKIE_NAME ] ?? null;

		if ( ! is_string( $value ) ) {
			return null;
		}

		// Must be exactly 32 lowercase hex characters.
		return preg_match( '/^[0-9a-f]{32}$/', $value ) ? $value : null;
	}

	/**
	 * Set the gateway_vid cookie in the current response.
	 *
	 * Called from the download endpoint after a visitor ID has been resolved.
	 * Not unit-tested — this is a pure side effect.
	 *
	 * @param string $visitor_id A valid 32-char hex visitor ID.
	 */
	public static function set_cookie( string $visitor_id ): void {
		setcookie(
			self::COOKIE_NAME,
			$visitor_id,
			[
				'expires'  => time() + self::COOKIE_TTL,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
	}
}
