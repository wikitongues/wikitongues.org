<?php
get_header();

$language_slug = isset( $_GET['language'] ) ? sanitize_title( wp_unslash( $_GET['language'] ) ) : '';
$language_post = $language_slug ? get_page_by_path( $language_slug, OBJECT, 'languages' ) : null;

echo '<main class="wt_archive-videos">';

if ( $language_post ) {
	$standard_name = get_field( 'standard_name', $language_post->ID ) ?: $language_post->post_title;
	$title_name    = $standard_name;
	if ( substr( $standard_name, -7 ) !== 'anguage' ) {
		$title_name .= ' language';
	}

	$params = array(
		'title'          => $title_name . ' videos',
		'subtitle'       => 'Wikitongues crowd-sources video samples of every language in the world.',
		'show_total'     => 'true',
		'post_type'      => 'videos',
		'columns'        => 3,
		'posts_per_page' => 9,
		'orderby'        => 'date',
		'order'          => 'desc',
		'pagination'     => 'true',
		'meta_key'       => 'featured_languages',
		'meta_value'     => $language_post->ID,
		'selected_posts' => '',
		'display_blank'  => 'true',
		'exclude_self'   => 'false',
		'taxonomy'       => '',
		'term'           => '',
	);
} else {
	$params = array(
		'title'          => 'Videos',
		'subtitle'       => 'Wikitongues crowd-sources video samples of every language in the world.',
		'show_total'     => 'true',
		'post_type'      => 'videos',
		'columns'        => 3,
		'posts_per_page' => 9,
		'orderby'        => 'date',
		'order'          => 'desc',
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
