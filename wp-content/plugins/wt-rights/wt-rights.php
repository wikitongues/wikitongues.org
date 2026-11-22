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

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once WT_RIGHTS_DIR . 'includes/cli.php';
}
