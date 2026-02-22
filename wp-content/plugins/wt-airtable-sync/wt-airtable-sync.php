<?php
/**
 * Plugin Name:       WT Airtable Sync
 * Plugin URI:        https://github.com/wikitongues/wikitongues.org
 * Description:       HTTP transport layer for Airtable → WordPress content sync. Make.com POSTs raw Airtable payloads; this plugin owns all field mapping, relationship resolution, and ACF writes in code.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            Wikitongues
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace WT\AirtableSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WT_AIRTABLE_SYNC_VERSION', '0.1.0' );
define( 'WT_AIRTABLE_SYNC_FILE', __FILE__ );
define( 'WT_AIRTABLE_SYNC_DIR', plugin_dir_path( __FILE__ ) );

require_once WT_AIRTABLE_SYNC_DIR . 'includes/class-logger.php';
require_once WT_AIRTABLE_SYNC_DIR . 'includes/class-sync-api.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\activate' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deactivate' );

/**
 * Activation: warn if WT_SYNC_API_KEY is not yet defined in wp-config.php.
 */
function activate(): void {
	if ( ! defined( 'WT_SYNC_API_KEY' ) || '' === WT_SYNC_API_KEY ) {
		update_option( 'wt_sync_key_missing', true );
	} else {
		delete_option( 'wt_sync_key_missing' );
	}
}

/**
 * Deactivation: clean up transient flags.
 */
function deactivate(): void {
	delete_option( 'wt_sync_key_missing' );
}

/**
 * Show an admin notice when WT_SYNC_API_KEY is absent.
 * The endpoint returns 503 in this state, so the notice is actionable.
 */
add_action(
	'admin_notices',
	function (): void {
		if ( ! defined( 'WT_SYNC_API_KEY' ) || '' === WT_SYNC_API_KEY ) {
			echo '<div class="notice notice-error"><p>';
			echo '<strong>WT Airtable Sync:</strong> ';
			echo '<code>WT_SYNC_API_KEY</code> is not defined in <code>wp-config.php</code>. ';
			echo 'The sync endpoint will reject all requests until this constant is set.';
			echo '</p></div>';
		}
	}
);

/**
 * Register the sync REST route once the REST API is initialised.
 */
add_action(
	'rest_api_init',
	function (): void {
		( new Sync_API() )->register_routes();
	}
);
