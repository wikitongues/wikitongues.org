<?php
/**
 * Logger — thin wrapper around error_log with a consistent prefix and level labels.
 *
 * Usage:
 *   Logger::info( 'Token issued for post_id=42' );
 *   Logger::error( 'File resolver failed: ' . $e->getMessage() );
 *   Logger::debug( 'PolicyResolver: no per-resource gate, checking global default' );
 *
 * Debug messages are suppressed unless WP_DEBUG is true, keeping production logs clean.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class Logger {

	private const PREFIX = '[download-gateway]';

	public static function info( string $message ): void {
		self::write( 'INFO', $message );
	}

	public static function error( string $message ): void {
		self::write( 'ERROR', $message );
	}

	public static function debug( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			self::write( 'DEBUG', $message );
		}
	}

	private static function write( string $level, string $message ): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( sprintf( '%s [%s] %s', self::PREFIX, $level, $message ) );
	}
}
