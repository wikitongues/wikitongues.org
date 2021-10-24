<?php 

// header
get_header();

// homepage banner
$banner_image = get_field('banner_image');
$banner_header = get_field('banner_header');
$banner_copy = get_field('banner_copy');

include( locate_template('modules/banner.php') );

// mission statement + metrics
$metrics_subhead = get_field('mission_statement');

include( locate_template('modules/metrics.php') );

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

// featured partner logos
$partners_header = get_field('partners_header');

include( locate_template('modules/partners.php') );

// footer
get_footer();