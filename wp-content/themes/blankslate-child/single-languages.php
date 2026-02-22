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
// Gallery — other languages from the same territory/territories
// ====================
$territories = get_field( 'territories' );
if ( $territories ) {
	// Collect language IDs from every territory the current language belongs to.
	$language_ids = array();
	foreach ( $territories as $territory ) {
		$ids = get_field( 'languages', $territory->ID, false );
		if ( $ids ) {
			$language_ids = array_merge( $language_ids, $ids );
		}
	}
	$language_ids = array_unique( $language_ids );
	$selected     = implode( ',', $language_ids );

	// Build territory name list with Oxford comma (e.g. "Dominica, Saint Kitts and Nevis, and United Kingdom").
	$territory_names = array_map(
		function ( $t ) {
			return wt_prefix_the( $t->post_title );
		},
		$territories
	);
	$territory_count = count( $territory_names );
	if ( $territory_count === 1 ) {
		$territory_label = $territory_names[0];
	} elseif ( $territory_count === 2 ) {
		$territory_label = $territory_names[0] . ' and ' . $territory_names[1];
	} else {
		$last            = array_pop( $territory_names );
		$territory_label = implode( ', ', $territory_names ) . ', and ' . $last;
	}
	$gallery_title    = 'Other languages from ' . $territory_label;
	$gallery_link_out = $territory_count === 1
		? add_query_arg( 'territory', $territories[0]->post_name, get_post_type_archive_link( 'languages' ) )
		: '';

	$params = array(
		'title'          => $gallery_title,
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
		'link_out'       => $gallery_link_out,
	);
	echo create_gallery_instance( $params );
}

// other posts (revitalization projects, translation/etc, learning options) - add in later version

require 'modules/newsletter.php';

get_footer();
