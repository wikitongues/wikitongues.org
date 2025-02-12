<?php
if( have_rows('main_content') ):
	log_data("hi");
	while( have_rows('main_content') ) : the_row();
		// Determine the current layout.
		$layout = get_row_layout();
		// Load a partial based on the layout.
		if( $layout == 'text_layout' ):
			$image = get_sub_field('image');
			include( 'flexible-content--text-layout.php' );
		elseif( $layout == 'banner_layout' ):
			$page_banner = get_sub_field('banner');
			include( 'banner--main.php' );
		elseif( $layout == 'video_layout' ):
			include( 'flexible-content--video-layout.php' );
		elseif( $layout == 'link_group_layout' ):
			include( 'flexible-content--link-group-layout.php' );
		elseif( $layout == 'gallery_layout' ):
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
							'taxonomy' => '',
							'term' => '',
						];
						echo create_gallery_instance($params);
				}
			};
		endif;

	endwhile;
endif;