<?php
get_header();

$language_slug = isset( $_GET['language'] ) ? sanitize_title( wp_unslash( $_GET['language'] ) ) : '';
$language_post = $language_slug ? get_page_by_path( $language_slug, OBJECT, 'languages' ) : null;

echo '<main class="wt_archive-videos">';

$subtitle = 'Wikitongues crowd-sources video samples of every language in the world.';

if ( $language_post ) {
	$standard_name = get_field( 'standard_name', $language_post->ID ) ?: $language_post->post_title;
	$title_name    = $standard_name;
	if ( substr( $standard_name, -7 ) !== 'anguage' ) {
		$title_name .= ' language';
	}

	$params = wt_gallery_params(
		array(
			'title'         => $title_name . ' videos',
			'subtitle'      => $subtitle,
			'post_type'     => 'videos',
			'orderby'       => 'date',
			'order'         => 'desc',
			'meta_key'      => 'featured_languages',
			'meta_value'    => $language_post->ID,
			'display_blank' => 'true',
		)
	);
} else {
	$params = wt_gallery_params(
		array(
			'title'     => 'Videos',
			'subtitle'  => $subtitle,
			'post_type' => 'videos',
			'orderby'   => 'date',
			'order'     => 'desc',
		)
	);
}

echo create_gallery_instance( $params );
echo '</main>';
require 'modules/newsletter.php';
get_footer();
