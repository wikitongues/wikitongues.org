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

	/**
	 * Return which of $keys the person has answered in at least one prior intake.
	 *
	 * Responses are stored as a JSON blob; each row is decoded and checked for
	 * key presence. Short-circuits once all $keys are accounted for.
	 *
	 * @param int      $person_id Person ID from wp_gateway_people.
	 * @param string[] $keys      Field keys to check, e.g. ['community','organization'].
	 * @return string[]           Subset of $keys found non-empty in any prior response.
	 */
	public static function get_answered_keys( int $person_id, array $keys ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT responses FROM {$wpdb->prefix}gateway_intake_responses WHERE person_id = %d",
				$person_id
			)
		);

		$answered = array();
		foreach ( $rows as $row ) {
			$data = json_decode( $row->responses, true );
			if ( ! is_array( $data ) ) {
				continue;
			}
			foreach ( $keys as $key ) {
				if ( ! empty( $data[ $key ] ) && ! in_array( $key, $answered, true ) ) {
					$answered[] = $key;
				}
			}
			if ( count( $answered ) === count( $keys ) ) {
				break;
			}
		}

		return $answered;
	}
}
