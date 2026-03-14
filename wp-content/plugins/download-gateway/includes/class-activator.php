<?php
/**
 * Activator — handles plugin activation and deactivation lifecycle.
 *
 * Activation checks environment requirements, seeds the plugin version option,
 * and creates all custom DB tables via Schema::create_tables().
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class Activator {

	public static function activate(): void {
		self::check_requirements();
		update_option( 'gateway_version', GATEWAY_VERSION );
		Schema::create_tables();
		Logger::info( 'Plugin activated (v' . GATEWAY_VERSION . ').' );
	}

	public static function deactivate(): void {
		Logger::info( 'Plugin deactivated.' );
	}

	private static function check_requirements(): void {
		$errors = [];

		if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
			$errors[] = 'Download Gateway requires PHP 8.2 or higher. Current version: ' . PHP_VERSION;
		}

		if ( ! function_exists( 'get_field' ) ) {
			$errors[] = 'Download Gateway requires Advanced Custom Fields (ACF) to be active.';
		}

		if ( ! empty( $errors ) ) {
			// Deactivate and show errors — wp_die() is appropriate in activation context.
			deactivate_plugins( plugin_basename( GATEWAY_FILE ) );
			wp_die(
				'<strong>Download Gateway could not be activated:</strong><ul><li>'
				. implode( '</li><li>', array_map( 'esc_html', $errors ) )
				. '</li></ul>',
				'Plugin Activation Error',
				[ 'back_link' => true ]
			);
		}
	}
}
