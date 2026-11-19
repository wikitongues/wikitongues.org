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

/**
 * Write the map sets the language pages need, for the build scripts.
 *
 * Read-only for the database. Groups every published language into the
 * distinct set of places its map draws (its focus, else its territory links,
 * as Airtable record IDs) and writes them with each place's shapes. The build
 * (`build/build.mjs`) reads this file; it is a build input, not committed.
 *
 * ## OPTIONS
 *
 * [--file=<path>]
 * : Where to write. Default: build/cache/sets.json in the plugin.
 *
 * ## EXAMPLES
 *
 *     wp wt-maps export
 *
 * @when after_wp_load
 *
 * @param list<string>          $args       Positional arguments (unused).
 * @param array<string, string> $assoc_args Named arguments.
 */
function wt_maps_export_command( array $args, array $assoc_args ): void {
	global $wpdb;

	$table = wt_maps_shape_table();
	$file  = $assoc_args['file'] ?? dirname( __DIR__ ) . '/build/cache/sets.json';

	// Published territories' post ID → record ID; languages link to post IDs.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one read-only export.
	$territory_rows = $wpdb->get_results(
		"SELECT p.ID AS id, m.meta_value AS record_id
		FROM {$wpdb->posts} p
		JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_airtable_record_id'
		WHERE p.post_type = 'territories' AND p.post_status = 'publish'",
		ARRAY_A
	);
	$record_of      = array();
	foreach ( (array) $territory_rows as $row ) {
		$record_of[ (int) $row['id'] ] = (string) $row['record_id'];
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one read-only export.
	$language_rows = $wpdb->get_results(
		"SELECT p.post_title AS title, r.meta_value AS record_id, t.meta_value AS territories
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} r ON r.post_id = p.ID AND r.meta_key = '_airtable_record_id'
		LEFT JOIN {$wpdb->postmeta} t ON t.post_id = p.ID AND t.meta_key = 'territories'
		WHERE p.post_type = 'languages' AND p.post_status = 'publish'",
		ARRAY_A
	);

	$languages = array();
	foreach ( (array) $language_rows as $row ) {
		$linked      = maybe_unserialize( (string) $row['territories'] );
		$territories = array();
		foreach ( is_array( $linked ) ? $linked : array() as $post_id ) {
			if ( isset( $record_of[ (int) $post_id ] ) ) {
				$territories[] = $record_of[ (int) $post_id ];
			}
		}
		$languages[] = array(
			'record_id'   => (string) $row['record_id'],
			'title'       => (string) $row['title'],
			'territories' => $territories,
		);
	}

	$plan   = wt_maps_plan_sets( $table, $languages );
	$places = array();
	foreach ( $plan['sets'] as $set ) {
		foreach ( $set['places'] as $place_id ) {
			$places[ $place_id ] = array(
				'name'   => $table->name( $place_id ),
				'shapes' => $table->shapes( $place_id ),
			);
		}
	}
	ksort( $places );

	$out = array(
		'natural_earth' => $table->natural_earth_version(),
		'places'        => $places,
		'sets'          => $plan['sets'],
	);

	if ( ! is_dir( dirname( $file ) ) ) {
		wp_mkdir_p( dirname( $file ) );
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WP-CLI writes a local build input.
	if ( false === file_put_contents( $file, wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" ) ) {
		WP_CLI::error( sprintf( 'Cannot write %s.', $file ) );
	}

	$mapped = array_sum( array_column( $plan['sets'], 'languages' ) );
	WP_CLI::log( sprintf( '%d languages mapped in %d sets (%d with several places); %d languages with no places; %d left unmapped.', $mapped, count( $plan['sets'] ), count( array_filter( $plan['sets'], fn( $s ) => count( $s['places'] ) > 1 ) ), $plan['no_places'], count( $plan['unmapped'] ) ) );
	foreach ( $plan['unmapped'] as $line ) {
		WP_CLI::log( '  ' . $line );
	}
	WP_CLI::success( sprintf( 'Wrote %s.', $file ) );
}

WP_CLI::add_command( 'wt-maps doctor', 'wt_maps_doctor_command' );
WP_CLI::add_command( 'wt-maps export', 'wt_maps_export_command' );
