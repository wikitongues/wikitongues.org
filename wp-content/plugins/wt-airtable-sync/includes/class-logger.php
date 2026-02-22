<?php
/**
 * Logger — thin wrapper around error_log with a consistent prefix and level labels.
 *
 * Usage:
 *   Logger::info( 'Sync started for post_type=languages' );
 *   Logger::error( 'Upsert failed: ' . $e->getMessage() );
 *
 * Output lands in the server error log (the same destination as native WP errors).
 * Phase 1+ may extend this to write to a dedicated log file or WP admin panel.
 *
 * @package WT\AirtableSync
 */

namespace WT\AirtableSync;

class Logger {

	private const PREFIX = '[wt-airtable-sync]';

	public static function info( string $message ): void {
		self::write( 'INFO', $message );
	}

	public static function error( string $message ): void {
		self::write( 'ERROR', $message );
	}

	private static function write( string $level, string $message ): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( sprintf( '%s [%s] %s', self::PREFIX, $level, $message ) );
	}
}
