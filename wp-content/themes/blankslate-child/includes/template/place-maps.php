<?php
/**
 * Locator maps on territory and region pages (plan 3.2, docs/maps.md).
 *
 * A territory's map is its own one-place set: the file a language spoken only
 * there already uses. A sub-region's map is the set of its published
 * territories, all primary, framed together. A continent's map (step 1c) is
 * every territory in its sub-regions, in a fixed frame, each sub-region
 * outlined as one shape; the build gives island sub-regions a halo.
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
 * Continent frames, keyed on the term slug (the build reads them, render.mjs):
 * `center` [lon, lat] fixes the view's centre and orientation; without a
 * `radius` the territories are fitted with equal margins round it; `radius`
 * (degrees) frames a circle instead, for continents too tall for a 4:3 frame;
 * `roll` turns the view (degrees, positive clockwise); `crop` keeps part of the
 * fitted view, [x0, y0, x1, y1] as fractions; `include` adds [lon, lat] points
 * to the fit. Tuned by eye, 2026-11-24.
 *
 * @return array<string, array{center?: array{0: float, 1: float}, radius?: float, roll?: float, crop?: array{0: float, 1: float, 2: float, 3: float}, include?: list<array{0: float, 1: float}>}>
 */
function wt_continent_frames(): array {
	return array(
		// Tall: a circle, so the Atlas and the Cape keep a margin.
		'africa'   => array(
			'center' => array( 18.0, 2.0 ),
			'radius' => 36.0,
		),
		'americas' => array(
			'center' => array( -82.0, 12.0 ),
			'radius' => 66.0,
		),
		// Fitted, turned so Japan isn't pushed up the right edge.
		'asia'     => array(
			'center' => array( 87.0, 31.0 ),
			'roll'   => -10.0,
		),
		// Fitted, so Russia is drawn whole, and out to Spain's Canary Islands,
		// which the fit (main bodies only) would leave off the west edge; then
		// 20% closer, into the fit's margin.
		'europe'   => array(
			'include' => array( array( -18.2, 27.6 ) ),
			'crop'    => array( 0.0833, 0.0833, 0.9167, 0.9167 ), // 20% closer, centred.
		),
		'oceania'  => array(
			'center' => array( 166.0, -14.0 ),
		),
	);
}

/**
 * A continent's sub-regions, ordered by slug: the group order on its map.
 *
 * @param WP_Term $continent A top-level `region` term.
 * @return list<WP_Term>
 */
function wt_continent_sub_regions( WP_Term $continent ): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'region',
			'parent'     => $continent->term_id,
			'hide_empty' => false,
			'orderby'    => 'slug',
		)
	);
	return array_values( array_filter( is_array( $terms ) ? $terms : array(), static fn( $t ) => $t instanceof WP_Term ) );
}

/**
 * A continent's map set: its sub-regions' territories, its frame (none when
 * simply fitted), and each place's group (its sub-region's position, from 1). null
 * for a region not in wt_continent_frames(), or with fewer than two places.
 *
 * @param WP_Term $continent A top-level `region` term.
 * @return array{places: list<string>, view: string, groups: array<string, int>, frame?: array<string, mixed>}|null
 */
function wt_continent_set( WP_Term $continent ): ?array {
	$frame = wt_continent_frames()[ $continent->slug ] ?? null;
	if ( null === $frame ) {
		return null;
	}
	$places = array();
	$groups = array();
	foreach ( wt_continent_sub_regions( $continent ) as $i => $sub_region ) {
		foreach ( wt_maps_record_ids( wt_region_territory_ids( $sub_region ) ) as $record_id ) {
			$places[]             = $record_id;
			$groups[ $record_id ] = $i + 1;
		}
	}
	if ( count( $places ) < 2 ) {
		return null;
	}
	$set = array(
		'places' => $places,
		'view'   => 'continent',
		'groups' => $groups,
	);
	if ( array() !== $frame ) {
		$set['frame'] = $frame;
	}
	return $set;
}

/**
 * Whether an archive region gets a sub-region map: sub-regions only.
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
		if ( ! $region instanceof WP_Term ) {
			continue;
		}
		if ( ! wt_region_has_map( $region ) ) {
			$continent = wt_continent_set( $region );
			if ( null !== $continent ) {
				$sets[ 'Continent: ' . html_entity_decode( $region->name ) ] = $continent;
			}
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
 * A region page's map: a sub-region's territories framed together, or a
 * continent's in its fixed frame, grouped by sub-region; all primary. '' for a
 * continent without a frame, or when the set is unbuilt.
 *
 * @param WP_Term        $region        A `region` term.
 * @param list<int>|null $territory_ids A sub-region's published territories,
 *                                      when the page has already queried them.
 * @return string
 */
function wt_region_map( WP_Term $region, ?array $territory_ids = null ): string {
	if ( ! function_exists( 'wt_map_places' ) ) {
		return '';
	}
	$view = '';
	if ( wt_region_has_map( $region ) ) {
		$places = wt_maps_record_ids( $territory_ids ?? wt_region_territory_ids( $region ) );
	} else {
		$continent = wt_continent_set( $region );
		$places    = null === $continent ? array() : $continent['places'];
		$view      = 'continent';
	}
	if ( count( $places ) < 2 ) {
		return '';
	}
	return wt_map_places(
		array(
			'places'  => $places,
			'primary' => $places,
			'title'   => 'Map of ' . wt_prefix_the( html_entity_decode( $region->name ) ),
			'class'   => 'wt-place-map', // One class: wt_map_places() sanitizes it as one.
			'view'    => $view,
		)
	);
}
