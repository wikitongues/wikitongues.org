<?php
/**
 * Plugin Name: Wikitongues Language Rights
 * Description: The Report on the Global State of Language Rights: exported from Airtable and Google Docs as tagged releases, imported into WordPress (plan 2.2, docs/language-rights.md).
 * Version: 0.1.0
 * Author: Wikitongues
 * Requires PHP: 8.2
 *
 * @package WT\Rights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WT_RIGHTS_VERSION', '0.1.0' );
define( 'WT_RIGHTS_DIR', plugin_dir_path( __FILE__ ) );

require_once WT_RIGHTS_DIR . 'includes/structure.php';
require_once WT_RIGHTS_DIR . 'includes/class-answers.php';
require_once WT_RIGHTS_DIR . 'includes/class-docs-adapter.php';
require_once WT_RIGHTS_DIR . 'includes/class-doc-parser.php';
require_once WT_RIGHTS_DIR . 'includes/class-validator.php';
require_once WT_RIGHTS_DIR . 'includes/class-versioner.php';
require_once WT_RIGHTS_DIR . 'includes/class-http.php';
require_once WT_RIGHTS_DIR . 'includes/class-google-docs.php';
require_once WT_RIGHTS_DIR . 'includes/class-airtable.php';
require_once WT_RIGHTS_DIR . 'includes/class-exporter.php';
require_once WT_RIGHTS_DIR . 'includes/class-release-status.php';
require_once WT_RIGHTS_DIR . 'includes/class-import-plan.php';
require_once WT_RIGHTS_DIR . 'includes/class-editorial.php';
require_once WT_RIGHTS_DIR . 'includes/class-importer.php';
require_once WT_RIGHTS_DIR . 'includes/content-model.php';
require_once WT_RIGHTS_DIR . 'includes/template-tags.php';
require_once WT_RIGHTS_DIR . 'includes/display.php';
require_once WT_RIGHTS_DIR . 'includes/press.php';
require_once WT_RIGHTS_DIR . 'includes/pdf.php';
require_once WT_RIGHTS_DIR . 'includes/analytics.php';
require_once WT_RIGHTS_DIR . 'includes/admin.php';

// The PDF download goes through the download gateway, when it is active.
add_action(
	'plugins_loaded',
	static function (): void {
		if ( interface_exists( '\\WT\\DownloadGateway\\FileResolver' ) && class_exists( '\\WT\\DownloadGateway\\FileResolverRegistry' ) ) {
			require_once WT_RIGHTS_DIR . 'includes/class-pdf-resolver.php';
			\WT\DownloadGateway\FileResolverRegistry::register( 'rights_reports', new \WT\Rights\Pdf_Resolver() );
		}
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once WT_RIGHTS_DIR . 'includes/cli.php';
}
