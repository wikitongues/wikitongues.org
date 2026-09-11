<?php
/**
 * WP-CLI: move fellow-category banners out of editorial content.
 *
 * Category banners were stored as a single `banner_layout` row inside each
 * term's editorial `main_content`. taxonomy-fellow-category.php now renders the
 * banner from the term's own `revitalization_fellows_banner` group instead,
 * which frees `main_content` for a block at the foot of the page.
 *
 * This command copies the stored banner across and then clears `main_content`
 * so that freed slot starts empty — otherwise the old banner row would render a
 * second time below the gallery.
 *
 * @package Wikitongues
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Move each fellow-category term's banner into `revitalization_fellows_banner`.
 *
 * Idempotent: a term whose target group already holds an image is left alone,
 * so the command is safe to re-run on localhost, staging and production.
 *
 * ## OPTIONS
 *
 * [--execute]
 * : Write the changes. Without it the command only reports what it would do.
 *
 * [--term=<slug>]
 * : Limit the run to a single fellow-category term.
 *
 * ## EXAMPLES
 *
 *     wp wt migrate-fellow-category-banner --allow-root
 *     wp wt migrate-fellow-category-banner --execute --allow-root
 *
 * @param array<int,string>    $args       Positional arguments (unused).
 * @param array<string,string> $assoc_args Associative arguments.
 * @return void
 */
function wt_migrate_fellow_category_banner( $args, $assoc_args ) {
	$execute   = isset( $assoc_args['execute'] );
	$only_slug = isset( $assoc_args['term'] ) ? $assoc_args['term'] : '';

	if ( ! function_exists( 'acf_get_field' ) ) {
		WP_CLI::error( 'ACF is not active.' );
	}

	// Read the target field definition rather than hardcoding keys, so the
	// reference meta this writes can never drift from the field group JSON.
	$group = acf_get_field( 'revitalization_fellows_banner' );
	if ( ! $group || empty( $group['sub_fields'] ) ) {
		WP_CLI::error( 'Field `revitalization_fellows_banner` is not registered — sync the field group first.' );
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'fellow-category',
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) ) {
		WP_CLI::error( $terms->get_error_message() );
	}

	WP_CLI::log( $execute ? 'Migrating fellow-category banners.' : 'Dry run — pass --execute to write.' );

	$moved   = 0;
	$cleared = 0;
	$skipped = 0;

	foreach ( $terms as $term ) {
		if ( $only_slug && $term->slug !== $only_slug ) {
			continue;
		}

		$acf_id = 'term_' . $term->term_id;

		// The source is only ever a lone banner row; anything else is real
		// editorial content and must be left alone.
		$source_is_banner = array( 'banner_layout' ) === get_term_meta( $term->term_id, 'main_content', true );
		$target_populated = (bool) get_term_meta( $term->term_id, 'revitalization_fellows_banner_banner_image', true );

		if ( ! $source_is_banner ) {
			WP_CLI::log(
				sprintf(
					'  skip       %s — %s',
					$term->slug,
					$target_populated ? 'already migrated' : 'main_content is not a single banner_layout row'
				)
			);
			++$skipped;
			continue;
		}

		$values = array();
		foreach ( $group['sub_fields'] as $sub ) {
			$values[ $sub['name'] ] = get_term_meta( $term->term_id, 'main_content_0_banner_' . $sub['name'], true );
		}

		/*
		 * A term whose target is already populated still needs its source row
		 * cleared — otherwise the old banner renders a second time in the
		 * editorial slot now sitting below the gallery.
		 */
		WP_CLI::log(
			sprintf(
				'  %s %s — %s',
				$execute ? 'write     ' : 'would write',
				$term->slug,
				$target_populated
					? 'target already set; clearing source row only'
					: sprintf( 'image %s, caption %s', $values['banner_image'] ?: '(none)', $values['display_caption'] ?: '(none)' )
			)
		);

		if ( $execute ) {
			if ( ! $target_populated ) {
				// update_field() writes both the values and the `_`-prefixed
				// field-key references, reproducing what an admin save produces.
				update_field( $group['key'], $values, $acf_id );
			}
			delete_field( 'main_content', $acf_id );
			wt_delete_orphan_term_meta( $term->term_id, 'main_content' );
		}

		if ( $target_populated ) {
			++$cleared;
		} else {
			++$moved;
		}
	}

	WP_CLI::success(
		sprintf(
			'%s %d banner(s), cleared %d stale source row(s), skipped %d.',
			$execute ? 'Migrated' : 'Would migrate',
			$moved,
			$cleared,
			$skipped
		)
	);
}

/**
 * Delete leftover flexible-content row meta for a term.
 *
 * delete_field() clears the field and its reference, but ACF can leave the
 * per-row sub-values behind. Sweep anything still prefixed with the field name.
 *
 * @param int    $term_id Term ID.
 * @param string $field   Flexible content field name.
 * @return int Rows deleted.
 */
function wt_delete_orphan_term_meta( $term_id, $field ) {
	global $wpdb;

	return (int) $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->termmeta} WHERE term_id = %d AND ( meta_key LIKE %s OR meta_key LIKE %s )",
			$term_id,
			$wpdb->esc_like( $field . '_' ) . '%',
			$wpdb->esc_like( '_' . $field . '_' ) . '%'
		)
	);
}

WP_CLI::add_command( 'wt migrate-fellow-category-banner', 'wt_migrate_fellow_category_banner' );
