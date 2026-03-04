<?php
/* Template Name: Random Language Redirect */

global $wpdb;

// Query languages that have at least one recorded video by reading the raw
// speakers_recorded ACF field meta — avoids reliance on the stale count meta.
$language_posts = $wpdb->get_col(
	"SELECT p.ID
	FROM {$wpdb->posts} p
	INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
	WHERE p.post_type = 'languages'
	AND p.post_status = 'publish'
	AND pm.meta_key = 'speakers_recorded'
	AND pm.meta_value REGEXP '^a:[1-9][0-9]*:'"
);

$language_posts = $language_posts ?: array();

if ( ! empty( $language_posts ) ) {
	$random_post_id = $language_posts[ array_rand( $language_posts ) ];
	wp_redirect( get_permalink( $random_post_id ) );
	exit;
} else {
		wp_redirect( home_url() );
	exit;
}
