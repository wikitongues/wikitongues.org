<?php
/**
 * DownloadEventRepository — writes download lifecycle events to wp_gateway_download_events.
 *
 * Every meaningful step in the download funnel (click, gate_view, gate_submit,
 * redirect) is logged here. The repository is intentionally write-only at this
 * stage; read/aggregate queries are added in sub-phase 8 (admin reporting).
 *
 * Usage:
 *   $id = DownloadEventRepository::log([
 *       'post_id'      => 42,
 *       'post_type'    => 'document_files',
 *       'event_type'   => 'click',
 *       'visitor_id'   => 'abc123',
 *       'utm_source'   => 'newsletter',
 *   ]);
 *
 * All keys except post_id, post_type, and event_type are optional.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class DownloadEventRepository {

	// Valid event_type values — mirrors the schema comment in class-schema.php.
	const EVENT_CLICK       = 'click';
	const EVENT_GATE_VIEW   = 'gate_view';
	const EVENT_GATE_SUBMIT = 'gate_submit';
	const EVENT_REDIRECT    = 'redirect';

	/**
	 * Insert a download event row.
	 *
	 * @param array $data {
	 *     @type int    $post_id      Required. Post ID of the downloadable item.
	 *     @type string $post_type    Required. Post type of the downloadable item.
	 *     @type string $event_type   Required. One of the EVENT_* constants.
	 *     @type string $visitor_id   Optional. Value of the gateway_vid cookie.
	 *     @type int    $person_id    Optional. FK to wp_gateway_people.
	 *     @type string $storage_type Optional. 'local'|'media'|'dropbox'|'external'.
	 *     @type string $ip_hash      Optional. SHA-256 of the request IP.
	 *     @type string $utm_source   Optional.
	 *     @type string $utm_medium   Optional.
	 *     @type string $utm_campaign Optional.
	 *     @type string $utm_term     Optional.
	 *     @type string $utm_content  Optional.
	 *     @type string $referrer     Optional.
	 * }
	 * @return int|false Inserted row ID, or false on failure.
	 */
	public static function log( array $data ): int|false {
		global $wpdb;

		if ( empty( $data['post_id'] ) || empty( $data['post_type'] ) || empty( $data['event_type'] ) ) {
			Logger::error( 'DownloadEventRepository::log() called with missing required fields.' );
			return false;
		}

		$row = array(
			'post_id'      => (int) $data['post_id'],
			'post_type'    => sanitize_key( $data['post_type'] ),
			'event_type'   => sanitize_key( $data['event_type'] ),
			'visitor_id'   => isset( $data['visitor_id'] ) ? sanitize_text_field( $data['visitor_id'] ) : null,
			'person_id'    => isset( $data['person_id'] ) ? (int) $data['person_id'] : null,
			'storage_type' => isset( $data['storage_type'] ) ? sanitize_key( $data['storage_type'] ) : null,
			'ip_hash'      => isset( $data['ip_hash'] ) ? sanitize_text_field( $data['ip_hash'] ) : null,
			'utm_source'   => isset( $data['utm_source'] ) ? sanitize_text_field( $data['utm_source'] ) : null,
			'utm_medium'   => isset( $data['utm_medium'] ) ? sanitize_text_field( $data['utm_medium'] ) : null,
			'utm_campaign' => isset( $data['utm_campaign'] ) ? sanitize_text_field( $data['utm_campaign'] ) : null,
			'utm_term'     => isset( $data['utm_term'] ) ? sanitize_text_field( $data['utm_term'] ) : null,
			'utm_content'  => isset( $data['utm_content'] ) ? sanitize_text_field( $data['utm_content'] ) : null,
			'referrer'     => isset( $data['referrer'] ) ? esc_url_raw( $data['referrer'] ) : null,
			'created_at'   => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( $wpdb->prefix . 'gateway_download_events', $row );

		if ( false === $result ) {
			Logger::error( 'DownloadEventRepository::log() DB insert failed: ' . $wpdb->last_error );
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Return the ID of the most recent redirect event for a person + post.
	 *
	 * Used by IntakeController to include the download event ID in the intake
	 * webhook payload so Make.com can update the correct Downloads record.
	 *
	 * @param int $person_id Person ID from wp_gateway_people.
	 * @param int $post_id   Post ID of the downloaded resource.
	 * @return int|null Row ID, or null if no matching redirect event found.
	 */
	public static function find_redirect_id( int $person_id, int $post_id ): ?int {
		global $wpdb;

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}gateway_download_events
				 WHERE person_id = %d
				   AND post_id   = %d
				   AND event_type = %s
				 ORDER BY created_at DESC
				 LIMIT 1",
				$person_id,
				$post_id,
				self::EVENT_REDIRECT
			)
		);

		return null !== $id ? (int) $id : null;
	}
}
