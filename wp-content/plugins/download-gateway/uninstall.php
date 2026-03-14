<?php
/**
 * Uninstall — runs when the plugin is deleted via the WP admin.
 *
 * Sub-phase 0: removes the version option seeded on activation.
 * Sub-phase 1 will extend this to drop DB tables (wp_dg_people,
 * wp_dg_download_events, wp_dg_webhook_delivery, wp_dg_tokens)
 * after confirming the operator has exported any data they need.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Bootstrap only what's needed for Schema and Logger — avoid a full WP load.
define( 'DG_VERSION', '0.1.0' );
define( 'DG_FILE', __FILE__ );
define( 'DG_DIR', plugin_dir_path( __FILE__ ) );
define( 'DG_REST_NAMESPACE', 'gateway/v1' );

if ( ! defined( 'DG_ENABLED' ) ) {
	define( 'DG_ENABLED', false );
}

require_once DG_DIR . 'includes/class-logger.php';
require_once DG_DIR . 'includes/class-schema.php';

\WT\DownloadGateway\Schema::drop_tables();
delete_option( 'dg_version' );
