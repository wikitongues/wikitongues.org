<?php
get_header();

$territory_slug      = isset( $_GET['territory'] ) ? sanitize_title( wp_unslash( $_GET['territory'] ) ) : '';
$territory_post      = $territory_slug ? get_page_by_path( $territory_slug, OBJECT, 'territories' ) : null;
$genealogy           = isset( $_GET['genealogy'] ) ? sanitize_text_field( wp_unslash( $_GET['genealogy'] ) ) : '';
$genealogy_term      = $genealogy ? get_term_by( 'slug', $genealogy, 'linguistic-genealogy' ) : null;
$writing_system      = isset( $_GET['writing_system'] ) ? sanitize_text_field( wp_unslash( $_GET['writing_system'] ) ) : '';
$writing_system_term = $writing_system ? get_term_by( 'slug', $writing_system, 'writing-system' ) : null;

echo '<main class="wt_archive-languages">';

$subtitle = 'Wikitongues crowd-sources video samples of every language in the world.';

if ( $territory_post ) {
	$territory_name = wt_prefix_the( $territory_post->post_title );
	// false = return raw IDs, not hydrated WP_Post objects (critical for large territories).
	$language_ids = get_field( 'languages', $territory_post->ID, false );
	$selected     = $language_ids ? implode( ',', $language_ids ) : '';

	$params = wt_gallery_params(
		array(
			'title'          => 'Languages of ' . $territory_name,
			'subtitle'       => $subtitle,
			'post_type'      => 'languages',
			'selected_posts' => $selected,
			'display_blank'  => 'true',
		)
	);
} elseif ( $genealogy_term ) {
	$params = wt_gallery_params(
		array(
			'title'         => $genealogy_term->name . ' linguistic family',
			'subtitle'      => $subtitle,
			'post_type'     => 'languages',
			'display_blank' => 'true',
			'taxonomy'      => 'linguistic-genealogy',
			'term'          => $genealogy_term->slug,
		)
	);
} elseif ( $writing_system_term ) {
	$title = strtolower( $writing_system_term->name ) === 'unwritten'
		? 'Unwritten languages'
		: 'Languages written in ' . $writing_system_term->name;

	$params = wt_gallery_params(
		array(
			'title'         => $title,
			'subtitle'      => $subtitle,
			'post_type'     => 'languages',
			'display_blank' => 'true',
			'taxonomy'      => 'writing-system',
			'term'          => $writing_system_term->slug,
		)
	);
} else {
	$params = wt_gallery_params(
		array(
			'title'     => 'Languages',
			'subtitle'  => $subtitle,
			'post_type' => 'languages',
		)
	);
}

echo create_gallery_instance( $params );
echo '</main>';
require 'modules/newsletter.php';
get_footer();
