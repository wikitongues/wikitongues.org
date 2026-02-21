<?php
add_action( 'wp_enqueue_scripts', 'enqueue_mobile_accordion_script' );
function enqueue_mobile_accordion_script() {
	if ( is_singular( 'languages' ) ) {
		wp_enqueue_script( 'mobile-accordion-helper', get_stylesheet_directory_uri() . '/js/mobile-accordion-helper.js', array( 'jquery' ), null, true );
	}
}

$standard_name = get_field( 'standard_name' );
$language      = get_the_ID();

// ====================
// Manage Language Page Titles
// ====================
if ( is_singular( 'languages' ) ) {
	if ( $standard_name ) {
		echo '<script>document.title = "Wikitongues | ' . esc_js( $standard_name ) . '";</script>';
	}
}

get_header();

require 'modules/languages/meta--languages-single.php';

echo '<main class="wt_single-languages__content">';
require 'modules/languages/single-languages__fellows.php';
require 'modules/languages/single-languages__videos.php';
require 'modules/languages/single-languages__lexicons.php';
require 'modules/languages/single-languages__resources.php';
echo '</main>';

// ====================
// Gallery — other languages from the same territory
// ====================
$territories = get_field( 'territories' );
if ( $territories ) {
	$primary_territory = $territories[0];
	$territory_slug    = $primary_territory->post_name;
	$territory_name    = wt_prefix_the( $primary_territory->post_title );
	$language_ids      = get_field( 'languages', $primary_territory->ID, false );
	$selected          = $language_ids ? implode( ',', $language_ids ) : '';
	$params            = array(
		'title'          => 'Other languages from ' . $territory_name,
		'subtitle'       => '',
		'show_total'     => 'true',
		'post_type'      => 'languages',
		'custom_class'   => 'full',
		'columns'        => 5,
		'posts_per_page' => 5,
		'orderby'        => 'rand',
		'order'          => 'asc',
		'pagination'     => 'true',
		'meta_key'       => '',
		'meta_value'     => '',
		'selected_posts' => $selected,
		'display_blank'  => 'false',
		'exclude_self'   => 'true',
		'taxonomy'       => '',
		'term'           => '',
		'link_out'       => add_query_arg( 'territory', $territory_slug, get_post_type_archive_link( 'languages' ) ),
	);
	echo create_gallery_instance( $params );
}

// other posts (revitalization projects, translation/etc, learning options) - add in later version

require 'modules/newsletter.php';

get_footer();
