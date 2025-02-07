<?php /* Template name: Editorial Page */

get_header();

	if( have_rows('main_content') ):
		while( have_rows('main_content') ) : the_row();

			// Determine the current layout.
			$layout = get_row_layout();

			// Load a partial based on the layout.
			if( $layout == 'text_layout' ):
				include( 'modules/flexible-content--text-layout.php' );
			elseif( $layout == 'banner_layout' ):
				$page_banner = get_sub_field('banner');
				include( 'modules/banner--main.php' );
			elseif( $layout == 'video_layout' ):
				include( 'modules/flexible-content--video-layout.php' );
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

include( 'modules/newsletter.php' );

get_footer();
