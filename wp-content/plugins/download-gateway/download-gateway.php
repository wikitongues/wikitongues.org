<?php
/**
 * Plugin Name:       Download Gateway
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

define( 'DG_VERSION', '0.1.0' );
define( 'DG_FILE', __FILE__ );
define( 'DG_DIR', plugin_dir_path( __FILE__ ) );
define( 'DG_REST_NAMESPACE', 'gateway/v1' );

/**
 * Feature flag. Set to true in wp-config.php to enable gateway functionality.
 * When false, the plugin registers its admin UI but does not intercept downloads.
 *
 *   define( 'DG_ENABLED', true );
 */
if ( ! defined( 'DG_ENABLED' ) ) {
	define( 'DG_ENABLED', false );
}

require_once DG_DIR . 'includes/class-logger.php';
require_once DG_DIR . 'includes/class-activator.php';
require_once DG_DIR . 'includes/admin/class-settings-page.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\Activator::activate' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\Activator::deactivate' );

/**
 * Show an admin notice when DG_ENABLED is false so the site operator knows
 * the gateway is installed but not yet intercepting downloads.
 */
add_action(
	'admin_notices',
	function (): void {
		if ( ! DG_ENABLED ) {
			$screen = get_current_screen();
			if ( $screen && str_contains( $screen->id, 'download-gateway' ) ) {
				echo '<div class="notice notice-warning"><p>';
				echo '<strong>Download Gateway:</strong> ';
				echo 'The gateway is currently <strong>disabled</strong>. ';
				echo 'Add <code>define( \'DG_ENABLED\', true );</code> to <code>wp-config.php</code> to activate download interception.';
				echo '</p></div>';
			}
		}
	}
);

add_action( 'admin_menu', __NAMESPACE__ . '\Settings_Page::register' );
