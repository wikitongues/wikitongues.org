<?php 

// header
get_header(); // do pseudo code

// banner
$front_page_banner = get_field('front_page_banner');
$banner_image = $front_page_banner['banner_image'];
$banner_header = $front_page_banner['banner_header'];
$banner_copy = $front_page_banner['banner_copy'];
$banner_CTA;

include( 'modules/banner.php' );

// initiate flexible content loop
if ( have_rows( 'front_page_content_layout' ) ) {

	// loop through flexible content field for layout options
	while ( have_rows( 'front_page_content_layout') ) {

		// the layout object
		the_row();

		if ( get_row_layout() == 'thumbnail_carousel' ) {

			include( 'modules/carousel--thumbnail.php' );	

		} elseif ( get_row_layout() == 'content_block' ) {

			include( 'modules/content-block--wide.php' );
	
		} elseif ( get_row_layout() == 'testimonial' ) {

			include( 'modules/carousel--testimonial.php' );

		}
	}
}

// footer
get_footer();