<?php
/**
 * Locator maps on territory and region pages (plan 3.2, docs/maps.md).
 *
 * A territory's map is its own one-place set: the file a language spoken only
 * there already uses. A sub-region's map is the set of its published
 * territories, all primary, framed together. Continents have no map until the
 * step 2 world view: at that scale the small-place rings crowd the map and
 * Russia stretches Europe's frame to the Pacific.
 *
 * Every set is keyed on its places' Airtable record IDs (wt_maps_set_key()) and
 * registered with `wt_maps_extra_sets`, so `wp wt-maps export` and `doctor`
 * plan them from the posts on every run. Region terms have no record ID, so a
 * region's map is named by what it draws: when a territory joins or leaves a
 * region the key changes, and `doctor` lists the new set as unbuilt.
 *
 * @package blankslate-child
 */

/**
 * Whether an archive region gets a map: sub-regions only.
 *
 * @param WP_Term $region A `region` term.
 * @return bool
 */
function wt_region_has_map( WP_Term $region ): bool {
	return 0 !== (int) $region->parent;
}

/**
 * Published territory post IDs in a sub-region.
 *
 * @param WP_Term $region A `region` term.
 * @return list<int>
 */
function wt_region_territory_ids( WP_Term $region ): array {
	return array_values(
		array_map(
			'intval',
			get_posts(
				array(
					'post_type'      => 'territories',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy'         => 'region',
							'field'            => 'term_id',
							'terms'            => array( $region->term_id ),
							'include_children' => false,
						),
					),
				)
			)
		)
	);
}

/**
 * Map sets for territory and region pages, for `wt_maps_extra_sets`.
 *
 * One per published territory (most are already built for languages) and one
 * per sub-region. Absolute state: rebuilt from the posts on every export.
 *
 * @param array<string, list<string>|array{places: list<string>, globe?: bool}> $sets Label → places, or places and options.
 * @return array<string, list<string>|array{places: list<string>, globe?: bool}>
 */
function wt_place_map_sets( array $sets ): array {
	if ( ! function_exists( 'wt_maps_record_ids' ) ) {
		return $sets;
	}

	$territories = get_posts(
		array(
			'post_type'      => 'territories',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	foreach ( $territories as $territory_id ) {
		$places = wt_maps_record_ids( array( (int) $territory_id ) );
		if ( array() !== $places ) {
			$sets[ 'Territory: ' . get_the_title( (int) $territory_id ) ] = $places;
		}
	}

	$regions = get_terms(
		array(
			'taxonomy'   => 'region',
			'hide_empty' => false,
		)
	);
	foreach ( is_array( $regions ) ? $regions : array() as $region ) {
		if ( ! $region instanceof WP_Term || ! wt_region_has_map( $region ) ) {
			continue;
		}
		$places = wt_maps_record_ids( wt_region_territory_ids( $region ) );
		if ( count( $places ) > 1 ) {
			// The locator globe whatever the frame's size: Polynesia is islands in an
			// empty sea. A territory's map keeps the language rule (small frames only).
			$sets[ 'Region: ' . html_entity_decode( $region->name ) ] = array(
				'places' => $places,
				'globe'  => true,
			);
		}
	}

	return $sets;
}
add_filter( 'wt_maps_extra_sets', 'wt_place_map_sets' );

/**
 * A territory page's map: the territory alone, primary. '' when unbuilt.
 *
 * @param int $territory_id Territory post ID.
 * @return string
 */
function wt_territory_map( int $territory_id ): string {
	if ( ! function_exists( 'wt_map_places' ) ) {
		return '';
	}
	$places = wt_maps_record_ids( array( $territory_id ) );
	if ( array() === $places ) {
		return '';
	}
	return wt_map_places(
		array(
			'places'  => $places,
			'primary' => $places,
			'title'   => 'Map of ' . wt_prefix_the( get_the_title( $territory_id ) ),
			'class'   => 'wt-place-map',
			'inset'   => 1.2, // The locator globe 20% larger than on language pages.
		)
	);
}

/**
 * A territory page's second map: its sub-region, the whole region active (the
 * region page's map), above the "Other territories in…" list. '' when the
 * territory is filed under a continent, or the set is unbuilt.
 *
 * @param int $territory_id Territory post ID.
 * @return string
 */
function wt_territory_region_map( int $territory_id ): string {
	if ( ! function_exists( 'wt_map_places' ) ) {
		return '';
	}
	$terms = get_the_terms( $territory_id, 'region' );
	$term  = is_array( $terms ) ? $terms[0] : null;
	if ( ! $term instanceof WP_Term || ! wt_region_has_map( $term ) ) {
		return '';
	}
	return wt_region_map( $term );
}

/**
 * A sub-region page's map: every territory in it, primary, framed together.
 * '' for a continent, or when the set is unbuilt.
 *
 * @param WP_Term        $region        A `region` term.
 * @param list<int>|null $territory_ids Its published territories, when the page
 *                                      has already queried them.
 * @return string
 */
function wt_region_map( WP_Term $region, ?array $territory_ids = null ): string {
	if ( ! function_exists( 'wt_map_places' ) || ! wt_region_has_map( $region ) ) {
		return '';
	}
	$places = wt_maps_record_ids( $territory_ids ?? wt_region_territory_ids( $region ) );
	if ( count( $places ) < 2 ) {
		return '';
	}
	return wt_map_places(
		array(
			'places'  => $places,
			'primary' => $places,
			'title'   => 'Map of ' . wt_prefix_the( html_entity_decode( $region->name ) ),
			'class'   => 'wt-place-map', // One class: wt_map_places() sanitizes it as one.
		)
	);
}
