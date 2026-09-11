<?php
if ( have_rows( 'custom_gallery_posts' ) ) {
	while ( have_rows( 'custom_gallery_posts' ) ) {
		the_row();
		$post_type    = get_sub_field( 'custom_gallery_type' );
		$custom_posts = get_sub_field( 'custom_gallery_post' );
		$post_ids     = $custom_posts ? implode( ',', wp_list_pluck( $custom_posts, 'ID' ) ) : '';

		// Rows saved before these fields existed have no stored value, so fall
		// back to what this module used to hardcode.
		$orderby      = get_sub_field( 'custom_gallery_orderby' ) ?: 'rand';
		$custom_class = 'full';
		$taxonomy     = '';
		$term         = '';

		// People pages compose their sections here: each row is a type-filtered
		// (or hand-picked) gallery, rendered wide or as a grid.
		if ( 'people' === $post_type ) {
			$custom_class = get_sub_field( 'custom_gallery_layout' ) ?: 'wide';
			$terms        = get_sub_field( 'custom_gallery_people_type' );
			if ( $terms ) {
				$taxonomy = 'people-type';
				$term     = implode( ',', wp_list_pluck( $terms, 'slug' ) );
			}
		} elseif ( 'careers' === $post_type ) {
			$terms = get_sub_field( 'custom_gallery_career_type' );
			if ( $terms ) {
				$taxonomy = 'career_type';
				$term     = implode( ',', wp_list_pluck( $terms, 'slug' ) );
			}
		}

		// Gallery
		$params = wt_gallery_params(
			array(
				'title'          => get_sub_field( 'custom_gallery_title' ),
				'post_type'      => $post_type,
				'custom_class'   => $custom_class,
				'show_total'     => 'false',
				'columns'        => get_sub_field( 'custom_gallery_columns' ),
				'posts_per_page' => get_sub_field( 'custom_gallery_posts_per_page' ),
				'orderby'        => $orderby,
				'pagination'     => get_sub_field( 'custom_gallery_paginate' ),
				'selected_posts' => esc_attr( $post_ids ),
				'taxonomy'       => $taxonomy,
				'term'           => $term,
				'exclude_self'   => 'true',
			)
		);
		echo create_gallery_instance( $params );
	}
}
