<?php
// Register custom REST API endpoints
add_action( 'rest_api_init', 'custom_register_search_endpoint' );
function custom_register_search_endpoint() {
	register_rest_route(
		'custom/v1',
		'/search',
		array(
			'methods'             => 'GET',
			'callback'            => 'custom_search_callback',
			'permission_callback' => '__return_true',
		)
	);
}

function custom_search_callback( $request ) {
	$query = sanitize_text_field( $request['query'] );

	// Refine the search to include only the alternate_names field
	$meta_query = array(
		array(
			'key'     => 'alternate_names',
			'value'   => $query,
			'compare' => 'LIKE',
		),
	);

	$args = array(
		'post_type'      => 'languages',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'meta_query'     => $meta_query,
	);

	$posts = get_posts( $args );

	$results = array();
	foreach ( $posts as $post ) {
			$meta = get_post_meta( $post->ID );

			$results[] = array(
				'id'                => $post->ID,
				'label'             => isset( $meta['standard_name'][0] ) ? $meta['standard_name'][0] : '',
				'identifier'        => $post->post_name,
				'alternate_names'   => isset( $meta['alternate_names'][0] ) ? $meta['alternate_names'][0] : '',
				'nations_of_origin' => isset( $meta['nations_of_origin'][0] ) ? $meta['nations_of_origin'][0] : '',
				'iso_code'          => isset( $meta['iso_code'][0] ) ? $meta['iso_code'][0] : '',
				'glottocode'        => isset( $meta['glottocode'][0] ) ? $meta['glottocode'][0] : '',
			);
	}

	return $results;
}
