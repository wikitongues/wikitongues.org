<?php /* Template name: Staff */

// header
get_header();

// team banner
$team_banner = get_field('team_banner');

include( 'modules/banner--team.php' );

// define team member posts to display
$staff_members = get_field('staff_members');

// cycle through	 selected posts
echo '<div class="wrapper" id="staff">';
echo '<h4>Staff</h4>	';
foreach( $staff_members as $post ) {
	// setup post data for each post
	setup_postdata( $post );

	$profile_picture = get_field('profile_picture');
	$name = get_the_title();
	$title = get_field('leadership_title');
	$bio = get_field('bio');
	$location = get_field('contributor_location');
	$personal_languages = get_field('languages');
	$social_links = [
		'email'     => ['url' => get_field('email'), 'icon' => 'fa-solid fa-envelope'],
		'facebook'  => ['url' => get_field('facebook'), 'icon' => 'fa-brands fa-square-facebook'],
		'instagram' => ['url' => get_field('instagram'), 'icon' => 'fa-brands fa-instagram'],
		'linkedin'  => ['url' => get_field('linkedin'), 'icon' => 'fa-brands fa-linkedin'],
		'tiktok'    => ['url' => get_field('tiktok'), 'icon' => 'fa-brands fa-tiktok'],
		'twitter'   => ['url' => get_field('twitter'), 'icon' => 'fa-brands fa-x-twitter'],
		'website'   => ['url' => get_field('website'), 'icon' => 'fa-solid fa-link'],
		'youtube'   => ['url' => get_field('youtube'), 'icon' => 'fa-brands fa-youtube']
	];

	// show board member module
	include( 'modules/team-member--wide.php' );

} wp_reset_postdata();
echo "</div>";
// Gallery
$careers = [
	'title' => 'Explore careers',
	'post_type' => 'careers',
	'custom_class' => '',
	'columns' => 1,
	'posts_per_page' => 3,
	'orderby' => 'rand',
	'order' => 'asc',
	'pagination' => 'false',
	'meta_key' => '',
	'meta_value' => '',
	'selected_posts' => '',
	'display_blank' => 'false',
	'taxonomy' => 'career_type',
	'term' => 'Staff'
];
echo create_gallery_instance($careers);

// Interns and Volunteers
$interns_and_volunteers = get_field('interns_and_volunteers');
echo '<div class="wrapper" id="volunteers">';
echo '<h4>Interns and Volunteers</h4>	';
if ( $interns_and_volunteers ) {
	foreach( $interns_and_volunteers as $post ) {
		setup_postdata( $post );
		$profile_picture = get_field('profile_picture');
		$name = get_the_title();
		$title = get_field('leadership_title');
		$location = get_field('contributor_location');
		$social_links = [
			'email'     => ['url' => get_field('email'), 'icon' => 'fa-solid fa-envelope'],
			'facebook'  => ['url' => get_field('facebook'), 'icon' => 'fa-brands fa-square-facebook'],
			'instagram' => ['url' => get_field('instagram'), 'icon' => 'fa-brands fa-instagram'],
			'linkedin'  => ['url' => get_field('linkedin'), 'icon' => 'fa-brands fa-linkedin'],
			'tiktok'    => ['url' => get_field('tiktok'), 'icon' => 'fa-brands fa-tiktok'],
			'twitter'   => ['url' => get_field('twitter'), 'icon' => 'fa-brands fa-x-twitter'],
			'website'   => ['url' => get_field('website'), 'icon' => 'fa-solid fa-link'],
			'youtube'   => ['url' => get_field('youtube'), 'icon' => 'fa-brands fa-youtube']
		];
		include( 'modules/team-member--grid.php' );
	} wp_reset_postdata();
}
echo "</div>";
// Gallery
$otherOpportunities = [
	'title' => 'Explore other opportunities',
	'post_type' => 'careers',
	'custom_class' => '',
	'columns' => 1,
	'posts_per_page' => 3,
	'orderby' => 'rand',
	'order' => 'asc',
	'pagination' => 'false',
	'meta_key' => '',
	'meta_value' => '',
	'selected_posts' => '',
	'display_blank' => 'false',
	'taxonomy' => 'career_type',
	'term' => 'Intern,Volunteer'
];
echo create_gallery_instance($otherOpportunities);

include( 'modules/newsletter.php' );

get_footer();
