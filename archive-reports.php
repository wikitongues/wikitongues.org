<?php

// header
get_header();

$page_header = 'Wikitongues <br/> Reports';
$page_subhead = 'Monthly updates about our projects and financial position';

include( locate_template('modules/banner-short.php') );

// loop
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post(); 

		include( locate_template('modules/report.php') );
	}
}

// footer
get_footer();