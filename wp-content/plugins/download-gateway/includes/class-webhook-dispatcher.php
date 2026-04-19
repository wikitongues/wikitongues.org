<?php
/**
 * WebhookDispatcher — outbound webhook delivery with exponential-backoff retry queue.
 *
 * Enqueues webhook payloads into wp_gateway_webhook_delivery and delivers them
 * asynchronously via WP-Cron every 5 minutes. Up to MAX_ATTEMPTS delivery
 * attempts are made before a row is marked dead.
 *
 * Status transitions:
 *   pending → delivered  (HTTP 2xx on any attempt)
 *   pending → failed     (non-2xx; eligible for retry)
 *   failed  → dead       (MAX_ATTEMPTS reached)
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class WebhookDispatcher {

	/** Maximum delivery attempts before a row is marked dead. */
	const MAX_ATTEMPTS = 5;

	/** WP-Cron hook name. */
	const CRON_HOOK = 'gateway_webhook_dispatch';

	/**
	 * Enqueue a webhook payload for delivery.
	 *
	 * Inserts a pending row into wp_gateway_webhook_delivery with
	 * next_attempt_at set to now so it is picked up on the next cron run.
	 *
	 * @param int    $event_id     Reference ID (person_id, download event ID, or intake ID).
	 * @param string $endpoint_url Target webhook URL.
	 * @param array  $payload      Data to deliver as JSON.
	 */
	public static function enqueue( int $event_id, string $endpoint_url, array $payload ): void {
		global $wpdb;

		$now = current_time( 'mysql' );

		$wpdb->insert(
			$wpdb->prefix . 'gateway_webhook_delivery',
			array(
				'event_id'        => $event_id,
				'endpoint_url'    => $endpoint_url,
				'payload'         => (string) wp_json_encode( $payload ),
				'status'          => 'pending',
				'attempts'        => 0,
				'last_attempt_at' => null,
				'next_attempt_at' => $now,
				'created_at'      => $now,
			)
		);
	}

	/**
	 * Process up to 50 pending/failed rows whose next_attempt_at is due.
	 *
	 * Called by WP-Cron via CRON_HOOK every 5 minutes.
	 */
	public static function dispatch_pending(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$wpdb->prefix}gateway_webhook_delivery
				 WHERE status IN ('pending','failed')
				   AND next_attempt_at <= %s
				 ORDER BY id ASC
				 LIMIT 50",
				current_time( 'mysql' )
			)
		);

		if ( ! $rows ) {
			return;
		}

		foreach ( $rows as $row ) {
			self::attempt( $row );
		}
	}

	/**
	 * Attempt delivery of a single webhook row and update its status.
	 *
	 * @param object $row Row from wp_gateway_webhook_delivery.
	 */
	private static function attempt( object $row ): void {
		global $wpdb;

		$response = wp_remote_post(
			$row->endpoint_url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => $row->payload,
				'timeout' => 10,
			)
		);

		$attempts = (int) $row->attempts + 1;
		$now      = current_time( 'mysql' );
		$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

		if ( $code >= 200 && $code < 300 ) {
			$wpdb->update(
				$wpdb->prefix . 'gateway_webhook_delivery',
				array(
					'status'          => 'delivered',
					'attempts'        => $attempts,
					'last_attempt_at' => $now,
					'next_attempt_at' => null,
				),
				array( 'id' => $row->id )
			);
		} elseif ( $attempts >= self::MAX_ATTEMPTS ) {
			$wpdb->update(
				$wpdb->prefix . 'gateway_webhook_delivery',
				array(
					'status'          => 'dead',
					'attempts'        => $attempts,
					'last_attempt_at' => $now,
					'next_attempt_at' => null,
				),
				array( 'id' => $row->id )
			);
		} else {
			$wpdb->update(
				$wpdb->prefix . 'gateway_webhook_delivery',
				array(
					'status'          => 'failed',
					'attempts'        => $attempts,
					'last_attempt_at' => $now,
					'next_attempt_at' => self::backoff( $attempts ),
				),
				array( 'id' => $row->id )
			);
		}
	}

	/**
	 * Calculate the next attempt datetime based on the attempt number.
	 *
	 * Delay schedule: 1→60s, 2→300s, 3→1800s, 4→7200s (capped at last value).
	 *
	 * @param int $attempt New total attempts count (1-indexed).
	 * @return string MySQL datetime string for the next attempt.
	 */
	private static function backoff( int $attempt ): string {
		$delays = array( 60, 300, 1800, 7200 );
		$index  = min( $attempt - 1, count( $delays ) - 1 );
		return gmdate( 'Y-m-d H:i:s', time() + $delays[ $index ] );
	}

	/**
	 * Register the WP-Cron event for this hook.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'every_5_minutes', self::CRON_HOOK );
		}
	}

	/**
	 * Deregister the WP-Cron event for this hook.
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}
}
