<?php /* Template name: Partners */ 

// header
get_header();

// team banner
$team_banner = get_field('team_banner');

include( 'modules/banner--team.php' );

// define partner posts to display (acf)
$partners = get_field('partners');

// cycle through selected posts
foreach( $partners as $post ) {
	// setup post data for each post
	setup_postdata( $post );

	$partner_logo = get_field('partner_logo');
	$name = get_the_title();
	$partner_bio = get_field('partner_bio');
	$partner_website = get_field('partner_website');
	$partner_email = get_field('partner_email');

	// show board member module
	include( 'modules/team-member--partner.php' );

} wp_reset_postdata();

get_footer();
