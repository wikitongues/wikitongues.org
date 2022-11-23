<?php /* Template name: Donate */

// header
get_header();

// vars
$banner_image = get_field('banner_image');
$banner_header = get_field('banner_header');
$banner_copy = get_field('banner_copy');
$donate_header = get_field('donate_header');
$donate_form_embed = get_field('donate_form_embed');
$donate_address = get_field('donate_address');
$donate_content = get_field('donate_content');
$case_studies = get_field('case_studies');

// banner
include( locate_template('modules/banner.php') );

// initiate main content
echo '<main class="wt_wrapper">';

// donate information
include( locate_template('modules/donate-content.php') );

// case studies
if( $case_studies ){

	echo '<div class="wt_donate__casestudies">'.
		 '<h1>Case Studies</h1>'.
		 '<h2>Learn about projects we\'ve supported</h2>'.
		 '<ul>';

	foreach( $case_studies as $post ){
		setup_postdata( $post );

		include( locate_template('modules/donate-grantee-thumbnail.php') );
	}

	echo '</ul>';

	wp_reset_postdata();
}

// donate 

// start a fundraiser

// end main content
echo '</main>';

get_footer();