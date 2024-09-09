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

		if ( get_row_layout() == 'thumbnail_carousel' ) {

			include( 'modules/carousel--thumbnail.php' );

		} elseif ( get_row_layout() == 'content_block' ) {

			include( 'modules/content-block--wide.php' );

		} elseif ( get_row_layout() == 'testimonial' ) {

			include( 'modules/carousel--testimonial.php' );

		} elseif ( get_row_layout() == 'custom_gallery_posts' && $row_id === 'archive') {
			$custom_posts = get_sub_field('custom_gallery_post');

			if ($custom_posts) {
				$post_ids = implode(',', wp_list_pluck($custom_posts, 'ID'));
			}
			$custom_title = get_sub_field('custom_gallery_title');
			$custom_post_type = 'videos';
			$custom_class = 'full home';
			$custom_columns = 4;
			$custom_posts_per_page = 4;
			$custom_orderby = 'rand';
			$custom_order = 'asc';
			$custom_pagination = 'false';
			$custom_meta_key = '';
			$custom_meta_value = '';
			$custom_selected_posts = esc_attr($post_ids);
			echo do_shortcode('[custom_gallery title="'.$custom_title.'" custom_class="'.$custom_class.'" post_type="'.$custom_post_type.'" columns="'.$custom_columns.'" posts_per_page="'.$custom_posts_per_page.'" orderby="'.$custom_orderby.'" order="'.$custom_order.'" pagination="'.$custom_pagination.'" meta_key="'.$custom_meta_key.'" meta_value="'.$custom_meta_value.'" selected_posts="'.$custom_selected_posts.'"]');
		} elseif ( get_row_layout() == 'custom_gallery_posts' && $row_id === 'fellows') {
			$custom_posts = get_sub_field('custom_gallery_post');

			if ($custom_posts) {
				$post_ids = implode(',', wp_list_pluck($custom_posts, 'ID'));
			}
			$custom_title = get_sub_field('custom_gallery_title');
			$custom_post_type = 'fellows';
			$custom_class = 'full home';
			$custom_columns = 4;
			$custom_posts_per_page = 4;
			$custom_orderby = 'rand';
			$custom_order = 'asc';
			$custom_pagination = 'false';
			$custom_meta_key = '';
			$custom_meta_value = '';
			$custom_selected_posts = esc_attr($post_ids);
			echo do_shortcode('[custom_gallery title="'.$custom_title.'" custom_class="'.$custom_class.'" post_type="'.$custom_post_type.'" columns="'.$custom_columns.'" posts_per_page="'.$custom_posts_per_page.'" orderby="'.$custom_orderby.'" order="'.$custom_order.'" pagination="'.$custom_pagination.'" meta_key="'.$custom_meta_key.'" meta_value="'.$custom_meta_value.'" selected_posts="'.$custom_selected_posts.'"]');
		}
	}
}

// footer
get_footer();