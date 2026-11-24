<?php
/**
 * Interactive maps: where each shape on a map leads, and its label (plan 3.2, step 2a).
 *
 * The built SVG stays shared by every page that draws the same set, and PHP
 * adds nothing to its shapes. The build tags every shape that stands for a
 * place with `data-place` (its Airtable record ID): the highlighted places and,
 * on the base map, the neighbouring countries. The figure carries one JSON
 * attribute, `data-wt-map`, mapping those record IDs to a label and a URL, and
 * `assets/wt-maps.js` reads it for hover labels and clicks:
 *
 *     { "places": { "recxJZFYc5RSOFGP6": { "n": "French Polynesia", "u": "https://…" } } }
 *
 * Resolved by record ID (`_airtable_record_id`), never by title. A subdivision
 * has no page (plan 6.8), so it leads to its nation's page and says so (`v`,
 * "via"). A place with no published page gets a label and no URL. Contested
 * areas are labelled in the browser from the SVG's own `data-area` and never link.
 *
 * @package WT\Maps
 */

/**
 * Label and destination for each place, by record ID, from one query.
 *
 * @param list<string> $record_ids Place record IDs.
 * @return array<string, array{n: string, u?: string, v?: string}> n: name; u: URL; v: the page's name when it isn't the place's own.
 */
function wt_maps_place_links( array $record_ids ): array {
	$links = array();
	if ( array() === $record_ids ) {
		return $links;
	}

	$posts = get_posts(
		array(
			'post_type'      => array( 'territories', 'subdivisions' ),
			'post_status'    => 'publish',
			'posts_per_page' => count( $record_ids ),
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_airtable_record_id',
					'value'   => $record_ids,
					'compare' => 'IN',
				),
			),
		)
	);

	foreach ( $posts as $post ) {
		$record_id = (string) get_post_meta( $post->ID, '_airtable_record_id', true );
		$link      = array( 'n' => get_the_title( $post ) );
		if ( 'territories' === $post->post_type ) {
			$link['u'] = (string) get_permalink( $post );
		} else {
			// A subdivision has no page of its own: lead to its nation's.
			$nation = (int) get_post_meta( $post->ID, 'nation', true );
			if ( $nation > 0 && 'publish' === get_post_status( $nation ) ) {
				$link['u'] = (string) get_permalink( $nation );
				$link['v'] = function_exists( 'wt_prefix_the' ) ? wt_prefix_the( get_the_title( $nation ) ) : get_the_title( $nation ); // "Part of the United States".
			}
		}
		$links[ $record_id ] = $link;
	}

	foreach ( $record_ids as $record_id ) {
		if ( ! isset( $links[ $record_id ] ) ) {
			$links[ $record_id ] = array( 'n' => wt_maps_shape_table()->name( $record_id ) );
		}
	}
	return $links;
}

/**
 * Record IDs of every place the build tagged on a map, in document order.
 *
 * @param string $svg The built SVG.
 * @return list<string>
 */
function wt_maps_tagged_places( string $svg ): array {
	$ids = array();
	$p   = new WP_HTML_Tag_Processor( $svg );
	while ( $p->next_tag() ) {
		$id = $p->get_attribute( 'data-place' );
		if ( is_string( $id ) && '' !== $id ) {
			$ids[ $id ] = true;
		}
	}
	return array_keys( $ids );
}

/**
 * The figure's `data-wt-map` value.
 *
 * @param array<string, array{n: string, u?: string, v?: string}> $links From wt_maps_place_links().
 * @return string JSON, not yet escaped for the attribute.
 */
function wt_maps_interaction_json( array $links ): string {
	ksort( $links );
	return (string) wp_json_encode( array( 'places' => $links ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

/**
 * Load the map script, once, in the footer.
 */
function wt_maps_enqueue_script(): void {
	wp_enqueue_script(
		'wt-maps',
		plugins_url( 'assets/wt-maps.js', dirname( __DIR__ ) . '/wt-maps.php' ),
		array(),
		WT_MAPS_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
