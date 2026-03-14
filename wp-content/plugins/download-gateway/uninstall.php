<?php
/**
 * Uninstall — runs when the plugin is deleted via the WP admin.
 *
 * Removes all gateway options and drops all custom DB tables.
 * Tables dropped: wp_gateway_people, wp_gateway_download_events,
 * wp_gateway_webhook_delivery, wp_gateway_tokens.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Bootstrap only what's needed for Schema and Logger — avoid a full WP load.
define( 'GATEWAY_VERSION', '0.1.0' );
define( 'GATEWAY_FILE', __FILE__ );
define( 'GATEWAY_DIR', plugin_dir_path( __FILE__ ) );
define( 'GATEWAY_REST_NAMESPACE', 'gateway/v1' );

if ( ! defined( 'GATEWAY_ENABLED' ) ) {
	define( 'GATEWAY_ENABLED', false );
}

require_once GATEWAY_DIR . 'includes/class-logger.php';
require_once GATEWAY_DIR . 'includes/class-schema.php';

\WT\DownloadGateway\Schema::drop_tables();
delete_option( 'gateway_version' );
