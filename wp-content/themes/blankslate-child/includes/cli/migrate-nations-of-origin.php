<?php
/**
 * WP-CLI: convert `nations_of_origin` from comma-separated text to territory links.
 *
 * The field was free text ("South Korea, North Korea") until 2026-11; it is now
 * an ACF post_object field pointing at territories, and the Airtable sync
 * resolves nation names to territory IDs on every write. This command converts
 * the text already stored on each language.
 *
 * @package Wikitongues
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Stored names that differ from today's territory titles: old Airtable names
 * and WordPress renames that have not reached every language yet.
 *
 * @return array<string, string> Stored name => territory title.
 */
function wt_nations_of_origin_aliases(): array {
	return array(
		'Congo'                            => 'Republic of the Congo',
		'Macedonia'                        => 'North Macedonia',
		'Vatican State'                    => 'Vatican City',
		'Micronesia'                       => 'Federated States of Micronesia',
		'Sahrawi Arab Democratic Republic' => 'Western Sahara',
		'Cape Verde Islands'               => 'Cabo Verde',
		'Nauru'                            => 'Naoero',
		'Swaziland'                        => 'Eswatini',
	);
}

/**
 * Convert each language's `nations_of_origin` text to territory IDs.
 *
 * Idempotent: values that are already arrays are skipped. A language keeps its
 * text untouched if any of its names matches no territory, so nothing is lost;
 * the report lists those names to fix and re-run.
 *
 * ## OPTIONS
 *
 * [--execute]
 * : Write the changes. Without it the command only reports what it would do.
 *
 * ## EXAMPLES
 *
 *     wp wt migrate-nations-of-origin --allow-root
 *     wp wt migrate-nations-of-origin --execute --allow-root
 *
 * @param array<int,string>    $args       Positional arguments (unused).
 * @param array<string,string> $assoc_args Associative arguments.
 * @return void
 */
function wt_migrate_nations_of_origin( $args, $assoc_args ) {
	global $wpdb;
	$execute = isset( $assoc_args['execute'] );

	if ( ! function_exists( 'update_field' ) || ! function_exists( 'acf_get_field' ) ) {
		WP_CLI::error( 'ACF is not active.' );
	}
	$field = acf_get_field( 'nations_of_origin' );
	if ( ! $field || 'post_object' !== $field['type'] ) {
		WP_CLI::error( 'Field `nations_of_origin` is not a post_object field yet. Sync the Languages field group first (ACF → Field Groups → Sync).' );
	}

	$aliases = wt_nations_of_origin_aliases();
	$cache   = array();
	$resolve = function ( string $title ) use ( $aliases, &$cache ): int {
		$title = $aliases[ $title ] ?? $title;
		if ( ! array_key_exists( $title, $cache ) ) {
			$ids             = wt_territory_ids_from_titles( array( $title ) );
			$cache[ $title ] = $ids ? $ids[0] : 0;
		}
		return $cache[ $title ];
	};

	$rows = $wpdb->get_results(
		"SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm
		JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE pm.meta_key = 'nations_of_origin' AND p.post_type = 'languages'"
	);

	$converted = 0;
	$cleared   = 0;
	$already   = 0;
	$kept      = array();
	foreach ( $rows as $row ) {
		if ( is_serialized( $row->meta_value ) ) {
			++$already;
			continue;
		}
		$titles = wt_split_origin_titles( (string) $row->meta_value );
		$ids    = array();
		$miss   = array();
		foreach ( $titles as $title ) {
			$id = $resolve( $title );
			if ( $id ) {
				$ids[] = $id;
			} else {
				$miss[] = $title;
			}
		}
		if ( $miss ) {
			$kept[ (int) $row->post_id ] = $miss;
			continue;
		}
		$ids ? ++$converted : ++$cleared;
		if ( $execute ) {
			update_field( 'nations_of_origin', array_values( array_unique( $ids ) ), (int) $row->post_id );
		}
	}

	$prefix = $execute ? '' : '[dry run] ';
	WP_CLI::log( "{$prefix}Converted {$converted} languages, emptied {$cleared} blank values, {$already} already converted." );
	if ( $kept ) {
		$names = array_count_values( array_merge( ...array_values( $kept ) ) );
		arsort( $names );
		WP_CLI::warning( count( $kept ) . ' languages left as text; names with no territory:' );
		foreach ( $names as $name => $count ) {
			WP_CLI::log( "  {$count}\t{$name}" );
		}
	}
	if ( $execute ) {
		WP_CLI::success( 'Done.' );
	}
}

WP_CLI::add_command( 'wt migrate-nations-of-origin', 'wt_migrate_nations_of_origin' );
