<?php
/**
 * WP-CLI: move authored content between environments as a bundle file.
 *
 * Pages built from ACF flexible content live entirely in the database, so a
 * deploy never carries them. `wp wt content-export` writes a page and every
 * post its fields reference (gallery FAQs, testimonials) to a JSON bundle;
 * `wp wt content-import` upserts that bundle on another environment.
 *
 * Environment-specific values are made portable on the way out:
 * - the home URL becomes a `{{home}}` token, so links never point at localhost;
 * - post references become post_type + slug, attachments become their upload
 *   path, and terms become taxonomy + slug, then resolve to local IDs on import.
 *
 * @package Wikitongues
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

require_once __DIR__ . '/content-bundle-helpers.php';

/**
 * Meta that belongs to the environment, not the content. Never exported, and
 * left untouched on the target when a post is updated.
 */
const WT_BUNDLE_LOCAL_META = array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_encloseme', '_pingme' );

/**
 * Export one or more posts, plus everything their fields reference, to a bundle.
 *
 * ## OPTIONS
 *
 * <post>...
 * : Post ID or slug of each post to export.
 *
 * --out=<file>
 * : Where to write the bundle. Keep it outside the repo or in docs/local_docs/
 * (gitignored) — unpublished content should not land in the public repo.
 *
 * [--post_type=<type>]
 * : Post type used to look up slugs. Default: page.
 *
 * ## EXAMPLES
 *
 *     wp wt content-export language-rights --out=docs/local_docs/content/language-rights.json --allow-root
 *
 * @param array<int,string>    $args       Posts to export.
 * @param array<string,string> $assoc_args Associative arguments.
 * @return void
 */
function wt_content_export( $args, $assoc_args ) {
	if ( ! function_exists( 'acf_get_field' ) ) {
		WP_CLI::error( 'ACF is not active.' );
	}

	$post_type = isset( $assoc_args['post_type'] ) ? $assoc_args['post_type'] : 'page';
	$out       = $assoc_args['out'];
	$home      = untrailingslashit( home_url() );

	$roots = array();
	foreach ( $args as $arg ) {
		$post = is_numeric( $arg ) ? get_post( (int) $arg ) : wt_bundle_find_post( $post_type, $arg );
		if ( ! $post ) {
			WP_CLI::error( "No {$post_type} found for `{$arg}`." );
		}
		$roots[] = $post->ID;
	}

	// Depth-first, references before the posts that point at them, so the
	// import can resolve every reference by the time it writes a post.
	$ordered = array();
	$seen    = array();
	foreach ( $roots as $id ) {
		wt_bundle_collect( $id, $seen, $ordered );
	}

	$posts = array();
	foreach ( $ordered as $id ) {
		$entry         = wt_bundle_export_post( get_post( $id ), $home );
		$entry['root'] = in_array( $id, $roots, true );
		$posts[]       = $entry;
		WP_CLI::log( sprintf( '  export  %s/%s%s', $entry['post_type'], $entry['post_name'], $entry['root'] ? '' : ' (referenced)' ) );
	}

	$bundle = array(
		'version'     => 1,
		'exported_at' => gmdate( 'c' ),
		'source_home' => $home,
		'posts'       => $posts,
	);

	$dir = dirname( $out );
	if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
		WP_CLI::error( "Cannot create {$dir}." );
	}
	$json = wp_json_encode( $bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false === $json || false === file_put_contents( $out, $json . "\n" ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		WP_CLI::error( "Cannot write {$out}." );
	}

	WP_CLI::success( sprintf( 'Wrote %d post(s) to %s.', count( $posts ), $out ) );
}

/**
 * Upsert a bundle written by `wp wt content-export`.
 *
 * Posts are matched by post type + slug. A match is updated in place — its
 * meta is replaced by the bundle's, so the bundle wins over earlier admin
 * edits — and anything else is created. Safe to re-run.
 *
 * ## OPTIONS
 *
 * <file>
 * : Bundle to import.
 *
 * [--execute]
 * : Write the changes. Without it the command only reports what it would do.
 *
 * [--status=<status>]
 * : Override the status of the bundle's root posts (e.g. draft to review on
 * staging first). Referenced posts keep their own status.
 *
 * [--author=<login>]
 * : Author for posts this creates. Default: none.
 *
 * ## EXAMPLES
 *
 *     wp wt content-import ~/language-rights.json --allow-root
 *     wp wt content-import ~/language-rights.json --execute --status=draft --author=freddie
 *
 * @param array<int,string>    $args       Bundle path.
 * @param array<string,string> $assoc_args Associative arguments.
 * @return void
 */
function wt_content_import( $args, $assoc_args ) {
	if ( ! function_exists( 'acf_get_field' ) ) {
		WP_CLI::error( 'ACF is not active.' );
	}

	$execute = isset( $assoc_args['execute'] );
	$status  = isset( $assoc_args['status'] ) ? $assoc_args['status'] : '';
	$home    = untrailingslashit( home_url() );

	$raw    = file_exists( $args[0] ) ? file_get_contents( $args[0] ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$bundle = $raw ? json_decode( $raw, true ) : null;
	if ( ! is_array( $bundle ) || 1 !== ( $bundle['version'] ?? null ) || empty( $bundle['posts'] ) ) {
		WP_CLI::error( "{$args[0]} is not a version 1 content bundle." );
	}

	$author = 0;
	if ( ! empty( $assoc_args['author'] ) ) {
		$user = get_user_by( 'login', $assoc_args['author'] );
		if ( ! $user ) {
			WP_CLI::error( "No user `{$assoc_args['author']}`." );
		}
		$author = $user->ID;
	}

	if ( $status && ! in_array( $status, array( 'draft', 'pending', 'private', 'publish' ), true ) ) {
		WP_CLI::error( "Unsupported status `{$status}`." );
	}

	WP_CLI::log( $execute ? "Importing into {$home}." : "Dry run against {$home} — pass --execute to write." );

	// Resolve every parent before writing anything: placing a nested page at the
	// top level would publish it at the wrong URL, and failing halfway through
	// would leave the bundle half-imported.
	$parents = array();
	foreach ( $bundle['posts'] as $entry ) {
		if ( empty( $entry['parent'] ) ) {
			continue;
		}
		$parent = get_page_by_path( $entry['parent']['path'], OBJECT, $entry['parent']['post_type'] );
		if ( ! $parent ) {
			WP_CLI::error( sprintf( '%s/%s: parent %s not found on this site. Create it first; nothing was written.', $entry['post_type'], $entry['post_name'], $entry['parent']['path'] ) );
		}
		$parents[ $entry['post_name'] ] = $parent->ID;
	}

	// Content is trusted (it came from our own admin) and kses would strip the
	// markup an editor is allowed to save.
	kses_remove_filters();

	$created  = 0;
	$updated  = 0;
	$warnings = 0;
	$planned  = array();
	$missing  = array();

	foreach ( $bundle['posts'] as $entry ) {
		$existing    = wt_bundle_find_post( $entry['post_type'], $entry['post_name'] );
		$post_status = ( $entry['root'] && $status ) ? $status : $entry['post_status'];

		$meta = array();
		foreach ( $entry['meta'] as $row ) {
			$value = wt_bundle_detokenize_urls( $row['value'], $home );
			if ( isset( $row['ref'] ) ) {
				$value = wt_bundle_resolve_ref( $row, $missing, $planned );
				foreach ( $missing as $label ) {
					WP_CLI::warning( sprintf( '%s/%s: %s → %s not found on this site; left empty.', $entry['post_type'], $entry['post_name'], $row['key'], $label ) );
					++$warnings;
				}
			}
			$meta[] = array( $row['key'], $value );
		}

		$parent_id = empty( $entry['parent'] ) ? 0 : $parents[ $entry['post_name'] ];

		WP_CLI::log(
			sprintf(
				'  %s %s/%s (%s, %d meta)',
				$existing ? ( $execute ? 'update     ' : 'would update' ) : ( $execute ? 'create     ' : 'would create' ),
				$entry['post_type'],
				$entry['post_name'],
				$post_status,
				count( $meta )
			)
		);

		if ( $existing ) {
			++$updated;
		} else {
			++$created;
		}

		if ( ! $execute ) {
			if ( ! $existing ) {
				$planned[] = $entry['post_type'] . '/' . $entry['post_name'];
			}
			continue;
		}

		$postarr = array(
			'post_type'      => $entry['post_type'],
			'post_name'      => $entry['post_name'],
			'post_title'     => $entry['post_title'],
			'post_content'   => wt_bundle_detokenize_urls( $entry['post_content'], $home ),
			'post_excerpt'   => wt_bundle_detokenize_urls( $entry['post_excerpt'], $home ),
			'post_status'    => $post_status,
			'menu_order'     => $entry['menu_order'],
			'post_parent'    => $parent_id,
			'comment_status' => $entry['comment_status'],
			'ping_status'    => $entry['ping_status'],
		);
		if ( $existing ) {
			$postarr['ID'] = $existing->ID;
		} else {
			$postarr['post_author'] = $author;
		}

		$id = $existing ? wp_update_post( wp_slash( $postarr ), true ) : wp_insert_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $id ) ) {
			WP_CLI::error( sprintf( '%s/%s: %s', $entry['post_type'], $entry['post_name'], $id->get_error_message() ) );
		}

		// Replace, don't merge: rows left over from a longer earlier version of
		// a flexible-content field would otherwise linger as orphans.
		foreach ( array_keys( get_post_meta( $id ) ) as $key ) {
			if ( ! in_array( $key, WT_BUNDLE_LOCAL_META, true ) ) {
				delete_post_meta( $id, $key );
			}
		}
		foreach ( $meta as $pair ) {
			add_post_meta( $id, $pair[0], wp_slash( $pair[1] ) );
		}

		foreach ( $entry['terms'] as $taxonomy => $slugs ) {
			$ids = array();
			foreach ( $slugs as $slug ) {
				$term = get_term_by( 'slug', $slug, $taxonomy );
				if ( $term ) {
					$ids[] = $term->term_id;
				} else {
					WP_CLI::warning( sprintf( '%s/%s: term %s/%s not found; skipped.', $entry['post_type'], $entry['post_name'], $taxonomy, $slug ) );
					++$warnings;
				}
			}
			wp_set_object_terms( $id, $ids, $taxonomy );
		}

		clean_post_cache( $id );
	}

	WP_CLI::success(
		sprintf(
			'%s %d, %s %d, %d warning(s).',
			$execute ? 'Created' : 'Would create',
			$created,
			$execute ? 'updated' : 'would update',
			$updated,
			$warnings
		)
	);
}

/**
 * Find a post of any non-trashed status by type and slug.
 *
 * @param string $post_type Post type.
 * @param string $slug      Post slug.
 * @return WP_Post|null
 */
function wt_bundle_find_post( $post_type, $slug ) {
	$found = get_posts(
		array(
			'post_type'        => $post_type,
			'name'             => $slug,
			'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page'   => 1,
			'suppress_filters' => true,
		)
	);
	return $found ? $found[0] : null;
}

/**
 * Collect a post and, first, every post its ACF fields reference.
 *
 * @param int             $id      Post ID.
 * @param array<int,bool> $seen    Visited IDs.
 * @param array<int,int>  $ordered Output, references first.
 * @return void
 */
function wt_bundle_collect( $id, &$seen, &$ordered ) {
	if ( isset( $seen[ $id ] ) ) {
		return;
	}
	$seen[ $id ] = true;

	foreach ( get_post_meta( $id ) as $key => $values ) {
		$field = wt_bundle_field_for( $id, $key );
		if ( $field && 'post' === wt_bundle_ref_kind( $field['type'] ) ) {
			foreach ( wt_bundle_ref_ids( maybe_unserialize( $values[0] ) ) as $ref ) {
				if ( get_post( $ref ) ) {
					wt_bundle_collect( $ref, $seen, $ordered );
				}
			}
		}
	}

	$ordered[] = $id;
}

/**
 * The ACF field a meta row belongs to, via its `_`-prefixed key reference.
 *
 * @param int    $id  Post ID.
 * @param string $key Meta key.
 * @return array<string,mixed>|null
 */
function wt_bundle_field_for( $id, $key ) {
	if ( '_' === $key[0] || ! function_exists( 'acf_get_field' ) ) {
		return null;
	}
	$field_key = get_post_meta( $id, '_' . $key, true );
	if ( ! is_string( $field_key ) || 0 !== strpos( $field_key, 'field_' ) ) {
		return null;
	}
	$field = acf_get_field( $field_key );
	return $field ? $field : null;
}

/**
 * Serialise one post for the bundle.
 *
 * @param WP_Post $post Post.
 * @param string  $home Source home URL.
 * @return array<string,mixed>
 */
function wt_bundle_export_post( $post, $home ) {
	$meta = array();
	foreach ( get_post_meta( $post->ID ) as $key => $values ) {
		if ( in_array( $key, WT_BUNDLE_LOCAL_META, true ) ) {
			continue;
		}
		$field = wt_bundle_field_for( $post->ID, $key );
		$kind  = '_thumbnail_id' === $key ? 'attachment' : ( $field ? wt_bundle_ref_kind( $field['type'] ) : null );

		foreach ( $values as $raw ) {
			$value = maybe_unserialize( $raw );
			if ( ! $kind ) {
				$meta[] = array(
					'key'   => $key,
					'value' => wt_bundle_tokenize_urls( $value, $home ),
				);
				continue;
			}

			$targets = array();
			foreach ( wt_bundle_ref_ids( $value ) as $ref ) {
				$target = wt_bundle_describe_ref( $kind, $ref, $field );
				if ( $target ) {
					$targets[] = $target;
				}
			}
			$meta[] = array(
				'key'   => $key,
				'value' => null,
				'ref'   => $kind,
				'shape' => is_array( $value ) ? 'array' : 'scalar',
				'items' => $targets,
			);
		}
	}

	$terms = array();
	foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
		$slugs = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'slugs' ) );
		if ( ! is_wp_error( $slugs ) && $slugs ) {
			$terms[ $taxonomy ] = $slugs;
		}
	}

	return array(
		'post_type'      => $post->post_type,
		'post_name'      => $post->post_name,
		'post_title'     => $post->post_title,
		'post_content'   => wt_bundle_tokenize_urls( $post->post_content, $home ),
		'post_excerpt'   => wt_bundle_tokenize_urls( $post->post_excerpt, $home ),
		'post_status'    => $post->post_status,
		'menu_order'     => $post->menu_order,
		// The parent's full path, so a nested page keeps its URL on the target.
		'parent'         => $post->post_parent ? array(
			'post_type' => get_post_type( $post->post_parent ),
			'path'      => get_page_uri( $post->post_parent ),
		) : null,
		'comment_status' => $post->comment_status,
		'ping_status'    => $post->ping_status,
		'terms'          => (object) $terms,
		'meta'           => $meta,
	);
}

/**
 * Describe a referenced object portably.
 *
 * @param string                   $kind  'post', 'attachment' or 'term'.
 * @param int                      $id    Object ID on the source site.
 * @param array<string,mixed>|null $field ACF field (for a term's taxonomy).
 * @return array<string,string>|null
 */
function wt_bundle_describe_ref( $kind, $id, $field ) {
	if ( 'attachment' === $kind ) {
		$file = get_post_meta( $id, '_wp_attached_file', true );
		return $file ? array( 'file' => $file ) : null;
	}
	if ( 'term' === $kind ) {
		$term = get_term( $id );
		return ( $term && ! is_wp_error( $term ) ) ? array(
			'taxonomy' => $term->taxonomy,
			'slug'     => $term->slug,
		) : null;
	}
	$post = get_post( $id );
	return $post ? array(
		'post_type' => $post->post_type,
		'slug'      => $post->post_name,
	) : null;
}

/**
 * Turn a bundle reference row back into a value with this site's IDs.
 *
 * In a dry run, posts the bundle would already have created count as found.
 *
 * @param array<string,mixed> $row     Meta row with `ref`, `shape`, `items`.
 * @param array<int,string>   $missing Out: labels of targets not found.
 * @param array<int,string>   $planned `type/slug` of posts a dry run would have created.
 * @return mixed
 */
function wt_bundle_resolve_ref( $row, &$missing, $planned ) {
	global $wpdb;

	$missing = array();
	$ids     = array();

	foreach ( $row['items'] as $item ) {
		$id = 0;
		if ( 'attachment' === $row['ref'] ) {
			$id    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1", $item['file'] ) );
			$label = 'upload ' . $item['file'];
		} elseif ( 'term' === $row['ref'] ) {
			$term  = get_term_by( 'slug', $item['slug'], $item['taxonomy'] );
			$id    = $term ? $term->term_id : 0;
			$label = 'term ' . $item['taxonomy'] . '/' . $item['slug'];
		} else {
			$post  = wt_bundle_find_post( $item['post_type'], $item['slug'] );
			$id    = $post ? $post->ID : 0;
			$label = $item['post_type'] . '/' . $item['slug'];
			if ( ! $id && in_array( $label, $planned, true ) ) {
				$id = -1; // Would be created earlier in this dry run.
			}
		}

		if ( $id ) {
			$ids[] = (string) $id;
		} else {
			$missing[] = $label;
		}
	}

	if ( 'array' === $row['shape'] ) {
		return $ids;
	}
	return $ids ? $ids[0] : '';
}

WP_CLI::add_command( 'wt content-export', 'wt_content_export' );
WP_CLI::add_command( 'wt content-import', 'wt_content_import' );
