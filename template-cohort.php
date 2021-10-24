<?php /* Template name: Cohort */

// header
get_header();

// cohort banner
$banner_image = get_field('banner_image');
$banner_header = get_field('banner_header');
$banner_copy = get_field('banner_copy');

include( locate_template('modules/banner.php') );

// signup cta
include( locate_template('modules/cohort-actions.php') );

// testimonials
$testimonials = get_field('testimonials');

foreach( $testimonials as $post ) {
	setup_postdata( $post );

	$testimonial_photo = $post['testimonial_photo'];
	$testimonial_copy = $post['testimonial_copy'];
	$testimonial_byline = $post['testimonial_byline'];

	include( locate_template('modules/testimonials.php') );
} wp_reset_postdata();

// footer
get_footer();