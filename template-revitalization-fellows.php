<?php /* Template name: Revitalization Fellows */

// header
get_header();

// banner
$page_banner = get_field('revitalization_fellows_banner');

include( 'modules/banner.php' );

// foreach linked page, display 1/3 content block
$query = new WP_Query( 'post_type' => 'fellows' );

if ( $query->have_posts() ) {

	while ( $query->have_posts() ) {

		$query->the_post();
		
		include( 'modules/content-block--thirds' );
	}

	wp_reset_postdata();
}

// footer
get_footer();