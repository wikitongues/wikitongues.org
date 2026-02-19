<?php /* Template name: Board and Advisors */

// header
get_header();

// team banner
$team_banner = get_field( 'team_banner' );

require 'modules/banners/banner--team.php';

// define team member posts to display
$board_members = get_field( 'board_members' );

// cycle through selected posts
echo "<div class='wrapper'>";
foreach ( $board_members as $post ) {
	// setup post data for each post
	setup_postdata( $post );

	$profile_picture    = get_field( 'profile_picture' );
	$name               = get_the_title();
	$title              = get_field( 'leadership_title' );
	$bio                = get_field( 'bio' );
	$location           = get_field( 'contributor_location' );
	$personal_languages = get_field( 'languages' );
	$social_links       = array(
		'email'     => array(
			'url'  => get_field( 'email' ),
			'icon' => 'fa-solid fa-envelope',
		),
		'facebook'  => array(
			'url'  => get_field( 'facebook' ),
			'icon' => 'fa-brands fa-square-facebook',
		),
		'instagram' => array(
			'url'  => get_field( 'instagram' ),
			'icon' => 'fa-brands fa-instagram',
		),
		'linkedin'  => array(
			'url'  => get_field( 'linkedin' ),
			'icon' => 'fa-brands fa-linkedin',
		),
		'tiktok'    => array(
			'url'  => get_field( 'tiktok' ),
			'icon' => 'fa-brands fa-tiktok',
		),
		'twitter'   => array(
			'url'  => get_field( 'twitter' ),
			'icon' => 'fa-brands fa-x-twitter',
		),
		'website'   => array(
			'url'  => get_field( 'website' ),
			'icon' => 'fa-solid fa-link',
		),
		'youtube'   => array(
			'url'  => get_field( 'youtube' ),
			'icon' => 'fa-brands fa-youtube',
		),
	);


	// show board member module
	include 'modules/team/team-member--wide.php';

} wp_reset_postdata();
echo '</div>';

require 'modules/newsletter.php';

get_footer();
