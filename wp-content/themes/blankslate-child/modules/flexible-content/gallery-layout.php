<?php
if ( have_rows( 'custom_gallery_posts' ) ) {
	while ( have_rows( 'custom_gallery_posts' ) ) {
		the_row();
		$custom_posts = get_sub_field( 'custom_gallery_post' );

		if ( $custom_posts ) {
			$post_ids = implode( ',', wp_list_pluck( $custom_posts, 'ID' ) );
		}

		// Gallery
		$params = wt_gallery_params(
			array(
				'title'          => get_sub_field( 'custom_gallery_title' ),
				'post_type'      => get_sub_field( 'custom_gallery_type' ),
				'custom_class'   => 'full',
				'show_total'     => 'false',
				'columns'        => get_sub_field( 'custom_gallery_columns' ),
				'posts_per_page' => get_sub_field( 'custom_gallery_posts_per_page' ),
				'orderby'        => 'rand',
				'pagination'     => get_sub_field( 'custom_gallery_paginate' ),
				'selected_posts' => esc_attr( $post_ids ),
				'exclude_self'   => 'true',
			)
		);
		echo create_gallery_instance( $params );
	}
}
