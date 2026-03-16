<?php
/**
 * RetentionJob — anonymizes stale person records on a daily schedule.
 *
 * People who submitted the gate form have their email and name nulled out
 * after `retention_months` (default: 24). The row is kept so that download
 * event records (which reference person_id) remain intact for reporting.
 * The `is_anonymized` flag prevents re-upsert from resurrecting a deleted
 * identity — re-capture after anonymization creates a fresh row.
 *
 * WP Cron fires only on page visits. Production environments should back
 * this up with a server cron: `wp cron event run --due-now`
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class RetentionJob {

	/** WP-Cron hook name. */
	const CRON_HOOK = 'gateway_retention_daily';

	/** Option key for last-run metadata. */
	const OPTION_LAST_RUN = 'gateway_retention_last_run';

	/**
	 * Anonymize person records older than the configured retention window.
	 *
	 * Nulls `email` and `name`, sets `is_anonymized = 1` and `anonymized_at`
	 * for all non-anonymized records whose `created_at` falls before the
	 * retention cutoff. Returns the number of records anonymized.
	 *
	 * @return int Number of records anonymized.
	 */
	public static function anonymize(): int {
		global $wpdb;

		$table            = $wpdb->prefix . 'gateway_people';
		$retention_months = SettingsRepository::get_retention_months();
		$now              = current_time( 'mysql' );

		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"UPDATE {$table}
			 SET email = NULL, name = NULL, is_anonymized = 1, anonymized_at = %s
			 WHERE is_anonymized = 0
			   AND created_at < DATE_SUB( %s, INTERVAL %d MONTH )",
			$now,
			$now,
			$retention_months
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $sql );
		$count  = false !== $result ? (int) $result : 0;

		update_option(
			self::OPTION_LAST_RUN,
			array(
				'timestamp' => $now,
				'count'     => $count,
			)
		);

		Logger::info( "Retention: anonymized {$count} record(s) older than {$retention_months} months." );

		return $count;
	}

	/**
	 * Schedule the daily retention cron event if not already scheduled.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule the daily retention cron event.
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}
}
