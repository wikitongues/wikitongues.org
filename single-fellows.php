<?php

// header
get_header();

// banner
$page_banner = get_field('fellow_banner');

include( 'modules/banner--main.php' );

// fellow meta
$first_name = get_field('first_name');
$last_name = get_field('last_name');
$fellow_name = $first_name . ' ' . $last_name;
$fellow_year = get_field('fellow_year');
$fellow_language = get_field('fellow_language');
$fellow_language_preferred_name = get_field('fellow_language_preferred_name');
$website = get_field('website');
$email = get_field('email');
$twitter = get_field('twitter');
$instagram = get_field('instagram');
$facebook = get_field('facebook');
$linkedin = get_field('linkedin');
$youtube = get_field('youtube');
$tiktok = get_field('tiktok');

include( 'modules/meta--fellows-single.php' );

// fellow narrative/content
include( 'modules/main-content.php' );

// fellow bio
$fellow_bio = get_field('fellow_bio');

if ( $fellow_bio ) {

	include( 'modules/fellow-bio.php');

}

// loop through other fellows
// include( 'modules/carousal--thumbnail.php' );

get_footer();