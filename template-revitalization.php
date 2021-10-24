<?php /* Template name: Language Revitalization */

// header
get_header();

// splash banner
$banner_image = get_field('banner_image');
$banner_header = get_field('banner_header');
$banner_copy = get_field('banner_copy');

include( locate_template('modules/banner.php') );

// testimonial
$testimonial_photo = get_field('revitalization_testimonial_photo');
$testimonial_copy = get_field('revitalization_testimonial_copy');
$testimonial_byline = get_field('revitalization_testimonial_byline');

if ( $testimonial_photo && $testimonial_copy && $testimonial_byline ) {
	include( locate_template('modules/testimonials.php') ); 
}

// primary content blocks
if ( have_rows('section') ) {
	while ( have_rows('section') ) {
		the_row();

		$section_image = get_sub_field('section_image');
		$section_header = get_sub_field('section_header');
		$section_copy = get_sub_field('section_copy');
		$section_call_to_action = get_sub_field('section_call_to_action');
		$section_secondary_action = get_sub_field('section_secondary_action'); 
		include( locate_template('modules/sections.php') );
	}
}

// footer
get_footer();