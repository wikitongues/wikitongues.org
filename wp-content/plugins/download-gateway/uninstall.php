<?php
/**
 * Uninstall — runs when the plugin is deleted via the WP admin.
 *
 * Removes all gateway DB tables, wp_options entries, and WP-Cron events.
 * Tables dropped: wp_gateway_intake_responses, wp_gateway_webhook_delivery,
 * wp_gateway_download_events, wp_gateway_tokens, wp_gateway_people.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Bootstrap only what's needed — avoid a full plugin load.
define( 'GATEWAY_VERSION', '0.1.9' );
define( 'GATEWAY_FILE', __FILE__ );
define( 'GATEWAY_DIR', plugin_dir_path( __FILE__ ) );
define( 'GATEWAY_REST_NAMESPACE', 'gateway/v1' );

if ( ! defined( 'GATEWAY_ENABLED' ) ) {
	define( 'GATEWAY_ENABLED', false );
}

require_once GATEWAY_DIR . 'includes/class-logger.php';
require_once GATEWAY_DIR . 'includes/class-schema.php';
require_once GATEWAY_DIR . 'includes/class-retention-job.php';
require_once GATEWAY_DIR . 'includes/class-settings-repository.php';

// Drop all custom tables (includes wp_gateway_intake_responses).
\WT\DownloadGateway\Schema::drop_tables();

// Unschedule the daily retention cron event.
\WT\DownloadGateway\RetentionJob::unschedule();

// Remove scalar wp_options entries.
delete_option( 'gateway_version' );
delete_option( \WT\DownloadGateway\Schema::VERSION_OPTION );
delete_option( \WT\DownloadGateway\SettingsRepository::OPTION_GLOBAL_GATE_POLICY );
delete_option( \WT\DownloadGateway\SettingsRepository::OPTION_RETENTION_MONTHS );
delete_option( \WT\DownloadGateway\RetentionJob::OPTION_LAST_RUN );

// Remove all per-CPT policy options (gateway_cpt_policy_*).
global $wpdb;
$cpt_options = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( \WT\DownloadGateway\SettingsRepository::OPTION_CPT_POLICY_PREFIX ) . '%'
	)
);
foreach ( $cpt_options as $option_name ) {
	delete_option( $option_name );
}
