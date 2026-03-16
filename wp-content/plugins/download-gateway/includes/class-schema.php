<?php
/**
 * Schema — creates and drops the plugin's custom DB tables.
 *
 * All four tables are created on activation via dbDelta(), which makes
 * the operation idempotent: re-running on an existing install adds missing
 * columns/indexes without destroying data.
 *
 * Table overview:
 *
 *   wp_gateway_tokens          — one-time signed download tokens (sub-phases 3, 5)
 *   wp_gateway_people          — email-known visitors captured via the gate (sub-phase 5)
 *   wp_gateway_download_events — every download lifecycle event (sub-phase 3+)
 *   wp_gateway_webhook_delivery — outbound webhook retry queue (sub-phase 2c)
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class Schema {

	/** Option key that stores the installed schema version. */
	const VERSION_OPTION = 'gateway_schema_version';

	/** Bump this when the schema changes to trigger a migration. */
	const SCHEMA_VERSION = 1;

	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		/*
		 * wp_gateway_tokens
		 *
		 * One record per download token issued. Tokens are single-use and
		 * short-lived. `used_at` is set on redemption; expired unused tokens
		 * are pruned by a scheduled job (sub-phase 9).
		 */
		dbDelta(
			"CREATE TABLE {$wpdb->prefix}gateway_tokens (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				token VARCHAR(64) NOT NULL,
				post_id BIGINT UNSIGNED NOT NULL,
				visitor_id VARCHAR(64) DEFAULT NULL,
				person_id BIGINT UNSIGNED DEFAULT NULL,
				expires_at DATETIME NOT NULL,
				used_at DATETIME DEFAULT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY token (token),
				KEY post_id (post_id),
				KEY expires_at (expires_at)
			) $charset;"
		);

		/*
		 * wp_gateway_people
		 *
		 * One record per email-known visitor. Email and name are nullable so
		 * they can be nulled out by the retention job without deleting the row
		 * (event records reference person_id and must remain intact for reporting).
		 * email_hash is SHA-256 of the lowercased, trimmed email — used for dedup
		 * and lookup without requiring plaintext storage.
		 */
		dbDelta(
			"CREATE TABLE {$wpdb->prefix}gateway_people (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				email_hash VARCHAR(64) NOT NULL,
				email VARCHAR(255) DEFAULT NULL,
				name VARCHAR(255) DEFAULT NULL,
				consent_download TINYINT(1) NOT NULL DEFAULT 0,
				consent_marketing TINYINT(1) NOT NULL DEFAULT 0,
				is_anonymized TINYINT(1) NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL,
				anonymized_at DATETIME DEFAULT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY email_hash (email_hash),
				KEY is_anonymized (is_anonymized)
			) $charset;"
		);

		/*
		 * wp_gateway_download_events
		 *
		 * One record per lifecycle event in the download funnel:
		 *   click       — user clicked a download link (token issued)
		 *   gate_view   — gate modal was shown
		 *   gate_submit — user submitted the gate form
		 *   redirect    — token redeemed, file redirect sent
		 *
		 * post_type is stored alongside post_id so events remain queryable
		 * even if a post is deleted. UTM params and referrer are captured
		 * client-side and forwarded with the token request.
		 */
		dbDelta(
			"CREATE TABLE {$wpdb->prefix}gateway_download_events (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id BIGINT UNSIGNED NOT NULL,
				post_type VARCHAR(50) NOT NULL,
				event_type VARCHAR(20) NOT NULL,
				visitor_id VARCHAR(64) DEFAULT NULL,
				person_id BIGINT UNSIGNED DEFAULT NULL,
				storage_type VARCHAR(20) DEFAULT NULL,
				ip_hash VARCHAR(64) DEFAULT NULL,
				utm_source VARCHAR(255) DEFAULT NULL,
				utm_medium VARCHAR(255) DEFAULT NULL,
				utm_campaign VARCHAR(255) DEFAULT NULL,
				utm_term VARCHAR(255) DEFAULT NULL,
				utm_content VARCHAR(255) DEFAULT NULL,
				referrer VARCHAR(2083) DEFAULT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY post_id (post_id),
				KEY visitor_id (visitor_id),
				KEY person_id (person_id),
				KEY event_type (event_type),
				KEY created_at (created_at)
			) $charset;"
		);

		/*
		 * wp_gateway_webhook_delivery
		 *
		 * Outbound webhook retry queue. Each row represents one delivery attempt
		 * for a download event payload to a configured endpoint. Status transitions:
		 *   pending → delivered  (HTTP 2xx on first or retry attempt)
		 *   pending → failed     (non-2xx; eligible for retry)
		 *   failed  → dead       (max attempts exceeded)
		 */
		dbDelta(
			"CREATE TABLE {$wpdb->prefix}gateway_webhook_delivery (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				event_id BIGINT UNSIGNED NOT NULL,
				endpoint_url VARCHAR(2083) NOT NULL,
				payload LONGTEXT NOT NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'pending',
				attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				last_attempt_at DATETIME DEFAULT NULL,
				next_attempt_at DATETIME DEFAULT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY event_id (event_id),
				KEY status (status),
				KEY next_attempt_at (next_attempt_at)
			) $charset;"
		);

		update_option( self::VERSION_OPTION, self::SCHEMA_VERSION );
		Logger::info( 'Schema v' . self::SCHEMA_VERSION . ' applied.' );
	}

	public static function drop_tables(): void {
		global $wpdb;

		// Drop in reverse dependency order (events reference people; tokens reference both).
		$tables = array(
			"{$wpdb->prefix}gateway_webhook_delivery",
			"{$wpdb->prefix}gateway_download_events",
			"{$wpdb->prefix}gateway_tokens",
			"{$wpdb->prefix}gateway_people",
		);

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}

		delete_option( self::VERSION_OPTION );
		Logger::info( 'All gateway tables dropped.' );
	}
}
