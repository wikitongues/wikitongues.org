<?php 

// header
get_header(); // do pseudo code

// banner
include( 'modules/banner.php' );

// initiate flexible content loop
if ( have_rows( 'front_page_content_layout' ) ) {

	// loop through flexible content field for layout options
	while ( have_rows( 'front_page_content_layout') ) {

		// the layout object
		the_row();

		if ( get_row_layout() == 'thumbnail_carousel' ) {

			$post_type == get_sub_field('thumbnail_carousel_posts');

			include( 'modules/carousel--thumbnail.php' );

			echo "hello? carousel";

		} elseif ( get_row_layout() == 'content_block' ) {

			include( 'modules/content-block--wide.php' );

			echo "hello? content block";

		} elseif ( get_row_layout() == 'testimonial' ) {

			include( 'modules/carousel--testimonial.php' );

			echo "hello? testimonial";
		}
	}
}

// footer
get_footer();