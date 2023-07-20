<?php

// header
get_header();

// $page_header = 'Wikitongues <br/> Reports';
// $page_subhead = 'Monthly updates about our projects and financial position';

// reports single banner

// loop
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post(); 

		// include modules report
	}
}

// footer
get_footer();