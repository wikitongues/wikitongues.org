<?php
// Register custom REST API endpoints
add_action('rest_api_init', 'custom_register_search_endpoint');
function custom_register_search_endpoint() {
	register_rest_route('custom/v1', '/search', array(
			'methods' => 'GET',
			'callback' => 'custom_search_callback',
			'permission_callback' => '__return_true'
	));
}

add_action('rest_api_init', function () {
	$routes = rest_get_server()->get_routes();
	error_log(print_r($routes, true));
});

function custom_search_callback($request) {
	$query = sanitize_text_field($request['query']);
	error_log('Search query: ' . $query);

	// Refine the search to include only the alternate_names field
	$meta_query = array(
			array(
					'key' => 'alternate_names',
					'value' => $query,
					'compare' => 'LIKE'
			)
	);

	$args = array(
			'post_type' => 'languages',
			'post_status' => 'publish',
			'posts_per_page' => 100,
			'meta_query' => $meta_query
	);

	error_log('WP_Query args: ' . print_r($args, true));

	$posts = get_posts($args);

	error_log('Found posts: ' . print_r($posts, true));

	$results = array();
	foreach ($posts as $post) {
			$meta = get_post_meta($post->ID);
			error_log('Post meta for ' . $post->ID . ': ' . print_r($meta, true));

			$results[] = array(
					'id' => $post->ID,
					'label' => isset($meta['standard_name'][0]) ? $meta['standard_name'][0] : '',
					'identifier' => $post->post_name,
					'alternate_names' => isset($meta['alternate_names'][0]) ? $meta['alternate_names'][0] : '',
					'nations_of_origin' => isset($meta['nations_of_origin'][0]) ? $meta['nations_of_origin'][0] : '',
					'iso_code' => isset($meta['iso_code'][0]) ? $meta['iso_code'][0] : '',
					'glottocode' => isset($meta['glottocode'][0]) ? $meta['glottocode'][0] : ''
			);
	}

	error_log('Search results: ' . print_r($results, true));

	return $results;
}