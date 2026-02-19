<?php
require_once __DIR__ . '/../vendor/autoload.php';

WP_Mock::bootstrap();

require_once __DIR__ . '/unit/FakeQuery.php';

require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/import-captions.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/acf-helpers.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/search-filter.php';
require_once __DIR__ . '/../wp-content/plugins/wt-gallery/includes/render_gallery_items.php';
require_once __DIR__ . '/../wp-content/plugins/wt-gallery/includes/queries.php';
require_once __DIR__ . '/../wp-content/plugins/wt-gallery/includes/helpers.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/template-helpers.php';

// events-filter.php calls get_current_datetime() → wp_date() at file scope,
// before WP_Mock has stubbed it. Define a shim so the include doesn't fatal.
if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( $format, $timestamp = null ) {
		return gmdate( $format, $timestamp ?? time() );
	}
}
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/events-filter.php';
