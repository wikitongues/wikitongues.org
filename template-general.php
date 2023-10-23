<?php

/* Template name: General Page */

// header
get_header();

// splash banner
$banner_image = get_field('banner_image');
$banner_header = get_field('banner_header');
$banner_copy = get_field('banner_copy');

include( locate_template('modules/banner.php') );

// primary content
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();

		echo '<div class="wt_project__content">';
		
		the_content();
		
		echo '</div>';
	}
}

include ( locate_template('modules/general-actions.php') );

// footer
get_footer();