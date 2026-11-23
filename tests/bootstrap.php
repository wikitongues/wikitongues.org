<?php
require_once __DIR__ . '/../vendor/autoload.php';

WP_Mock::bootstrap();

// Gateway plugin constants — required before any gateway class is included.
if ( ! defined( 'NONCE_KEY' ) ) {
	define( 'NONCE_KEY', 'test-nonce-key-for-phpunit-do-not-use-in-production' );
}
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
require_once GATEWAY_DIR . 'includes/class-person-cookie.php';
require_once GATEWAY_DIR . 'includes/class-gate-controller.php';
require_once GATEWAY_DIR . 'includes/class-intake-repository.php';
require_once GATEWAY_DIR . 'includes/class-intake-controller.php';
require_once GATEWAY_DIR . 'includes/class-intake-resolver.php';
require_once GATEWAY_DIR . 'includes/class-retention-job.php';
require_once GATEWAY_DIR . 'includes/class-webhook-dispatcher.php';
require_once GATEWAY_DIR . 'includes/class-dropbox-adapter.php';
require_once GATEWAY_DIR . 'includes/class-video-file-resolver.php';
require_once GATEWAY_DIR . 'includes/class-caption-file-resolver.php';

// WordPress time constants used by DropboxAdapter transient TTLs.
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/integrations/acf-helpers.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/integrations/resource-card-abilities.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/template/search-filter.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/template/short-links.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/template/language-origins.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/template/place-maps.php';
require_once __DIR__ . '/../wp-content/plugins/wt-gallery/includes/render_gallery_items.php';
require_once __DIR__ . '/../wp-content/plugins/wt-gallery/includes/queries.php';
require_once __DIR__ . '/../wp-content/plugins/wt-gallery/includes/helpers.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/template/template-helpers.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/template/language-blurb.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/template/donate-content.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/taxonomies/form-990.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/cli/content-bundle-helpers.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/cli/stamp-airtable-ids-helpers.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/cli/sync-territories-helpers.php';
require_once __DIR__ . '/../wp-content/plugins/wt-airtable-sync/includes/class-logger.php';
require_once __DIR__ . '/../wp-content/plugins/wt-airtable-sync/includes/class-field-resolver.php';
require_once __DIR__ . '/../wp-content/plugins/wt-airtable-sync/includes/class-sync-controller.php';
require_once __DIR__ . '/../wp-content/plugins/wt-airtable-sync/includes/class-place-sync.php';
require_once __DIR__ . '/../wp-content/plugins/wt-maps/includes/class-shape-table.php';
require_once __DIR__ . '/../wp-content/plugins/wt-maps/includes/doctor.php';
require_once __DIR__ . '/../wp-content/plugins/wt-maps/includes/sets.php';
require_once __DIR__ . '/../wp-content/plugins/wt-maps/includes/class-renderer.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/structure.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-answers.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-docs-adapter.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-doc-parser.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-validator.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-versioner.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-http.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-google-docs.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-airtable.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-exporter.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-release-status.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-import-plan.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-editorial.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/content-model.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/template-tags.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/display.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/press.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/pdf.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/analytics.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/admin.php';
require_once __DIR__ . '/../wp-content/plugins/wt-rights/includes/class-pdf-resolver.php';
require_once __DIR__ . '/unit/rights/RightsFixture.php';

// WordPress's HTML API, for tests of code that uses WP_HTML_Tag_Processor. Core
// is not in the repository, so this loads only where a local install sits at
// the repository root; those tests skip elsewhere (CI).
if ( file_exists( __DIR__ . '/../wp-includes/html-api/class-wp-html-tag-processor.php' ) && ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
	require_once __DIR__ . '/../wp-includes/class-wp-token-map.php';
	require_once __DIR__ . '/../wp-includes/html-api/html5-named-character-references.php';
	foreach ( array( 'class-wp-html-attribute-token', 'class-wp-html-span', 'class-wp-html-text-replacement', 'class-wp-html-decoder', 'class-wp-html-tag-processor' ) as $wt_html_api_file ) {
		require_once __DIR__ . "/../wp-includes/html-api/{$wt_html_api_file}.php";
	}
}

// events-filter.php calls get_current_datetime() → wp_date() at file scope,
// before WP_Mock has stubbed it. Define a shim so the include doesn't fatal.
if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( $format, $timestamp = null ) {
		return gmdate( $format, $timestamp ?? time() );
	}
}
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/integrations/events-filter.php';
