<?php
/**
 * The `doctor` report: does the shape table agree with the places in WordPress?
 *
 * Pure, so it is unit-tested; the WP-CLI command in cli.php gathers the posts.
 *
 * @package WT\Maps
 */

use WT\Maps\Shape_Table;

/**
 * Compare the shape table with the place and language posts.
 *
 * @param Shape_Table                                                      $table     The shape table.
 * @param array<string, array{title: string, type: string, status: string}> $places    Place record ID → its post (territories and subdivisions, any status).
 * @param array<string, string>                                            $languages Language record ID → title, for the focus languages found.
 * @return array{table: list<string>, missing: list<string>, unknown: list<string>, unpublished: list<string>, languages: list<string>}
 *   table: problems in the file itself.
 *   missing: published territories with no shape, so no map.
 *   unknown: table entries that match no place post.
 *   unpublished: table entries whose place is not a published page, so no language reaches them through a territory link.
 *   languages: focus entries that match no language post.
 */
function wt_maps_doctor( Shape_Table $table, array $places, array $languages ): array {
	$report = array(
		'table'       => $table->problems(),
		'missing'     => array(),
		'unknown'     => array(),
		'unpublished' => array(),
		'languages'   => array(),
	);

	foreach ( $places as $record_id => $place ) {
		if ( 'territories' === $place['type'] && 'publish' === $place['status'] && array() === $table->shapes( (string) $record_id ) ) {
			$report['missing'][] = sprintf( '%s (%s)', $place['title'], $record_id );
		}
	}

	foreach ( $table->place_ids() as $record_id ) {
		$label = sprintf( '%s (%s)', $table->name( $record_id ), $record_id );
		if ( ! isset( $places[ $record_id ] ) ) {
			$report['unknown'][] = $label;
		} elseif ( 'territories' === $places[ $record_id ]['type'] && 'publish' !== $places[ $record_id ]['status'] ) {
			$report['unpublished'][] = sprintf( '%s: %s', $label, $places[ $record_id ]['status'] );
		}
	}

	foreach ( $table->focus_language_ids() as $language_id ) {
		if ( ! isset( $languages[ $language_id ] ) ) {
			$report['languages'][] = $language_id;
		}
	}

	foreach ( $report as $key => $lines ) {
		sort( $lines );
		$report[ $key ] = $lines;
	}

	return $report;
}

/**
 * Whether a doctor report has anything a person must fix.
 *
 * `unpublished` and `unused` are informational: a draft territory may wait for
 * its region, and a map no language uses does no harm until the next rebuild.
 *
 * @param array<string, list<string>> $report From wt_maps_doctor().
 * @return bool
 */
function wt_maps_doctor_has_errors( array $report ): bool {
	foreach ( array( 'table', 'missing', 'unknown', 'languages', 'unbuilt' ) as $key ) {
		if ( ! empty( $report[ $key ] ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Compare the map sets languages use with the maps that have been built.
 *
 * @param array<string, array{places: list<string>, languages: int}> $sets  From wt_maps_plan_sets().
 * @param list<string>                                               $built Keys of the built SVGs.
 * @return array{unbuilt: list<string>, unused: list<string>}
 */
function wt_maps_build_report( array $sets, array $built ): array {
	$unbuilt = array();
	foreach ( array_diff_key( $sets, array_flip( $built ) ) as $key => $set ) {
		$unbuilt[] = sprintf( '%s (%d %s)', $key, $set['languages'], 1 === $set['languages'] ? 'language' : 'languages' );
	}
	$unused = array_values( array_diff( $built, array_keys( $sets ) ) );

	sort( $unbuilt );
	sort( $unused );
	return array(
		'unbuilt' => $unbuilt,
		'unused'  => $unused,
	);
}
