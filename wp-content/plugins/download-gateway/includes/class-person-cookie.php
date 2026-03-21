<?php
/**
 * PersonCookie — HMAC-signed gateway_gated cookie.
 *
 * The gateway_gated cookie stores the visitor's person_id so returning
 * visitors can skip the gate form (silent passthrough). Without signing,
 * any visitor can forge an arbitrary integer to impersonate another person
 * (IDOR) or to bypass rate limiting.
 *
 * Cookie format: "{person_id}.{sha256-hmac}"
 *
 * The HMAC is keyed on NONCE_KEY (a 64-byte random secret defined in
 * wp-config.php). Tampered or forged values are rejected at verify time;
 * the server falls back to showing the gate form.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class PersonCookie {

	/**
	 * Sign a person_id and return the cookie value.
	 *
	 * @param int $person_id Positive integer person ID.
	 * @return string Signed cookie value in the form "{person_id}.{hmac}".
	 */
	public static function sign( int $person_id ): string {
		$hmac = hash_hmac( 'sha256', (string) $person_id, NONCE_KEY );
		return $person_id . '.' . $hmac;
	}

	/**
	 * Verify a cookie value and return the person_id if valid.
	 *
	 * Returns false if the value is malformed, the person_id is not a
	 * positive integer, or the HMAC does not match (tampered/forged).
	 *
	 * @param string $value Cookie value to verify.
	 * @return int|false Verified person_id on success, false on failure.
	 */
	public static function verify( string $value ): int|false {
		$parts = explode( '.', $value, 2 );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		[ $id_str, $hmac ] = $parts;

		if ( ! ctype_digit( $id_str ) || (int) $id_str <= 0 ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $id_str, NONCE_KEY );
		if ( ! hash_equals( $expected, $hmac ) ) {
			return false;
		}

		return (int) $id_str;
	}
}
