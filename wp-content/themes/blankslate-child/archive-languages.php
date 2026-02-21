<?php
get_header();

$territory_slug         = isset( $_GET['territory'] ) ? sanitize_title( wp_unslash( $_GET['territory'] ) ) : '';
$territory_post         = $territory_slug ? get_page_by_path( $territory_slug, OBJECT, 'territories' ) : null;
$genealogy              = isset( $_GET['genealogy'] ) ? sanitize_text_field( wp_unslash( $_GET['genealogy'] ) ) : '';
$genealogy_term         = $genealogy ? get_term_by( 'slug', $genealogy, 'linguistic-genealogy' ) : null;
$writing_system         = isset( $_GET['writing_system'] ) ? sanitize_text_field( wp_unslash( $_GET['writing_system'] ) ) : '';
$writing_system_term    = $writing_system ? get_term_by( 'slug', $writing_system, 'writing-system' ) : null;
$archive_columns        = 5;
$archive_posts_per_page = 100;
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
		'columns'        => $archive_columns,
		'posts_per_page' => $archive_posts_per_page,
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
		'link_out'       => '',
	);
} elseif ( $genealogy_term ) {
	$params = array(
		'title'          => $genealogy_term->name . ' linguistic family',
		'subtitle'       => 'Wikitongues crowd-sources video samples of every language in the world.',
		'show_total'     => 'true',
		'post_type'      => 'languages',
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
		'taxonomy'       => 'linguistic-genealogy',
		'term'           => $genealogy_term->slug,
		'link_out'       => '',
	);
} elseif ( $writing_system_term ) {
	$params = array(
		'title'          => strtolower( $writing_system_term->name ) === 'unwritten' ? 'Unwritten languages' : 'Languages written in ' . $writing_system_term->name,
		'subtitle'       => 'Wikitongues crowd-sources video samples of every language in the world.',
		'show_total'     => 'true',
		'post_type'      => 'languages',
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
		'taxonomy'       => 'writing-system',
		'term'           => $writing_system_term->slug,
		'link_out'       => '',
	);
} else {
	$params = array(
		'title'          => 'Languages',
		'subtitle'       => 'Wikitongues crowd-sources video samples of every language in the world.',
		'show_total'     => 'true',
		'post_type'      => 'languages',
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
