<?php /* Template name: Editorial Page */

get_header();

	if( have_rows('editorial_content') ):
		while( have_rows('editorial_content') ) : the_row();

			// Determine the current layout.
			$layout = get_row_layout();

			// Load a partial based on the layout.
			if( $layout == 'text_block' ):
				// get_template_part('template-parts/flexible/hero');
				echo '<section class="main-content">';
				echo wpautop(wp_kses_post(get_sub_field('text_area')));
				echo '</section>';

			elseif( $layout == 'banner' ):
				$page_banner = get_sub_field('banner');
				include( 'modules/banner--main.php' );

			elseif( $layout == 'gallery' ):
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
								'display_blank' => '',
								'taxonomy' => '',
								'term' => '',
							];
							echo create_gallery_instance($params);
					}
				}

			endif;

		endwhile;
	endif;

include( 'modules/newsletter.php' );

get_footer();
