<?php /* Template name: Staff */

// header
get_header();

// team banner
$team_banner = get_field('team_banner');

include( 'modules/banner--team.php' );

// define team member posts to display
$staff_members = get_field('staff_members');

// cycle through	 selected posts
foreach( $staff_members as $post ) {
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
	$personal_languages = get_field('languages');

	// show board member module
	include( 'modules/team-member--wide.php' );

} wp_reset_postdata();

// define volunteer+intern team member posts to display acf

// cycle through selected posts
$interns_and_volunteers = get_field('interns_and_volunteers');

if ( $interns_and_volunteers ) {

	// cycle through selected posts
	foreach( $interns_and_volunteers as $post ) {
		// setup post data for each post
		setup_postdata( $post );

		// variables - would be cool to add a
		$profile_picture = get_field('profile_picture');
		$name = get_the_title();
		$title = get_field('leadership_title');
		$location = get_field('contributor_location');
		$linkedin = get_field('linkedin');
		$website = get_field('website');
		$twitter = get_field('twitter');
		$email = get_field('email');

		// show board member module
		include( 'modules/team-member--grid.php' );

	} wp_reset_postdata();

}

include( 'modules/newsletter.php' );

get_footer();
