<?php
/**
 * WP-CLI: bulk-load Airtable Territories records as places.
 *
 * The first load of Airtable's Territories table (about 850 records) is too
 * big for Make, which posts one record at a time on a schedule. This command
 * reads an export and runs every row through the same code as the sync
 * endpoint (`WT\AirtableSync\Place_Sync`), so the bulk load and Make's later
 * updates write exactly the same thing.
 *
 * @package Wikitongues
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Sync Airtable Territories records into `territories` and `subdivisions` posts.
 *
 * Each row becomes a `territories` post when it has an ISO 3166-1 code and a
 * `subdivisions` post otherwise, keyed on its Airtable record ID. Rows that
 * already match WordPress are skipped, so a second run changes nothing.
 * Dry run by default.
 *
 * ## OPTIONS
 *
 * --file=<path>
 * : CSV with a header row: airtable_id, post_title, iso_3166_code, nation.
 *   `nation` holds the Nations record IDs, comma-separated (quote the cell).
 *
 * [--execute]
 * : Write the changes. Without it the command only reports what it would do.
 *
 * ## EXAMPLES
 *
 *     wp wt sync-territories --file=territories.csv --allow-root
 *     wp wt sync-territories --file=territories.csv --execute --allow-root
 *
 * @param array<int,string>    $args       Positional arguments (unused).
 * @param array<string,string> $assoc_args Associative arguments.
 * @return void
 */
function wt_sync_territories( $args, $assoc_args ) {
	global $wpdb;
	$execute = isset( $assoc_args['execute'] );
	$file    = $assoc_args['file'] ?? '';

	if ( ! class_exists( 'WT\AirtableSync\Place_Sync' ) ) {
		WP_CLI::error( 'The wt-airtable-sync plugin is not active.' );
	}
	if ( ! is_readable( $file ) ) {
		WP_CLI::error( "Cannot read {$file}." );
	}

	// Read the CSV into rows keyed by header.
	$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	$header = fgetcsv( $handle, 0, ',', '"', '' );
	$header = array_map( static fn( $h ) => trim( (string) $h, "\xEF\xBB\xBF \t" ), (array) $header );
	foreach ( array( 'airtable_id', 'post_title', 'iso_3166_code', 'nation' ) as $column ) {
		if ( ! in_array( $column, $header, true ) ) {
			WP_CLI::error( "The CSV has no {$column} column." );
		}
	}
	$raw = array();
	while ( false !== ( $line = fgetcsv( $handle, 0, ',', '"', '' ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		if ( array( null ) === $line ) {
			continue;
		}
		$raw[] = array_combine( $header, array_pad( $line, count( $header ), '' ) );
	}
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

	$parsed = wt_territory_rows( $raw );
	foreach ( $parsed['invalid'] as $problem ) {
		WP_CLI::warning( $problem );
	}

	// What WordPress holds now: every place with its record ID, ISO code and nation.
	$current = array();
	$known   = array();
	$titles  = array(
		'territories'  => array(),
		'subdivisions' => array(),
	);
	$posts   = $wpdb->get_results(
		"SELECT p.ID, p.post_type, p.post_title,
			COALESCE(rid.meta_value, '') AS record_id,
			COALESCE(iso.meta_value, '') AS iso,
			COALESCE(nrid.meta_value, '') AS nation
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} rid ON rid.post_id = p.ID AND rid.meta_key = '_airtable_record_id'
		LEFT JOIN {$wpdb->postmeta} iso ON iso.post_id = p.ID AND iso.meta_key = 'iso_3166_code'
		LEFT JOIN {$wpdb->postmeta} nat ON nat.post_id = p.ID AND nat.meta_key = 'nation'
		LEFT JOIN {$wpdb->postmeta} nrid ON nrid.post_id = nat.meta_value AND nrid.meta_key = '_airtable_record_id'
		WHERE p.post_type IN ('territories', 'subdivisions')
		AND p.post_status NOT IN ('trash', 'auto-draft', 'inherit')"
	);
	foreach ( $posts as $post ) {
		if ( '' !== $post->record_id ) {
			$known[ $post->record_id ] = true;
		}
		if ( '' === $post->record_id ) {
			$titles[ $post->post_type ][ $post->post_title ] = ( $titles[ $post->post_type ][ $post->post_title ] ?? 0 ) + 1;
			continue;
		}
		$current[ $post->record_id ] = array(
			'type'   => $post->post_type,
			'title'  => $post->post_title,
			'iso'    => (string) $post->iso,
			'nation' => (string) $post->nation,
		);
	}

	$plan = wt_plan_territory_sync( $parsed['rows'], $current, $titles, $known );

	foreach ( array( 'create', 'stamp', 'update', 'type_change' ) as $kind ) {
		foreach ( $plan[ $kind ] as $line ) {
			WP_CLI::log( sprintf( '%s %s: %s', $execute ? 'Doing' : 'Would', str_replace( '_', ' ', $kind ), $line ) );
		}
	}
	foreach ( $plan['ambiguous'] as $line ) {
		WP_CLI::warning( "ambiguous title, skipped: {$line}" );
	}

	$todo = array_flip( $plan['todo'] );
	$done = 0;
	$fail = 0;

	if ( $execute ) {
		$maps = require WP_PLUGIN_DIR . '/wt-airtable-sync/config/field-maps.php';
		$sync = new WT\AirtableSync\Place_Sync();

		foreach ( $parsed['rows'] as $row ) {
			if ( ! isset( $todo[ $row['airtable_id'] ] ) ) {
				continue;
			}

			$result = $sync->sync( $row, $maps['territories'] );
			if ( is_wp_error( $result ) ) {
				++$fail;
				WP_CLI::warning( sprintf( '%s: %s', $row['airtable_id'], $result->get_error_message() ) );
			} else {
				++$done;
			}
		}
	}

	WP_CLI::success(
		sprintf(
			'%d to create, %d to stamp, %d to update, %d to change type, %d unchanged, %d ambiguous, %d invalid.%s',
			count( $plan['create'] ),
			count( $plan['stamp'] ),
			count( $plan['update'] ),
			count( $plan['type_change'] ),
			$plan['unchanged'],
			count( $plan['ambiguous'] ),
			count( $parsed['invalid'] ),
			$execute ? sprintf( ' Wrote %d of %d (%d failed).', $done, count( $todo ), $fail ) : ' Dry run: nothing written.'
		)
	);
}

WP_CLI::add_command( 'wt sync-territories', 'wt_sync_territories' );
