<?php
/**
 * Public API: wt_map(), the [wt_map] shortcode, and the sidebar map.
 *
 * @package WT\Maps
 */

use WT\Maps\Renderer;

/**
 * Plugin settings, with defaults.
 *
 * @return array{sidebar: bool, interactive: bool}
 */
function wt_maps_settings(): array {
	$saved = get_option( 'wt_maps', array() );
	$saved = is_array( $saved ) ? $saved : array();

	return array(
		'sidebar'     => ! isset( $saved['sidebar'] ) || (bool) $saved['sidebar'],
		'interactive' => ! isset( $saved['interactive'] ) || (bool) $saved['interactive'],
	);
}

/**
 * Airtable record IDs of published place posts, in the order given.
 *
 * @param array<int|string|WP_Post> $post_ids Territory or subdivision posts or their IDs.
 * @return list<string>
 */
function wt_maps_record_ids( array $post_ids ): array {
	$ids = array();
	foreach ( $post_ids as $post_id ) {
		$post_id = (int) ( $post_id instanceof WP_Post ? $post_id->ID : $post_id );
		if ( $post_id <= 0 || 'publish' !== get_post_status( $post_id ) ) {
			continue;
		}
		$record_id = (string) get_post_meta( $post_id, '_airtable_record_id', true );
		if ( '' !== $record_id ) {
			$ids[] = $record_id;
		}
	}
	return array_values( array_unique( $ids ) );
}

/**
 * Current names of places, by record ID, from their posts.
 *
 * Falls back to the shape table's label for a place with no post. Shares its
 * one query with an interactive map's links (wt_maps_place_links()).
 *
 * @param list<string> $record_ids Place record IDs.
 * @return array<string, string>
 */
function wt_maps_place_names( array $record_ids ): array {
	return array_map( static fn( array $link ): string => $link['n'], wt_maps_place_links( $record_ids ) );
}

/**
 * The shared SVG definitions every map uses: the red hatch over an active
 * contested area (1.8 lines on 5 units; claims spec, display). Each map's own
 * `--claim` pattern draws its relief under it. Printed once per page, before
 * the first map.
 *
 * @return string
 */
function wt_maps_defs(): string {
	return '<svg class="wt-map-defs" width="0" height="0" aria-hidden="true" focusable="false"><defs>'
		. '<pattern id="hatch-claim" width="5" height="5" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><line class="hc-line" x1="2.5" y1="0" x2="2.5" y2="5"/></pattern>'
		// Claims on hover: an area the hovered place claims that isn't active, on plain land.
		. '<pattern id="hatch-hover" width="5" height="5" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><rect class="hh-bg" x="-1" y="-1" width="7" height="7"/><line class="hh-line" x1="2.5" y1="0" x2="2.5" y2="5"/></pattern>'
		. '</defs></svg>';
}
/**
 * A language's locator map, or '' when it has none.
 *
 * @param array<string, mixed> $args {
 *     @type int        $language    Language post ID. Default: the current post.
 *     @type array<int> $territories Territory/subdivision post IDs to draw instead
 *                                   of the language's focus or territory links.
 *     @type array<int> $primary     Post IDs to highlight as primary. Default:
 *                                   the language's countries of origin.
 *     @type string     $class       Extra class on the figure.
 *     @type bool       $caption     Show the description as a visible caption. Default false.
 * }
 * @return string A `<figure class="wt-map">`, preceded by the shared defs the
 *                first time a map is printed on the page.
 */
function wt_map( array $args = array() ): string {
	$language = (int) ( $args['language'] ?? get_the_ID() );
	if ( $language <= 0 ) {
		return '';
	}

	$origins = $args['primary'] ?? get_post_meta( $language, 'nations_of_origin', true );
	$origins = wt_maps_record_ids( is_array( $origins ) ? $origins : array() );

	$table = wt_maps_shape_table();
	if ( isset( $args['territories'] ) ) {
		$places = wt_maps_language_places( $table, '', wt_maps_record_ids( (array) $args['territories'] ) );
	} else {
		$linked = get_post_meta( $language, 'territories', true );
		$places = wt_maps_language_places(
			$table,
			(string) get_post_meta( $language, '_airtable_record_id', true ),
			wt_maps_record_ids( is_array( $linked ) ? $linked : array() ),
			$origins
		);
	}

	// A language's post title is its ISO code; its display name is standard_name.
	$name = (string) get_post_meta( $language, 'standard_name', true );

	return wt_map_places(
		array(
			'places'  => $places,
			'primary' => $origins,
			'title'   => sprintf( 'Where %s is spoken', '' !== $name ? $name : get_the_title( $language ) ),
			'class'   => $args['class'] ?? '',
			'caption' => ! empty( $args['caption'] ),
		) + array_intersect_key( $args, array( 'interactive' => true ) )
	);
}

/**
 * The map for any set of places, by record ID: what wt_map() draws for a
 * language, for pages that aren't one (a Language Rights report region or
 * country). '' when the set has no built map.
 *
 * @param array{places: list<string>, primary?: list<string>, title: string, class?: string, caption?: bool, inset?: float, view?: string, interactive?: bool, groups?: list<array{n: string, u: string, places: list<string>}>} $args
 *     Places and primary places as record IDs; the map's accessible title;
 *     `inset` scales the locator globe (1.2 on territory pages); `view` picks
 *     another drawing of the places (`continent`), see wt_maps_set_key().
 *     `interactive` adds hover labels and links (default: Settings → Maps);
 *     `groups` makes each place lead to its group (a continent's sub-regions;
 *     the countries around it lead to theirs).
 * @return string
 */
function wt_map_places( array $args ): string {
	static $defs_printed = false;

	$places  = array_values( array_unique( $args['places'] ) );
	$origins = $args['primary'] ?? array();
	sort( $places );

	$key  = wt_maps_set_key( $places, (string) ( $args['view'] ?? '' ) );
	$file = dirname( __DIR__ ) . '/maps/' . $key . '.svg';
	if ( '' === $key || ! is_readable( $file ) ) {
		return '';
	}

	$roles = Renderer::roles( $places, $origins );
	$inter = (bool) ( $args['interactive'] ?? wt_maps_settings()['interactive'] );

	// An interactive map links every place the build tagged, neighbours included,
	// resolved with the place names in one query.
	$svg    = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local plugin file.
	$groups = $args['groups'] ?? array();
	$tagged = $inter ? wt_maps_tagged_places( $svg ) : array();
	// A grouped map (a continent) labels its shapes by group, so only its own
	// places need names.
	$links = wt_maps_place_links( array() === $groups ? array_values( array_unique( array_merge( $places, $tagged ) ) ) : $places );
	$names = array_map( static fn( array $link ): string => $link['n'], array_intersect_key( $links, array_flip( $places ) ) );
	if ( function_exists( 'wt_prefix_the' ) ) { // The theme's "the Northern Mariana Islands".
		$names = array_map( 'wt_prefix_the', $names );
	}
	$title = $args['title'];
	$desc  = Renderer::describe( $roles, $names, PHP_INT_MAX, $origins );
	$cap   = empty( $args['caption'] ) ? '' : Renderer::caption( $roles, $names, $origins );
	$class = trim( 'wt-map' . ( $inter ? ' wt-map--interactive' : '' ) . ' ' . sanitize_html_class( (string) ( $args['class'] ?? '' ) ) );
	$inset = (float) ( $args['inset'] ?? 1.0 );
	$data  = $inter ? wt_maps_interaction_json( $links, $groups, array_merge( $places, $tagged ) ) : '';

	$cache_key = md5( (string) wp_json_encode( array( $key, $roles, $title, $desc, $cap, $class, $inset, $data, Renderer::VERSION, filemtime( $file ) ) ) );
	$figure    = wp_cache_get( $cache_key, 'wt_maps' );
	if ( ! is_string( $figure ) ) {
		$svg    = Renderer::decorate( $svg, $roles, plugins_url( 'maps/', dirname( __DIR__ ) . '/wt-maps.php' ), $title, $desc, $inset );
		$figure = '<figure class="' . esc_attr( $class ) . '"' . ( '' !== $data ? ' data-wt-map="' . esc_attr( $data ) . '"' : '' ) . '>' . $svg
			. ( '' !== $cap ? '<figcaption class="wt-map__caption">' . esc_html( ucfirst( $cap ) ) . '</figcaption>' : '' )
			. ( Renderer::shows_contested( $svg ) ? '<p class="wt-map__note">' . esc_html( Renderer::DISCLAIMER ) . '</p>' : '' )
			. '</figure>';
		wp_cache_set( $cache_key, $figure, 'wt_maps', DAY_IN_SECONDS );
	}

	if ( $inter ) {
		wt_maps_enqueue_script();
	}
	$defs         = $defs_printed ? '' : wt_maps_defs();
	$defs_printed = true;
	return $defs . $figure;
}

/**
 * The map for the language sidebar, unless turned off in Settings → Maps.
 *
 * @param int $language Language post ID.
 * @return string
 */
function wt_maps_sidebar_map( int $language ): string {
	return wt_maps_settings()['sidebar']
		? wt_map(
			array(
				'language' => $language,
				'caption'  => true,
			)
		)
		: '';
}

/**
 * [wt_map language="123" territories="1,2" primary="1" class="" caption="1"]
 *
 * @param array<string, string>|string $atts Shortcode attributes.
 * @return string
 */
function wt_map_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'language'    => '',
			'territories' => '',
			'primary'     => '',
			'class'       => '',
			'caption'     => '',
		),
		is_array( $atts ) ? $atts : array(),
		'wt_map'
	);

	$ids  = static fn( string $csv ): array => array_values( array_filter( array_map( 'intval', explode( ',', $csv ) ) ) );
	$args = array(
		'class'   => $atts['class'],
		'caption' => '' !== $atts['caption'] && '0' !== $atts['caption'],
	);
	if ( '' !== $atts['language'] ) {
		$args['language'] = $atts['language'];
	}
	foreach ( array( 'territories', 'primary' ) as $name ) {
		if ( '' !== $atts[ $name ] ) {
			$args[ $name ] = $ids( $atts[ $name ] );
		}
	}
	return wt_map( $args );
}
add_shortcode( 'wt_map', 'wt_map_shortcode' );
