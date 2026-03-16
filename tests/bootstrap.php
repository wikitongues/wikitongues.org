<?php
require_once __DIR__ . '/../vendor/autoload.php';

WP_Mock::bootstrap();

// Gateway plugin constants — required before any gateway class is included.
if ( ! defined( 'GATEWAY_VERSION' ) ) {
	define( 'GATEWAY_VERSION', '0.1.0' );
}
if ( ! defined( 'GATEWAY_DIR' ) ) {
	define( 'GATEWAY_DIR', __DIR__ . '/../wp-content/plugins/download-gateway/' );
}
if ( ! defined( 'GATEWAY_FILE' ) ) {
	define( 'GATEWAY_FILE', GATEWAY_DIR . 'download-gateway.php' );
}
if ( ! defined( 'GATEWAY_REST_NAMESPACE' ) ) {
	define( 'GATEWAY_REST_NAMESPACE', 'gateway/v1' );
}
if ( ! defined( 'GATEWAY_ENABLED' ) ) {
	define( 'GATEWAY_ENABLED', false );
}

require_once __DIR__ . '/unit/FakeQuery.php';
require_once __DIR__ . '/unit/WP_Error.php';

// Gateway plugin classes.
require_once GATEWAY_DIR . 'includes/class-logger.php';
require_once GATEWAY_DIR . 'includes/class-ip-hasher.php';
require_once GATEWAY_DIR . 'includes/class-token-repository.php';
require_once GATEWAY_DIR . 'includes/interface-file-resolver.php';
require_once GATEWAY_DIR . 'includes/class-document-file-resolver.php';
require_once GATEWAY_DIR . 'includes/class-file-resolver-registry.php';
require_once GATEWAY_DIR . 'includes/class-visitor-id.php';
require_once GATEWAY_DIR . 'includes/class-settings-repository.php';
require_once GATEWAY_DIR . 'includes/class-policy-resolver.php';
require_once GATEWAY_DIR . 'includes/class-event-bus.php';
require_once GATEWAY_DIR . 'includes/class-download-event-repository.php';
require_once GATEWAY_DIR . 'includes/class-download-controller.php';
require_once GATEWAY_DIR . 'includes/class-people-repository.php';
require_once GATEWAY_DIR . 'includes/class-gate-controller.php';
require_once GATEWAY_DIR . 'includes/class-retention-job.php';

require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/integrations/acf-helpers.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/template/search-filter.php';
require_once __DIR__ . '/../wp-content/plugins/wt-gallery/includes/render_gallery_items.php';
require_once __DIR__ . '/../wp-content/plugins/wt-gallery/includes/queries.php';
require_once __DIR__ . '/../wp-content/plugins/wt-gallery/includes/helpers.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/template/template-helpers.php';

// events-filter.php calls get_current_datetime() → wp_date() at file scope,
// before WP_Mock has stubbed it. Define a shim so the include doesn't fatal.
if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( $format, $timestamp = null ) {
		return gmdate( $format, $timestamp ?? time() );
	}
}
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/integrations/events-filter.php';
