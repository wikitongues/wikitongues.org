<?php

get_header();

// $page_header = 'Wikitongues <br/> Reports';
// $page_subhead = 'Monthly updates about our projects and financial position';

// reports single banner

// loop
if ( have_posts() ) {
	while ( have_posts() ) {

		the_post();

		$post_date = get_the_date( 'Y' );

		if ( $post_date > 2023 ) {
			include 'modules/single-reports__preview.php';
		}
	}
}

require 'modules/newsletter.php';

get_footer();
