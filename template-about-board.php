<?php /* Template name: Board */ 

// header
get_header();

// team banner
include( 'modules/banner--team.php' );

// define team member posts to display
$board_members = get_field('board_members');

// cycle through selected posts
foreach( $board_members as $post ) {
	// setup post data for each post
	setup_postdata( $post );

	$profile_picture = get_field('profile_picture');
	$name = get_the_title();
	$title = get_field('leadership_title');
	$bio = get_field('bio');
	$location = get_field('contributor_location');
	$linkedin = get_field('linkedin');
	$website = get_field('website');
	$twitter = get_field('twitter');
	$email = get_field('email');

	// show board member module
	include( 'modules/team-member--wide.php' );

} wp_reset_postdata();

get_footer();
