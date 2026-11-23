<?php
/**
 * Plugin Name: Wikitongues Maps
 * Description: Locator maps for language pages, drawn ahead of time from Natural Earth (plan 3.2).
 * Version: 0.1.0
 * Author: Wikitongues
 * Requires PHP: 8.2
 *
 * @package WT\Maps
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WT_MAPS_VERSION', '0.1.0' );
define( 'WT_MAPS_DIR', plugin_dir_path( __FILE__ ) );

require_once WT_MAPS_DIR . 'includes/class-shape-table.php';
require_once WT_MAPS_DIR . 'includes/doctor.php';
require_once WT_MAPS_DIR . 'includes/sets.php';
require_once WT_MAPS_DIR . 'includes/class-renderer.php';
require_once WT_MAPS_DIR . 'includes/api.php';
require_once WT_MAPS_DIR . 'includes/settings.php';

/**
 * The shape table, read once per request.
 *
 * @return \WT\Maps\Shape_Table
 */
function wt_maps_shape_table(): \WT\Maps\Shape_Table {
	static $table = null;
	if ( null === $table ) {
		$table = \WT\Maps\Shape_Table::from_file( WT_MAPS_DIR . 'data/shapes.json' );
	}
	return $table;
}

/**
 * Natural Earth shape references for a place post (territory or subdivision).
 *
 * Looked up by the post's Airtable record ID, never by title.
 *
 * @param int $post_id A `territories` or `subdivisions` post.
 * @return list<string> References such as `units:GAZ`; empty when the place has no map.
 */
function wt_map_shapes( int $post_id ): array {
	$record_id = (string) get_post_meta( $post_id, '_airtable_record_id', true );
	return '' === $record_id ? array() : wt_maps_shape_table()->shapes( $record_id );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once WT_MAPS_DIR . 'includes/cli.php';
}
