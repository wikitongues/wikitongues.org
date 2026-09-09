<?php
/**
 * Gallery item for the People post type.
 *
 * Rather than duplicate the member markup, this prepares the variables the
 * theme's person modules expect and hands off to them, so the People template
 * and any other gallery render identically.
 *
 * `custom_class` picks the render mode, matching how gallery-fellows.php
 * branches: 'grid' for the compact card, anything else for the wide profile.
 */

$profile_picture    = get_field( 'profile_picture' );
$name               = get_the_title();
$bio                = get_field( 'bio' );
$location           = get_field( 'contributor_location' );
$personal_languages = get_field( 'languages' );

// Shadows the $title that render_gallery_items() sets from get_custom_title():
// the person modules use $title for the person's role, not the post title.
$title = get_field( 'leadership_title' );

$social_links = function_exists( 'wt_social_links' ) ? wt_social_links() : array();

$class = isset( $atts['custom_class'] ) ? $atts['custom_class'] : '';
$mode  = 'wide';
foreach ( array( 'grid', 'list' ) as $candidate ) {
	if ( strpos( $class, $candidate ) !== false ) {
		$mode = $candidate;
		break;
	}
}

$module = locate_template( 'modules/people/person--' . $mode . '.php' );

echo '<li class="gallery-item gallery-item--person">';
if ( $module ) {
	include $module;
}
echo '</li>';
