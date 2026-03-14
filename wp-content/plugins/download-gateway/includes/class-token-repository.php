<?php
/**
 * TokenRepository — CRUD for wp_gateway_tokens.
 *
 * Tokens are single-use, short-lived signed strings that authorize one
 * file redirect. A token is valid if it has not been redeemed (used_at IS NULL)
 * and has not expired (expires_at > now).
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class TokenRepository {

	const TTL_DEFAULT = 3600; // 1 hour in seconds.

	/**
	 * Generate a new token and insert it into the DB.
	 *
	 * @param int         $post_id      Post ID of the downloadable item.
	 * @param int         $ttl_seconds  Seconds until expiry. Defaults to TTL_DEFAULT.
	 * @param string|null $visitor_id   gateway_vid cookie value, if known.
	 * @param int|null    $person_id    FK to wp_gateway_people, if gate was completed.
	 * @return string 64-character hex token.
	 */
	public static function create(
		int $post_id,
		int $ttl_seconds = self::TTL_DEFAULT,
		?string $visitor_id = null,
		?int $person_id = null
	): string {
		global $wpdb;

		$token = bin2hex( random_bytes( 32 ) );

		$wpdb->insert(
			$wpdb->prefix . 'gateway_tokens',
			[
				'token'      => $token,
				'post_id'    => $post_id,
				'visitor_id' => $visitor_id,
				'person_id'  => $person_id,
				'expires_at' => gmdate( 'Y-m-d H:i:s', time() + $ttl_seconds ),
				'created_at' => current_time( 'mysql' ),
			]
		);

		Logger::debug( "Token created for post_id={$post_id}." );

		return $token;
	}

	/**
	 * Find a token row by its string value.
	 *
	 * @param string $token The raw token string.
	 * @return object|null Row object, or null if not found.
	 */
	public static function find_by_token( string $token ): ?object {
		global $wpdb;

		$table = $wpdb->prefix . 'gateway_tokens';
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE token = %s", $token )
		);

		return $row ?: null;
	}

	/**
	 * Mark a token as redeemed by setting used_at to now.
	 *
	 * @param string $token The raw token string.
	 * @return bool True on success, false on failure.
	 */
	public static function mark_used( string $token ): bool {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . 'gateway_tokens',
			[ 'used_at' => current_time( 'mysql' ) ],
			[ 'token'   => $token ]
		);

		return false !== $result;
	}

	/**
	 * Check whether a token row is still valid.
	 *
	 * Pure function — no DB access. Accepts an optional $now timestamp so
	 * tests can assert time-sensitive behaviour without relying on the clock.
	 *
	 * @param object   $row A row object from find_by_token().
	 * @param int|null $now Unix timestamp to treat as "now". Defaults to time().
	 * @return bool True if the token is unused and not expired.
	 */
	public static function is_valid( object $row, ?int $now = null ): bool {
		if ( null !== $row->used_at ) {
			return false;
		}

		$now = $now ?? time();
		return strtotime( $row->expires_at ) > $now;
	}

	/**
	 * Delete all expired, unused tokens.
	 *
	 * Intended for the scheduled retention job (sub-phase 9).
	 *
	 * @return int Number of rows deleted.
	 */
	public static function purge_expired(): int {
		global $wpdb;

		$table  = $wpdb->prefix . 'gateway_tokens';
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE expires_at < %s AND used_at IS NULL",
				current_time( 'mysql' )
			)
		);

		return (int) $result;
	}
}
