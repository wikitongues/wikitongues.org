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

	// show board member module
	include( 'modules/team-member--wide.php' );

} wp_reset_postdata();

get_footer();
