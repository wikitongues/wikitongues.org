<?php
get_header();

$region_slug            = isset( $_GET['region'] ) ? sanitize_title( wp_unslash( $_GET['region'] ) ) : '';
$region_term            = $region_slug ? get_term_by( 'slug', $region_slug, 'region' ) : null;
$archive_columns        = 5;
$archive_posts_per_page = 100;

echo '<main class="wt_archive-territories">';

if ( $region_term ) {
	$region_name = wt_prefix_the( $region_term->name );
	$params      = array(
		'title'          => 'Territories of ' . $region_name,
		'subtitle'       => '',
		'show_total'     => 'true',
		'post_type'      => 'territories',
		'columns'        => $archive_columns,
		'posts_per_page' => $archive_posts_per_page,
		'orderby'        => 'title',
		'order'          => 'asc',
		'pagination'     => 'true',
		'meta_key'       => '',
		'meta_value'     => '',
		'selected_posts' => '',
		'display_blank'  => 'true',
		'exclude_self'   => 'false',
		'taxonomy'       => 'region',
		'term'           => $region_term->slug,
		'link_out'       => '',
	);
} else {
	$params = array(
		'title'          => 'Territories',
		'subtitle'       => '',
		'show_total'     => 'true',
		'post_type'      => 'territories',
		'columns'        => $archive_columns,
		'posts_per_page' => $archive_posts_per_page,
		'orderby'        => 'title',
		'order'          => 'asc',
		'pagination'     => 'true',
		'meta_key'       => '',
		'meta_value'     => '',
		'selected_posts' => '',
		'display_blank'  => 'false',
		'exclude_self'   => 'false',
		'taxonomy'       => '',
		'term'           => '',
		'link_out'       => '',
	);
}

echo create_gallery_instance( $params );
echo '</main>';
require 'modules/newsletter.php';
get_footer();
