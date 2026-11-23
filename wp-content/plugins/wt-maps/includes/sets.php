<?php
/**
 * Map sets: which places each language's map draws, and the file key for them.
 *
 * A map is built once per distinct set of places, not once per language, and
 * PHP picks the file by the same key at render time. Sets are made of Airtable
 * record IDs, which are the same on every environment; post IDs are not (the
 * sync creates places separately on each one), so they never reach a file name.
 *
 * Pure, so it is unit-tested; `wp wt-maps export` gathers the posts.
 *
 * @package WT\Maps
 */

use WT\Maps\Shape_Table;

/**
 * File key for a set of places.
 *
 * One place: its record ID (`recguDPgV3cIXLO34`), so the common case is
 * readable in a file listing. Several: `set-` and a hash of the sorted IDs,
 * so the key doesn't grow with the set and doesn't depend on link order.
 *
 * @param list<string> $place_ids Place record IDs.
 * @return string Empty for an empty set.
 */
function wt_maps_set_key( array $place_ids ): string {
	$place_ids = array_values( array_unique( $place_ids ) );
	sort( $place_ids );

	if ( array() === $place_ids ) {
		return '';
	}
	if ( 1 === count( $place_ids ) ) {
		return $place_ids[0];
	}
	return 'set-' . substr( sha1( implode( ',', $place_ids ) ), 0, 12 );
}

/**
 * Places a language's map draws: its focus when it has one, else its
 * territories, else its nations of origin (Eteocypriot: no territories, origin
 * Cyprus).
 *
 * The origins fallback is temporary (plan 6.8): once the sync writes territories
 * as presence plus origins, every origin is a territory and it never applies.
 * Remove it then; a rebuild and `wp wt-maps doctor` should show no change.
 *
 * @param Shape_Table  $table              The shape table.
 * @param string       $language_record_id The language's record ID ('' if none).
 * @param list<string> $territory_ids      Record IDs of its published territory links.
 * @param list<string> $origin_ids         Record IDs of its published nations of origin.
 * @return list<string> Sorted record IDs.
 */
function wt_maps_language_places( Shape_Table $table, string $language_record_id, array $territory_ids, array $origin_ids = array() ): array {
	$places = '' !== $language_record_id ? $table->focus( $language_record_id ) : array();
	if ( array() === $places ) {
		$places = array() !== $territory_ids ? $territory_ids : $origin_ids;
	}

	$places = array_values( array_unique( array_filter( $places, 'is_string' ) ) );
	sort( $places );
	return $places;
}

/**
 * Group languages into the distinct map sets to build.
 *
 * A language whose places include one with no shape gets no map, never a
 * partial one, and is listed under `unmapped`.
 *
 * @param Shape_Table                                                      $table     The shape table.
 * @param list<array{record_id: string, title: string, territories: list<string>, origins?: list<string>}> $languages Published languages; `territories` and `origins` are record IDs.
 * @return array{sets: array<string, array{places: list<string>, languages: int}>, unmapped: list<string>, no_places: int}
 */
function wt_maps_plan_sets( Shape_Table $table, array $languages ): array {
	$sets      = array();
	$unmapped  = array();
	$no_places = 0;

	foreach ( $languages as $language ) {
		$places = wt_maps_language_places( $table, $language['record_id'], $language['territories'], $language['origins'] ?? array() );
		if ( array() === $places ) {
			++$no_places;
			continue;
		}

		$missing = array_filter( $places, fn( $id ) => array() === $table->shapes( $id ) );
		if ( array() !== $missing ) {
			$unmapped[] = sprintf( '%s: no shape for %s', $language['title'], implode( ', ', $missing ) );
			continue;
		}

		$key = wt_maps_set_key( $places );
		if ( ! isset( $sets[ $key ] ) ) {
			$sets[ $key ] = array(
				'places'    => $places,
				'languages' => 0,
			);
		}
		++$sets[ $key ]['languages'];
	}

	ksort( $sets );
	sort( $unmapped );

	return array(
		'sets'      => $sets,
		'unmapped'  => $unmapped,
		'no_places' => $no_places,
	);
}

/**
 * Add map sets that pages other than languages draw, such as a Language Rights
 * report region, to a plan from wt_maps_plan_sets().
 *
 * Each entry is a label and its places: a list of record IDs, or
 * `array{places: list<string>, globe?: bool}` to ask for the locator globe
 * whatever the frame's size (region maps; a language's map shows it only on
 * small frames). A set with a place that has no shape is listed under
 * `unmapped`, like a language's; a set a language already uses keeps its
 * language count and gains the label. Asking for the globe is sticky: a set
 * shared by several pages has it if any of them asks.
 *
 * @param Shape_Table                                                                 $table The shape table.
 * @param array{sets: array<string, array{places: list<string>, languages: int, pages?: list<string>, globe?: bool}>, unmapped: list<string>, no_places: int} $plan  From wt_maps_plan_sets().
 * @param array<string, list<string>|array{places: list<string>, globe?: bool}>       $extra Label → place record IDs, or places and options.
 * @return array{sets: array<string, array{places: list<string>, languages: int, pages?: list<string>, globe?: bool}>, unmapped: list<string>, no_places: int}
 */
function wt_maps_add_extra_sets( Shape_Table $table, array $plan, array $extra ): array {
	foreach ( $extra as $label => $entry ) {
		$globe  = isset( $entry['places'] ) && ! empty( $entry['globe'] );
		$places = isset( $entry['places'] ) ? (array) $entry['places'] : $entry;
		$places = array_values( array_unique( array_filter( $places, 'is_string' ) ) );
		sort( $places );
		if ( array() === $places ) {
			continue;
		}
		$missing = array_filter( $places, fn( $id ) => array() === $table->shapes( $id ) );
		if ( array() !== $missing ) {
			$plan['unmapped'][] = sprintf( '%s: no shape for %s', $label, implode( ', ', $missing ) );
			continue;
		}
		$key = wt_maps_set_key( $places );
		if ( ! isset( $plan['sets'][ $key ] ) ) {
			$plan['sets'][ $key ] = array(
				'places'    => $places,
				'languages' => 0,
			);
		}
		$plan['sets'][ $key ]['pages'][] = (string) $label;
		if ( $globe ) {
			$plan['sets'][ $key ]['globe'] = true;
		}
	}
	ksort( $plan['sets'] );
	sort( $plan['unmapped'] );
	return $plan;
}

/**
 * Map sets other plugins' pages need, as label → place record IDs, from the
 * `wt_maps_extra_sets` filter. Used by `export` and `doctor`.
 *
 * @return array<string, list<string>|array{places: list<string>, globe?: bool}>
 */
function wt_maps_extra_sets(): array {
	$extra = apply_filters( 'wt_maps_extra_sets', array() );
	return is_array( $extra ) ? $extra : array();
}
