<?php /* Template name: Editorial Page */

get_header();

	if( have_rows('main_content') ):
		while( have_rows('main_content') ) : the_row();

			// Determine the current layout.
			$layout = get_row_layout();

			// Load a partial based on the layout.
			if( $layout == 'text_layout' ):
				// get_template_part('template-parts/flexible/hero');
				echo '<section class="main-content">';
				echo wpautop(wp_kses_post(get_sub_field('text_area')));
				echo '</section>';

			elseif( $layout == 'banner_layout' ):
				$page_banner = get_sub_field('banner');
				include( 'modules/banner--main.php' );

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
								'display_blank' => '',
								'taxonomy' => '',
								'term' => '',
							];
							echo create_gallery_instance($params);
					}
				};
			elseif( $layout == 'video_layout' ):
				$video = get_sub_field('video');
				$video_title = get_sub_field('video_title');
				$dropbox_link_raw = str_replace("dl=0", "raw=1", $video);
				if ( $dropbox_link_raw ) {
					?>
					<div class="wt_single-videos__embed">
						<video width="320" height="240" controls>
							<source src="<?php echo $dropbox_link_raw ?>" type="video/mp4">Your browser does not support the video tag.
						</video>
						<?php
					if ( $video_title ) {
						echo '<h3>' . $video_title . '</h3>';
					};
					echo '</div>';
				};
			endif;

		endwhile;
	endif;

include( 'modules/newsletter.php' );

get_footer();
