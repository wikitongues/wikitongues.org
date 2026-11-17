<?php
/**
 * A language's nations of origin, as territory posts.
 *
 * `nations_of_origin` is an ACF post_object field (multiple) pointing at
 * territories. It was comma-separated text until 2026-11; `wp wt
 * migrate-nations-of-origin` converts stored text to territory IDs. Until a
 * post is migrated its value is still a string, so the reader below resolves
 * text by exact title too. Everything that shows or searches nations of origin
 * goes through these functions rather than reading the meta directly.
 *
 * @package blankslate-child
 */

/**
 * Published territory posts a language is original to, in stored order.
 *
 * @param int $post_id Language post ID.
 * @return WP_Post[]
 */
function wt_language_origins( int $post_id ): array {
	$raw = get_post_meta( $post_id, 'nations_of_origin', true );
	if ( is_array( $raw ) ) {
		$ids = array_map( 'intval', $raw );
	} elseif ( is_string( $raw ) && '' !== trim( $raw ) ) {
		$ids = wt_territory_ids_from_titles( wt_split_origin_titles( $raw ) );
	} else {
		return array();
	}

	$posts = array();
	foreach ( $ids as $id ) {
		$post = get_post( $id );
		if ( $post instanceof WP_Post && 'territories' === $post->post_type && 'publish' === $post->post_status ) {
			$posts[] = $post;
		}
	}
	return $posts;
}

/**
 * Titles of a language's nations of origin.
 *
 * @param int $post_id Language post ID.
 * @return string[]
 */
function wt_language_origin_titles( int $post_id ): array {
	return array_map( 'get_the_title', wt_language_origins( $post_id ) );
}

/**
 * Split legacy comma-separated nation text into trimmed titles.
 *
 * @param string $text Stored text, e.g. "South Korea, North Korea".
 * @return string[]
 */
function wt_split_origin_titles( string $text ): array {
	$text = str_replace( "\xc2\xa0", ' ', $text );
	return array_values( array_filter( array_map( 'trim', explode( ',', $text ) ), fn( string $title ): bool => '' !== $title ) );
}

/**
 * Territory IDs for exact titles, in input order. Unmatched titles are skipped.
 *
 * @param string[] $titles Territory titles.
 * @return int[]
 */
function wt_territory_ids_from_titles( array $titles ): array {
	$ids = array();
	foreach ( $titles as $title ) {
		$query = new WP_Query(
			array(
				'post_type'              => 'territories',
				'title'                  => $title,
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		if ( $query->posts ) {
			$ids[] = (int) $query->posts[0];
		}
	}
	return $ids;
}

/**
 * Comma-separated links to a list of territory posts.
 *
 * @param WP_Post[] $territories Territory posts.
 * @return string HTML, escaped.
 */
function wt_territory_links( array $territories ): string {
	return implode(
		', ',
		array_map(
			function ( $territory ) {
				return '<a href="' . esc_url( get_permalink( $territory ) ) . '">' . esc_html( get_the_title( $territory ) ) . '</a>';
			},
			$territories
		)
	);
}

/**
 * Meta-query clauses that find languages original to any territory whose
 * title contains the search term. Used by the site search.
 *
 * @param string $term Search text.
 * @return array<int, array<string, string>> Clauses for an OR meta_query; empty when no territory matches.
 */
function wt_origin_meta_clauses( string $term ): array {
	$term = trim( $term );
	if ( '' === $term ) {
		return array();
	}
	$ids     = get_posts(
		array(
			'post_type'      => 'territories',
			'post_status'    => 'publish',
			's'              => $term,
			'search_columns' => array( 'post_title' ),
			'fields'         => 'ids',
			'numberposts'    => 10,
		)
	);
	$clauses = array();
	foreach ( (array) $ids as $id ) {
		$clauses[] = array(
			'key'     => 'nations_of_origin',
			'value'   => '"' . (int) $id . '"',
			'compare' => 'LIKE',
		);
	}
	return $clauses;
}
