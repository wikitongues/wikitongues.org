<?php /* Template name: Staff */

// header
get_header();

// team banner
$team_banner = get_field( 'team_banner' );

require 'modules/banners/banner--team.php';

// define team member posts to display
$staff_members = get_field( 'staff_members' );

// cycle through     selected posts
echo '<div class="wrapper" id="staff">';
echo '<h4>Staff</h4>	';
foreach ( $staff_members as $post ) {
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
// Gallery
$careers = array(
	'title'          => 'Explore careers',
	'post_type'      => 'careers',
	'custom_class'   => '',
	'columns'        => 1,
	'posts_per_page' => 3,
	'orderby'        => 'rand',
	'order'          => 'asc',
	'pagination'     => 'false',
	'meta_key'       => '',
	'meta_value'     => '',
	'selected_posts' => '',
	'display_blank'  => 'false',
	'exclude_self'   => 'true',
	'taxonomy'       => 'career_type',
	'term'           => 'Staff',
	'link_out'       => '',
);
echo create_gallery_instance( $careers );

// Interns and Volunteers
$interns_and_volunteers = get_field( 'interns_and_volunteers' );
echo '<div class="wrapper" id="volunteers">';
echo '<h4>Interns and Volunteers</h4>	';
if ( $interns_and_volunteers ) {
	foreach ( $interns_and_volunteers as $post ) {
		setup_postdata( $post );
		$profile_picture = get_field( 'profile_picture' );
		$name            = get_the_title();
		$title           = get_field( 'leadership_title' );
		$location        = get_field( 'contributor_location' );
		$social_links    = array(
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
		include 'modules/team/team-member--grid.php';
	} wp_reset_postdata();
}
echo '</div>';
// Gallery
$otherOpportunities = array(
	'title'          => 'Explore other opportunities',
	'post_type'      => 'careers',
	'custom_class'   => '',
	'columns'        => 1,
	'posts_per_page' => 3,
	'orderby'        => 'rand',
	'order'          => 'asc',
	'pagination'     => 'false',
	'meta_key'       => '',
	'meta_value'     => '',
	'selected_posts' => '',
	'display_blank'  => 'false',
	'exclude_self'   => 'true',
	'taxonomy'       => 'career_type',
	'term'           => 'Intern,Volunteer',
	'link_out'       => '',
);
echo create_gallery_instance( $otherOpportunities );

require 'modules/newsletter.php';

get_footer();
