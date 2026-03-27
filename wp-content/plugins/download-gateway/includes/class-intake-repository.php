<?php
/**
 * IntakeRepository — persists intake form responses to wp_gateway_intake_responses.
 *
 * The `responses` column is a JSON blob whose shape is determined by the
 * `gateway_intake_fields` filter. The repository stores it opaquely so the
 * table schema never needs to change when field definitions change.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class IntakeRepository {

	/**
	 * Persist an intake response.
	 *
	 * @param int                  $person_id Person ID from wp_gateway_people.
	 * @param int                  $post_id   Post ID of the downloaded resource.
	 * @param string               $post_type Post type slug.
	 * @param array<string,string> $responses Key-value map of sanitized field responses.
	 * @return int|false Inserted row ID on success, false on DB error.
	 */
	public static function save( int $person_id, int $post_id, string $post_type, array $responses ): int|false {
		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . 'gateway_intake_responses',
			array(
				'person_id'  => $person_id,
				'post_id'    => $post_id,
				'post_type'  => sanitize_key( $post_type ),
				'responses'  => (string) wp_json_encode( $responses ),
				'created_at' => current_time( 'mysql' ),
			)
		);

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}
}
