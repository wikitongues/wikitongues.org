<?php

/* Template name: Accelerator Landing */

// header
get_header();

// splash banner
$banner_image = get_field('banner_image');
$banner_header = get_field('banner_header');
$banner_copy = get_field('banner_copy');

include( locate_template('modules/banner.php') );

include ( locate_template('modules/general-actions.php') );

// primary content
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();

		echo '<div class="wt_project__content">';
		
		the_content();
		
		echo '</div>';
	}
}

// footer
get_footer();