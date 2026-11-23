<?php
/**
 * Parse a comma-separated list of post IDs into a sanitised array.
 *
 * Backs the gallery's `selected_posts` attribute. Non-numeric and zero entries
 * are dropped so a malformed value can't widen the query, and the given order
 * is preserved so callers can pair this with orderby="post__in".
 *
 * @param mixed $csv Comma-separated post IDs.
 * @return int[] Post IDs, in the order given.
 */
function wt_gallery_selected_post_ids( $csv ) {
	if ( ! is_string( $csv ) || '' === trim( $csv ) ) {
		return array();
	}

	$ids = array_map( 'intval', array_map( 'trim', explode( ',', $csv ) ) );

	return array_values( array_filter( $ids ) );
}

/**
 * Builds the WP_Query args array for the custom gallery.
 *
 * Contains all arg-building logic for get_custom_gallery_query() so it can
 * be tested independently of WP_Query instantiation.
 *
 * @param array $atts Shortcode/function attributes.
 * @return array WP_Query arguments.
 */
function build_gallery_query_args( $atts = array() ) {
	$defaults = array(
		'post_status'    => 'publish',
		'post_type'      => 'languages', // videos, languages, fellows
		'posts_per_page' => 6,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_key'       => '',
		'meta_value'     => '',
		'paged'          => 1,
		'taxonomy'       => '',
		'term'           => '',
		'exclude_self'   => 'false',
	);

	$args = wp_parse_args( $atts, $defaults );

	$meta_query = array();

	if ( ! empty( $atts['meta_key'] ) && ! empty( $atts['meta_value'] ) ) {
		$val_array = array_map( 'trim', explode( ',', $atts['meta_value'] ) );
		// Special handling for featured_languages: meta_value contains language post IDs.
		if ( $atts['meta_key'] === 'featured_languages' ) {
			$meta_query = array( 'relation' => 'OR' );
			foreach ( $val_array as $language_id ) {
				$meta_query[] = array(
					'key'     => 'featured_languages',
					'value'   => '"' . intval( $language_id ) . '"',
					'compare' => 'LIKE',
				);
			}
			$args['meta_query'] = $meta_query;
		} else {
			// Non-featured_languages keys follow original logic
			$compare_operator = 'LIKE';
			// Serialized post-ID fields: match the quoted ID inside the stored array.
			if ( in_array( $atts['meta_key'], array( 'fellow_language', 'nations_of_origin' ), true ) ) {
				$val_array = array_map( fn( $v ) => '"' . intval( $v ) . '"', $val_array );
			}

			if ( count( $val_array ) > 1 ) {
				$meta_query = array( 'relation' => 'OR' );
				foreach ( $val_array as $value ) {
					if ( ! empty( $value ) ) {
						$meta_query[] = array(
							'key'     => $atts['meta_key'],
							'value'   => $value,
							'compare' => $compare_operator,
						);
					}
				}
			} else {
				$meta_query[] = array(
					'key'     => $atts['meta_key'],
					'value'   => $val_array[0],
					'compare' => $compare_operator,
				);
			}

			$args['meta_query'] = $meta_query;
		}

		unset( $args['meta_key'] );
		unset( $args['meta_value'] );
	} elseif ( ! empty( $atts['taxonomy'] ) && ! empty( $atts['term'] ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy'         => $atts['taxonomy'],
				'field'            => 'slug',
				'terms'            => $atts['term'],
				'include_children' => true, // explicit: continent slugs must include child regions
			),
		);
	}

	// Exclude current post from the query
	if ( $args['exclude_self'] === 'true' ) {
		$current_post_type = get_post_type();
		if ( $current_post_type === $args['post_type'] ) {
			$args['post__not_in'] = array( get_the_ID() );
		}
	}

	if ( ! empty( $args['tax_query'] ) ) {
		$tax_query = $args['tax_query'][0];
		$terms     = explode( ',', $tax_query['terms'] );
		$terms     = array_map( 'trim', $terms );

		if ( count( $terms ) > 1 ) {
			$args['tax_query'] = array( 'relation' => 'OR' );
			foreach ( $terms as $term ) {
				if ( ! empty( $term ) ) {
					$args['tax_query'][] = array(
						'taxonomy' => $tax_query['taxonomy'],
						'field'    => $tax_query['field'],
						'terms'    => $term,
					);
				}
			}
		} else {
			$args['tax_query'][0]['terms'] = $terms[0];
		}
	}

	// Seeded random order (#380): when a stable seed is supplied (persisted in
	// the gallery's data-attributes and echoed back by the AJAX pagination
	// request), turn orderby=rand into RAND(<seed>) so the shuffle is identical
	// on every page. Without a seed, plain rand is left as-is.
	if ( 'rand' === $args['orderby'] && ! empty( $args['rand_seed'] ) ) {
		$args['orderby'] = 'RAND(' . (int) $args['rand_seed'] . ')';
	}
	unset( $args['rand_seed'] );

	return $args;
}

/**
 * Retrieves a list of posts for the custom gallery.
 *
 * @param array $atts Arguments to customize the WP_Query.
 * @return WP_Query The query result.
 */
function get_custom_gallery_query( $atts = array() ) {
	return new WP_Query( build_gallery_query_args( $atts ) );
}


/**
 * Invert stored ID lists: each row's serialized array of target IDs becomes
 * target ID => post IDs, keeping row order and dropping duplicates.
 *
 * ACF relationship and post_object fields store a serialized array of ID
 * strings. Reading them once and inverting in PHP replaces one `LIKE` scan
 * of wp_postmeta per gallery card.
 *
 * @param array<int, object> $rows Rows with `post_id` and `meta_value`.
 * @return array<int, int[]> Target ID => post IDs.
 */
function wt_gallery_invert_id_lists( array $rows ): array {
	$map = array();
	foreach ( $rows as $row ) {
		$targets = maybe_unserialize( $row->meta_value );
		if ( ! is_array( $targets ) ) {
			continue;
		}
		$post_id = (int) $row->post_id;
		foreach ( $targets as $target ) {
			$target = is_scalar( $target ) ? (int) $target : 0;
			if ( $target > 0 ) {
				$map[ $target ][ $post_id ] = $post_id;
			}
		}
	}
	return array_map( 'array_values', $map );
}

/**
 * Published posts of a type that reference each target ID through a
 * serialized ID-list meta field, ordered by post title.
 *
 * One query per meta key and post type per request; the result is kept in
 * a static cache, so every card in a gallery shares it.
 *
 * @param string $meta_key  Meta key holding a serialized ID array.
 * @param string $post_type Post type of the referencing posts.
 * @return array<int, int[]> Target ID => referencing post IDs.
 */
function wt_gallery_posts_by_meta_reference( string $meta_key, string $post_type ): array {
	static $cache = array();
	$key          = $meta_key . '|' . $post_type;
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	global $wpdb;
	$rows          = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT m.post_id, m.meta_value FROM {$wpdb->postmeta} m
			INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
			WHERE m.meta_key = %s AND p.post_type = %s AND p.post_status = 'publish'
			ORDER BY p.post_title ASC, p.ID ASC",
			$meta_key,
			$post_type
		)
	);
	$cache[ $key ] = wt_gallery_invert_id_lists( is_array( $rows ) ? $rows : array() );
	return $cache[ $key ];
}

/**
 * Values of a few meta keys for a set of posts, in one query.
 *
 * Reads only the named keys, unlike update_meta_cache(), which loads every
 * meta row of every post (languages carry dozens).
 *
 * @param string[] $meta_keys Meta keys to read.
 * @param int[]    $post_ids  Post IDs.
 * @return array<int, array<string, string>> Post ID => meta key => first value.
 */
function wt_gallery_meta_values( array $meta_keys, array $post_ids ): array {
	$post_ids = array_values( array_unique( array_filter( array_map( 'intval', $post_ids ) ) ) );
	if ( ! $post_ids || ! $meta_keys ) {
		return array();
	}

	global $wpdb;
	$key_placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
	$id_placeholders  = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
	$rows             = $wpdb->get_results(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- only %s/%d placeholder lists built above.
			"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key IN ($key_placeholders) AND post_id IN ($id_placeholders) ORDER BY meta_id ASC",
			array_merge( array_values( $meta_keys ), $post_ids )
		)
	);

	$values = array();
	foreach ( is_array( $rows ) ? $rows : array() as $row ) {
		$post_id = (int) $row->post_id;
		if ( ! isset( $values[ $post_id ][ $row->meta_key ] ) ) {
			$values[ $post_id ][ $row->meta_key ] = (string) $row->meta_value;
		}
	}
	return $values;
}

/**
 * Card preview data for languages: display name and one random featured
 * video (thumbnail and titles).
 *
 * Replaces a title lookup plus a `featured_languages` LIKE query per
 * language with a handful of batched queries for the whole gallery. Results
 * are cached per language for the request, so calling it once per card with
 * the gallery's full ID list only queries on the first card.
 *
 * @param int[] $language_ids Language post IDs.
 * @return array<int, array{name: string, video_id: int, video_title: string, video_post_title: string, thumbnail: string}>
 */
function wt_gallery_language_previews( array $language_ids ): array {
	static $cache = array();

	$language_ids = array_values( array_unique( array_filter( array_map( 'intval', $language_ids ) ) ) );
	$missing      = array_values( array_diff( $language_ids, array_keys( $cache ) ) );

	if ( $missing ) {
		$videos_by_language = wt_gallery_posts_by_meta_reference( 'featured_languages', 'videos' );
		$video_for          = array();
		foreach ( $missing as $language_id ) {
			$pool = $videos_by_language[ $language_id ] ?? array();
			if ( $pool ) {
				// Same effect as the former ORDER BY RAND() LIMIT 1.
				$video_for[ $language_id ] = $pool[ array_rand( $pool ) ];
			}
		}

		_prime_post_caches( array_merge( $missing, array_values( $video_for ) ), false, false );
		$meta = wt_gallery_meta_values(
			array( 'standard_name', 'video_title', 'video_thumbnail_v2' ),
			array_merge( $missing, array_values( $video_for ) )
		);

		$attachment_ids = array();
		foreach ( $video_for as $video_id ) {
			$attachment_id = (int) ( $meta[ $video_id ]['video_thumbnail_v2'] ?? 0 );
			if ( $attachment_id > 0 ) {
				$attachment_ids[] = $attachment_id;
			}
		}
		if ( $attachment_ids ) {
			_prime_post_caches( $attachment_ids, false, true );
		}

		foreach ( $missing as $language_id ) {
			$video_id      = $video_for[ $language_id ] ?? 0;
			$attachment_id = $video_id ? (int) ( $meta[ $video_id ]['video_thumbnail_v2'] ?? 0 ) : 0;
			$thumbnail     = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';
			$name          = $meta[ $language_id ]['standard_name'] ?? '';

			$cache[ $language_id ] = array(
				'name'             => '' !== $name ? $name : get_the_title( $language_id ),
				'video_id'         => $video_id,
				'video_title'      => $video_id ? ( $meta[ $video_id ]['video_title'] ?? '' ) : '',
				'video_post_title' => $video_id ? get_the_title( $video_id ) : '',
				'thumbnail'        => is_string( $thumbnail ) ? $thumbnail : '',
			);
		}
	}

	return array_intersect_key( $cache, array_flip( $language_ids ) );
}
