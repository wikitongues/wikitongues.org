<?php
/* Template Name: Random Language Redirect */

$args = array(
	'post_type'      => 'languages',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_query'     => array(
		array(
			'key'     => 'speakers_recorded_count',
			'value'   => '0',
			'compare' => '>',
			'type'    => 'NUMERIC',
		),
	),
);

$language_posts = get_posts( $args );

if ( ! empty( $language_posts ) ) {
	$random_post_id = $language_posts[ array_rand( $language_posts ) ];
	wp_redirect( get_permalink( $random_post_id ) );
	exit;
} else {
		wp_redirect( home_url() );
	exit;
}
