<?php
/**
 * WP-CLI commands for wt-maps.
 *
 * @package WT\Maps
 */

/**
 * Check the shape table against the places and languages in WordPress.
 *
 * Read-only. Lists published territories with no shape (they show no map),
 * table entries that match no place, entries whose territory is not
 * published, focus languages that don't exist, and problems in the file
 * itself. Run it after any territory change and before a maps release.
 *
 * Exits with an error when something needs fixing, so it can gate a script.
 *
 * ## EXAMPLES
 *
 *     wp wt-maps doctor
 *
 * @when after_wp_load
 */
function wt_maps_doctor_command(): void {
	global $wpdb;

	$table = wt_maps_shape_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one read-only report.
	$rows = $wpdb->get_results(
		"SELECT m.meta_value AS record_id, p.post_title AS title, p.post_type AS type, p.post_status AS status
		FROM {$wpdb->posts} p
		JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_airtable_record_id'
		WHERE p.post_type IN ('territories', 'subdivisions') AND p.post_status NOT IN ('trash', 'auto-draft')",
		ARRAY_A
	);

	$places = array();
	foreach ( (array) $rows as $row ) {
		$places[ (string) $row['record_id'] ] = array(
			'title'  => (string) $row['title'],
			'type'   => (string) $row['type'],
			'status' => (string) $row['status'],
		);
	}

	$languages = array();
	foreach ( $table->focus_language_ids() as $language_id ) {
		$ids = get_posts(
			array(
				'post_type'      => 'languages',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_airtable_record_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $language_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		if ( $ids ) {
			$languages[ $language_id ] = get_the_title( (int) $ids[0] );
		}
	}

	$report   = wt_maps_doctor( $table, $places, $languages );
	$headings = array(
		'table'       => 'Problems in data/shapes.json',
		'missing'     => 'Published territories with no shape (no map)',
		'unknown'     => 'Table entries that match no place',
		'unpublished' => 'Table entries whose territory is not published (informational)',
		'languages'   => 'Focus languages not found',
	);

	WP_CLI::log( sprintf( 'Shape table: %d places, %d focus languages, Natural Earth %s.', count( $table->place_ids() ), count( $table->focus_language_ids() ), $table->natural_earth_version() ) );

	foreach ( $headings as $key => $heading ) {
		WP_CLI::log( '' );
		WP_CLI::log( sprintf( '%s: %d', $heading, count( $report[ $key ] ) ) );
		foreach ( $report[ $key ] as $line ) {
			WP_CLI::log( '  ' . $line );
		}
	}

	WP_CLI::log( '' );
	if ( wt_maps_doctor_has_errors( $report ) ) {
		WP_CLI::error( 'The shape table needs attention.' );
	}
	WP_CLI::success( 'The shape table matches the places in WordPress.' );
}

WP_CLI::add_command( 'wt-maps doctor', 'wt_maps_doctor_command' );
