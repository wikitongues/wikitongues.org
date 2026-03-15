<?php
/**
 * PeopleRepository — CRUD on wp_gateway_people.
 *
 * People are identified by the SHA-256 hash of their lowercase, trimmed email
 * address. Upsert is the primary operation: insert on first capture, update
 * name/consent on repeat submissions (e.g. upgraded consent).
 *
 * Anonymized records (is_anonymized = 1) are excluded from lookups so that
 * a re-capture after anonymization creates a fresh record rather than
 * resurrecting a deleted one.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class PeopleRepository {

	/**
	 * Upsert a person by email address.
	 *
	 * Returns the person_id on success, false on DB error.
	 *
	 * @param string $email            Raw email address.
	 * @param string $name             Display name.
	 * @param bool   $consent_download Consented to download-related comms.
	 * @param bool   $consent_marketing Consented to marketing comms.
	 * @return int|false
	 */
	public static function upsert(
		string $email,
		string $name,
		bool $consent_download,
		bool $consent_marketing = false
	): int|false {
		global $wpdb;

		$table      = $wpdb->prefix . 'gateway_people';
		$email_hash = self::hash_email( $email );

		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE email_hash = %s AND is_anonymized = 0",
				$email_hash
			)
		);

		if ( null !== $existing_id ) {
			$wpdb->update(
				$table,
				[
					'name'               => sanitize_text_field( $name ),
					'consent_download'   => $consent_download ? 1 : 0,
					'consent_marketing'  => $consent_marketing ? 1 : 0,
				],
				[ 'id' => (int) $existing_id ]
			);
			return (int) $existing_id;
		}

		$result = $wpdb->insert(
			$table,
			[
				'email_hash'         => $email_hash,
				'email'              => sanitize_email( $email ),
				'name'               => sanitize_text_field( $name ),
				'consent_download'   => $consent_download ? 1 : 0,
				'consent_marketing'  => $consent_marketing ? 1 : 0,
				'is_anonymized'      => 0,
				'created_at'         => current_time( 'mysql' ),
			]
		);

		if ( false === $result ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Find a non-anonymized person by ID.
	 *
	 * Used by the passthrough path to verify the gateway_gated cookie value
	 * still corresponds to an active (non-anonymized) person record.
	 *
	 * @param int $person_id Person ID.
	 * @return object|null
	 */
	public static function find_by_id( int $person_id ): ?object {
		global $wpdb;

		$table = $wpdb->prefix . 'gateway_people';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND is_anonymized = 0",
				$person_id
			)
		);

		return $row ?: null;
	}

	/**
	 * Find a non-anonymized person by email address.
	 *
	 * @param string $email Raw email address.
	 * @return object|null
	 */
	public static function find_by_email( string $email ): ?object {
		global $wpdb;

		$table      = $wpdb->prefix . 'gateway_people';
		$email_hash = self::hash_email( $email );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE email_hash = %s AND is_anonymized = 0",
				$email_hash
			)
		);

		return $row ?: null;
	}

	/**
	 * Hash an email address for storage and lookup.
	 *
	 * @param string $email Raw email address.
	 * @return string 64-char lowercase hex SHA-256 hash.
	 */
	public static function hash_email( string $email ): string {
		return hash( 'sha256', strtolower( trim( $email ) ) );
	}
}
