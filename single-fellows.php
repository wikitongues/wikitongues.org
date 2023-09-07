<?php

// header
get_header();

// banner
$page_banner = get_field('fellow_banner');

include( 'modules/banner--main.php' );

// fellow meta
$fellow_year;
$fellow_website;
$fellow_email;
$fellow_twitter;
$fellow_instagram;
$fellow_facebook;
$fellow_linkedin;

include( 'modules/meta--fellows-single' );

// fellow narrative/content
include( 'modules/main-content.php' );

// loop through other fellows
// include( 'modules/carousal--thumbnail.php' );

get_footer();