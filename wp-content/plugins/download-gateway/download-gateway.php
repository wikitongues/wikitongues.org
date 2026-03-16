<?php
/**
 * Plugin Name:       Wikitongues Download Gateway
 * Plugin URI:        https://github.com/wikitongues/wikitongues.org
 * Description:       Signed download tokens, optional email gate, download event logging, and GA4 forwarding for all downloadable resources (document files, videos, captions, and future types).
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            Wikitongues
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace WT\DownloadGateway;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GATEWAY_VERSION', '0.1.0' );
define( 'GATEWAY_FILE', __FILE__ );
define( 'GATEWAY_DIR', plugin_dir_path( __FILE__ ) );
define( 'GATEWAY_REST_NAMESPACE', 'gateway/v1' );

/**
 * Feature flag. Set to true in wp-config.php to enable gateway functionality.
 * When false, the plugin registers its admin UI but does not intercept downloads.
 *
 *   define( 'GATEWAY_ENABLED', true );
 */
if ( ! defined( 'GATEWAY_ENABLED' ) ) {
	define( 'GATEWAY_ENABLED', false );
}

require_once GATEWAY_DIR . 'includes/class-logger.php';
require_once GATEWAY_DIR . 'includes/class-schema.php';
require_once GATEWAY_DIR . 'includes/class-activator.php';
require_once GATEWAY_DIR . 'includes/class-settings-repository.php';
require_once GATEWAY_DIR . 'includes/class-policy-resolver.php';
require_once GATEWAY_DIR . 'includes/class-event-bus.php';
require_once GATEWAY_DIR . 'includes/class-ip-hasher.php';
require_once GATEWAY_DIR . 'includes/class-visitor-id.php';
require_once GATEWAY_DIR . 'includes/class-token-repository.php';
require_once GATEWAY_DIR . 'includes/class-download-event-repository.php';
require_once GATEWAY_DIR . 'includes/interface-file-resolver.php';
require_once GATEWAY_DIR . 'includes/class-document-file-resolver.php';
require_once GATEWAY_DIR . 'includes/class-file-resolver-registry.php';
require_once GATEWAY_DIR . 'includes/class-download-controller.php';
require_once GATEWAY_DIR . 'includes/class-resource-metabox.php';
require_once GATEWAY_DIR . 'includes/class-download-shortcode.php';
require_once GATEWAY_DIR . 'includes/class-people-repository.php';
require_once GATEWAY_DIR . 'includes/class-gate-controller.php';
require_once GATEWAY_DIR . 'includes/class-retention-job.php';
require_once GATEWAY_DIR . 'includes/admin/class-settings-page.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\Activator::activate' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\Activator::deactivate' );

/**
 * Show an admin notice when GATEWAY_ENABLED is false so the site operator knows
 * the gateway is installed but not yet intercepting downloads.
 */
add_action(
	'admin_notices',
	function (): void {
		// @phpstan-ignore-next-line (runtime constant — value is overridden in wp-config.php)
		if ( ! GATEWAY_ENABLED ) {
			$screen = get_current_screen();
			if ( $screen && str_contains( $screen->id, 'download-gateway' ) ) {
				echo '<div class="notice notice-warning"><p>';
				echo '<strong>Download Gateway:</strong> ';
				echo 'The gateway is currently <strong>disabled</strong>. ';
				echo 'Add <code>define( \'GATEWAY_ENABLED\', true );</code> to <code>wp-config.php</code> to activate download interception.';
				echo '</p></div>';
			}
		}
	}
);

add_action( 'admin_menu', __NAMESPACE__ . '\Settings_Page::register' );

add_action(
	'rest_api_init',
	function (): void {
		// @phpstan-ignore-next-line (runtime constant — value is overridden in wp-config.php)
		if ( GATEWAY_ENABLED ) {
			( new DownloadController() )->register_routes();
			( new GateController() )->register_routes();
		}
	}
);

add_action(
	'wp_enqueue_scripts',
	function (): void {
		// @phpstan-ignore-next-line (runtime constant — value is overridden in wp-config.php)
		if ( ! GATEWAY_ENABLED ) {
			return;
		}
		// @phpstan-ignore-next-line (unreachable only in static analysis — runtime value differs)
		wp_enqueue_style(
			'gateway-modal',
			plugins_url( 'assets/css/gateway-modal.css', GATEWAY_FILE ),
			array(),
			GATEWAY_VERSION
		);
		wp_enqueue_script(
			'gateway-modal',
			plugins_url( 'assets/js/gateway-modal.js', GATEWAY_FILE ),
			array(),
			GATEWAY_VERSION,
			true
		);
		wp_localize_script(
			'gateway-modal',
			'gatewaySettings',
			array(
				'nonce'        => wp_create_nonce( 'gateway_gate' ),
				'restNonce'    => wp_create_nonce( 'wp_rest' ),
				'apiUrl'       => rest_url( GATEWAY_REST_NAMESPACE . '/gate' ),
				'downloadBase' => rest_url( GATEWAY_REST_NAMESPACE . '/download' ),
			)
		);
	}
);

// Register file resolvers for supported post types.
FileResolverRegistry::register( 'document_files', new DocumentFileResolver() );

add_action(
	RetentionJob::CRON_HOOK,
	function (): void {
		RetentionJob::anonymize();
	}
);

add_action( 'add_meta_boxes', __NAMESPACE__ . '\Resource_Metabox::register' );
add_action( 'save_post', __NAMESPACE__ . '\Resource_Metabox::save' );
Download_Shortcode::register();
