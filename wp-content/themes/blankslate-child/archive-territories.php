<?php
get_header();

$region_slug = isset( $_GET['region'] ) ? sanitize_title( wp_unslash( $_GET['region'] ) ) : '';
$region_term = $region_slug ? get_term_by( 'slug', $region_slug, 'region' ) : null;

echo '<main class="wt_archive-territories">';

if ( $region_term ) {
	$region_name = wt_prefix_the( $region_term->name );
	$params      = wt_gallery_params(
		array(
			'title'         => 'Territories of ' . $region_name,
			'post_type'     => 'territories',
			'display_blank' => 'true',
			'taxonomy'      => 'region',
			'term'          => $region_term->slug,
		)
	);
} else {
	$params = wt_gallery_params(
		array(
			'title'     => 'Territories',
			'post_type' => 'territories',
		)
	);
}

echo create_gallery_instance( $params );
echo '</main>';
require 'modules/newsletter.php';
get_footer();
