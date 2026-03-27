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

define( 'GATEWAY_VERSION', '0.1.11' );
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
require_once GATEWAY_DIR . 'includes/class-dropbox-adapter.php';
require_once GATEWAY_DIR . 'includes/class-video-file-resolver.php';
require_once GATEWAY_DIR . 'includes/class-caption-file-resolver.php';
require_once GATEWAY_DIR . 'includes/class-file-resolver-registry.php';
require_once GATEWAY_DIR . 'includes/class-download-controller.php';
require_once GATEWAY_DIR . 'includes/class-resource-metabox.php';
require_once GATEWAY_DIR . 'includes/class-download-shortcode.php';
require_once GATEWAY_DIR . 'includes/class-people-repository.php';
require_once GATEWAY_DIR . 'includes/class-person-cookie.php';
require_once GATEWAY_DIR . 'includes/class-gate-controller.php';
require_once GATEWAY_DIR . 'includes/class-intake-repository.php';
require_once GATEWAY_DIR . 'includes/class-intake-controller.php';
require_once GATEWAY_DIR . 'includes/class-intake-resolver.php';
require_once GATEWAY_DIR . 'includes/class-retention-job.php';
require_once GATEWAY_DIR . 'includes/class-webhook-dispatcher.php';
require_once GATEWAY_DIR . 'includes/admin/class-settings-page.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\Activator::activate' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\Activator::deactivate' );


add_action( 'admin_menu', __NAMESPACE__ . '\Settings_Page::register' );
add_action( 'admin_init', __NAMESPACE__ . '\Settings_Page::handle_run_now_action' );

/*
 * Auto-upgrade the DB schema when the plugin is updated.
 * create_tables() uses dbDelta() so it is safe to run on every load when
 * the version check passes — it only adds missing tables/columns.
 */
add_action(
	'plugins_loaded',
	function (): void {
		$installed = (int) get_option( Schema::VERSION_OPTION, 0 );
		if ( $installed < Schema::SCHEMA_VERSION ) {
			Schema::create_tables();
		}
	}
);

add_action(
	'rest_api_init',
	function (): void {
		// @phpstan-ignore-next-line (runtime constant — value is overridden in wp-config.php)
		if ( GATEWAY_ENABLED ) {
			( new DownloadController() )->register_routes();
			( new GateController() )->register_routes();
			( new IntakeController() )->register_routes();
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
		/**
		 * Filters named intake field set definitions for the download modal step 2.
		 *
		 * Return an array keyed by set name. Each value is an array of field
		 * definition arrays with keys:
		 *   key      — machine name (used as the response key in the DB)
		 *   label    — human-readable label
		 *   type     — text | textarea | select | radio | checkbox
		 *   options  — assoc array of value => label (select / radio only)
		 *   required — bool (currently informational; JS does not enforce)
		 *
		 * Example:
		 *   add_filter( 'gateway_intake_fields', function( $sets ) {
		 *       $sets['standard'] = array(
		 *           array( 'key' => 'use_case', 'label' => 'How will you use this?',
		 *                  'type' => 'select', 'options' => array( 'research' => 'Research' ) ),
		 *       );
		 *       return $sets;
		 *   } );
		 *
		 * @param array<string,array<int,array<string,mixed>>> $sets Empty by default.
		 */
		wp_localize_script(
			'gateway-modal',
			'gatewaySettings',
			array(
				'nonce'        => wp_create_nonce( 'gateway_gate' ),
				'restNonce'    => wp_create_nonce( 'wp_rest' ),
				'apiUrl'       => rest_url( GATEWAY_REST_NAMESPACE . '/gate' ),
				'downloadBase' => rest_url( GATEWAY_REST_NAMESPACE . '/download' ),
				'intakeUrl'    => rest_url( GATEWAY_REST_NAMESPACE . '/intake' ),
				'intakeSets'   => (array) apply_filters( 'gateway_intake_fields', array() ),
			)
		);
	}
);

// Register file resolvers for supported post types.
FileResolverRegistry::register( 'document_files', new DocumentFileResolver() );
FileResolverRegistry::register( 'videos', new VideoFileResolver() );
FileResolverRegistry::register( 'captions', new CaptionFileResolver() );

add_action(
	RetentionJob::CRON_HOOK,
	function (): void {
		RetentionJob::anonymize();
	}
);

add_filter(
	'cron_schedules',
	function ( array $schedules ): array {
		if ( ! isset( $schedules['every_5_minutes'] ) ) {
			$schedules['every_5_minutes'] = array(
				'interval' => 300,
				'display'  => 'Every 5 minutes',
			);
		}
		return $schedules;
	}
);

add_action(
	WebhookDispatcher::CRON_HOOK,
	function (): void {
		WebhookDispatcher::dispatch_pending();
	}
);

add_action( 'add_meta_boxes', __NAMESPACE__ . '\Resource_Metabox::register' );
add_action( 'save_post', __NAMESPACE__ . '\Resource_Metabox::save' );
Download_Shortcode::register();
