<?php
get_header();

$territory_slug = isset( $_GET['territory'] ) ? sanitize_title( wp_unslash( $_GET['territory'] ) ) : '';
$territory_post = $territory_slug ? get_page_by_path( $territory_slug, OBJECT, 'territories' ) : null;

echo '<main class="wt_archive-languages">';

if ( $territory_post ) {
	$territory_name = wt_prefix_the( $territory_post->post_title );
	// false = return raw IDs, not hydrated WP_Post objects (critical for large territories).
	$language_ids = get_field( 'languages', $territory_post->ID, false );
	$selected     = $language_ids ? implode( ',', $language_ids ) : '';

	$params = array(
		'title'          => 'Languages of ' . $territory_name,
		'subtitle'       => 'Wikitongues crowd-sources video samples of every language in the world.',
		'show_total'     => 'true',
		'post_type'      => 'languages',
		'columns'        => 4,
		'posts_per_page' => 12,
		'orderby'        => 'title',
		'order'          => 'asc',
		'pagination'     => 'true',
		'meta_key'       => '',
		'meta_value'     => '',
		'selected_posts' => $selected,
		'display_blank'  => 'true',
		'exclude_self'   => 'false',
		'taxonomy'       => '',
		'term'           => '',
	);
} else {
	$params = array(
		'title'          => 'Languages',
		'subtitle'       => 'Wikitongues crowd-sources video samples of every language in the world.',
		'show_total'     => 'true',
		'post_type'      => 'languages',
		'columns'        => 4,
		'posts_per_page' => 12,
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
	);
}

echo create_gallery_instance( $params );
echo '</main>';
require 'modules/newsletter.php';
get_footer();
