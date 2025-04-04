<?php
add_action( 'wp_enqueue_scripts', 'enqueue_mobile_accordion_script' );
function enqueue_mobile_accordion_script() {
	if ( is_singular('languages') ) {
		wp_enqueue_script( 'mobile-accordion-helper', get_stylesheet_directory_uri() . '/js/mobile-accordion-helper.js', array( 'jquery' ), null, true );
	}
}

$standard_name = get_field('standard_name');
$language = get_the_ID();
$videos = get_field('speakers_recorded');
$videos = is_array($videos) ? $videos : [];
$videos_count = count($videos);
$external_resources = get_field('resources');
$external_resources = is_array($external_resources) ? $external_resources : [];
$external_resources_count = count($external_resources);
$nations_of_origin = get_field('nations_of_origin');

// ====================
// Manage Language Page Titles
// ====================
if (is_singular('languages')) {
    if ($standard_name) {
        echo '<script>document.title = "Wikitongues | ' . esc_js($standard_name) . '";</script>';
    }
}

get_header();

include( 'modules/meta--languages-single.php' );

echo '<main class="wt_single-languages__content">';
include( 'modules/single-languages__fellows.php' );
include( 'modules/single-languages__videos.php' );
include( 'modules/single-languages__lexicons.php' );
include( 'modules/single-languages__resources.php' );
echo '</main>';

// ====================
// Gallery
// return empty if no nations of origin match
// ====================
$params = [
	'title' => 'Other languages from '.$nations_of_origin,
	'subtitle' => '',
	'post_type' => 'languages',
	'custom_class' => 'full',
	'columns' => 5,
	'posts_per_page' => 5,
	'orderby' => 'rand',
	'order' => 'asc',
	'pagination' => 'true',
	'meta_key' => 'nations_of_origin',
	'meta_value' => $nations_of_origin,
	'selected_posts' => '',
	'display_blank' => 'false',
	'exclude_self' => 'true',
	'taxonomy' => '',
	'term' => ''
];
echo create_gallery_instance($params);

// other posts (revitalization projects, translation/etc, learning options) - add in later version

include( 'modules/newsletter.php' );

get_footer();