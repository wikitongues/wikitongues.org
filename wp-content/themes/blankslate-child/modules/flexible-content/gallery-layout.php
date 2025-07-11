<?php
	if ( have_rows( 'custom_gallery_posts' ) ) {
		while ( have_rows( 'custom_gallery_posts') ) {
			the_row();
				$custom_posts = get_sub_field('custom_gallery_post');

				if ($custom_posts) {
					$post_ids = implode(',', wp_list_pluck($custom_posts, 'ID'));
				}

				// Gallery
				$params = [
					'title' => get_sub_field('custom_gallery_title'),
					'subtitle' => '',
					'show_total' => 'false',
					'post_type' => get_sub_field('custom_gallery_type'),
					'custom_class' => 'full',
					'columns' => get_sub_field('custom_gallery_columns'),
					'posts_per_page' => get_sub_field('custom_gallery_posts_per_page'),
					'orderby' => 'rand',
					'order' => 'asc',
					'pagination' => get_sub_field('custom_gallery_paginate'),
					'meta_key' => '',
					'meta_value' => '',
					'selected_posts' => esc_attr($post_ids),
					'display_blank' => 'false',
					'exclude_self' => 'true',
					'taxonomy' => '',
					'term' => '',
				];
				echo create_gallery_instance($params);
		}
	};