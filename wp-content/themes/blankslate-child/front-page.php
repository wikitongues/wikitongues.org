<?php

// header
get_header();

// banner
$page_banner = get_field('front_page_banner');

include( 'modules/banner--main.php' );

// initiate flexible content loop
if ( have_rows( 'front_page_content_layout' ) ) {

	// loop through flexible content field for layout options
	while ( have_rows( 'front_page_content_layout') ) {

		// the layout object
		the_row();

		$row_id = get_sub_field('custom_gallery_id');

		if ( get_row_layout() == 'content_block' ) {
			include( 'modules/content-block--wide.php' );
		} elseif ( get_row_layout() == 'testimonial' ) {
			include( 'modules/carousel--testimonial.php' );
		} elseif ( get_row_layout() == 'custom_gallery_posts' && $row_id === 'archive') {
			$custom_posts = get_sub_field('custom_gallery_post');

			if ($custom_posts) {
				$post_ids = implode(',', wp_list_pluck($custom_posts, 'ID'));
			}
			// Gallery
			$params = [
				'title' => get_sub_field('custom_gallery_title'),
				'subtitle' => '',
				'post_type' => 'videos',
				'custom_class' => 'full home',
				'columns' => 4,
				'posts_per_page' => 4,
				'orderby' => 'rand',
				'order' => 'asc',
				'pagination' => 'false',
				'meta_key' => '',
				'meta_value' => '',
				'selected_posts' => esc_attr($post_ids),
				'display_blank' => 'false',
				'taxonomy' => '',
				'term' => ''
			];
			echo create_gallery_instance($params);

		} elseif ( get_row_layout() == 'custom_gallery_posts' && $row_id === 'fellows') {
			$custom_posts = get_sub_field('custom_gallery_post');

			if ($custom_posts) {
				$post_ids = implode(',', wp_list_pluck($custom_posts, 'ID'));
			}
			// Gallery
			$params = [
				'title' => get_sub_field('custom_gallery_title'),
				'subtitle' => '',
				'post_type' => 'fellows',
				'custom_class' => 'full home',
				'columns' => 4,
				'posts_per_page' => 4,
				'orderby' => 'rand',
				'order' => 'asc',
				'pagination' => 'false',
				'meta_key' => '',
				'meta_value' => '',
				'selected_posts' => esc_attr($post_ids),
				'display_blank' => 'false',
				'taxonomy' => '',
				'term' => ''
			];
			echo create_gallery_instance($params);
		}
	}
}

include( 'modules/social-proof.php' );

include( 'modules/newsletter.php' );

get_footer();