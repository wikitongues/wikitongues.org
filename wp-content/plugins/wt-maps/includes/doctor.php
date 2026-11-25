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
 * `unpublished`, `unused` and `claims_noop` are informational: a draft territory may wait for
 * its region, and a map no language uses does no harm until the next rebuild.
 *
 * @param array<string, list<string>> $report From wt_maps_doctor().
 * @return bool
 */
function wt_maps_doctor_has_errors( array $report ): bool {
	foreach ( array( 'table', 'missing', 'unknown', 'languages', 'unbuilt', 'claims' ) as $key ) {
		if ( ! empty( $report[ $key ] ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Compare the map sets languages use with the maps that have been built.
 *
 * @param array<string, array{places: list<string>, languages: int, pages?: list<string>}> $sets  From wt_maps_plan_sets().
 * @param list<string>                                               $built Keys of the built SVGs.
 * @return array{unbuilt: list<string>, unused: list<string>}
 */
function wt_maps_build_report( array $sets, array $built ): array {
	$unbuilt = array();
	foreach ( array_diff_key( $sets, array_flip( $built ) ) as $key => $set ) {
		$users = array();
		if ( $set['languages'] ) {
			$users[] = sprintf( '%d %s', $set['languages'], 1 === $set['languages'] ? 'language' : 'languages' );
		}
		$users     = array_merge( $users, $set['pages'] ?? array() );
		$unbuilt[] = sprintf( '%s (%s)', $key, implode( ', ', $users ) );
	}
	$unused = array_values( array_diff( $built, array_keys( $sets ) ) );

	sort( $unbuilt );
	sort( $unused );
	return array(
		'unbuilt' => $unbuilt,
		'unused'  => $unused,
	);
}

/**
 * Check the claims table (data/claims.json) against the shape table.
 *
 * Replays the layers as the build does (build/claims-table.mjs): layer 1's
 * rows, then each review's changes in order. The build itself stops on a
 * contested area with no row; this checks what it cannot see.
 *
 * @param array<string, mixed> $claims The decoded claims table.
 * @param Shape_Table          $table  The shape table.
 * @return array{claims: list<string>, claims_noop: list<string>}
 *   claims: errors. A claimant that is not in the shape table, a review change
 *   with no reason, or one naming an area layer 1 does not have.
 *   claims_noop: review changes that change nothing (informational): after a
 *   Natural Earth upgrade, an override that layer 1 now agrees with.
 */
function wt_maps_claims_report( array $claims, Shape_Table $table ): array {
	$errors = array();
	$noops  = array();
	$known  = array_flip( $table->place_ids() );
	$layers = isset( $claims['layers'] ) && is_array( $claims['layers'] ) ? array_values( $claims['layers'] ) : array();

	$areas = array();
	foreach ( (array) ( $layers[0]['rows'] ?? array() ) as $code => $row ) {
		$areas[ (string) $code ] = array_flip( (array) ( $row['claimants'] ?? array() ) );
	}

	$check = function ( string $where, array $ids ) use ( $known, &$errors ): void {
		foreach ( $ids as $id ) {
			if ( ! isset( $known[ $id ] ) ) {
				$errors[] = sprintf( '%s: claimant %s is not in the shape table', $where, $id );
			}
		}
	};

	foreach ( $areas as $code => $ids ) {
		$check( 'natural-earth: ' . $code, array_keys( $ids ) );
	}

	foreach ( array_slice( $layers, 1 ) as $layer ) {
		$layer_id = (string) ( $layer['id'] ?? '?' );
		foreach ( (array) ( $layer['changes'] ?? array() ) as $change ) {
			$code  = (string) ( $change['area'] ?? '' );
			$where = $layer_id . ': ' . $code;
			if ( ! isset( $areas[ $code ] ) ) {
				$errors[] = $where . ' is not in layer 1';
				continue;
			}
			if ( empty( $change['reason'] ) ) {
				$errors[] = $where . ' has no reason';
			}
			$add  = array_map( 'strval', (array) ( $change['add'] ?? array() ) );
			$drop = array_map( 'strval', (array) ( $change['drop'] ?? array() ) );
			$check( $where, array_merge( $add, $drop ) );

			$changed = isset( $change['status'] );
			foreach ( $add as $id ) {
				if ( ! isset( $areas[ $code ][ $id ] ) ) {
					$areas[ $code ][ $id ] = true;
					$changed               = true;
				}
			}
			foreach ( $drop as $id ) {
				if ( isset( $areas[ $code ][ $id ] ) ) {
					unset( $areas[ $code ][ $id ] );
					$changed = true;
				}
			}
			if ( ! $changed ) {
				$noops[] = $where . ' changes nothing';
			}
		}
	}

	sort( $errors );
	return array(
		'claims'      => $errors,
		'claims_noop' => $noops,
	);
}
