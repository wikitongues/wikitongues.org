<?php
add_action('init', 'create_post_type_videos');
function create_post_type_videos()
{
    register_taxonomy_for_object_type('category', 'videos');
    register_taxonomy_for_object_type('post_tag', 'videos');
    register_post_type('videos',
        array(
        'labels' => array(
            'name' => __('Videos', 'video'),
            'singular_name' => __('Video', 'video'),
            'add_new' => __('Add New', 'video'),
            'add_new_item' => __('Add New Video', 'video'),
            'edit' => __('Edit', 'video'),
            'edit_item' => __('Edit Video', 'video'),
            'new_item' => __('New Video', 'video'),
            'view' => __('View Video', 'video'),
            'view_item' => __('View Video', 'video'),
            'search_items' => __('Search Videos', 'video'),
            'not_found' => __('No Videos found', 'video'),
            'not_found_in_trash' => __('No Videos found in Trash', 'video')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-video-alt3',
        'has_archive' => true,
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'thumbnail'
        ),
        'can_export' => true,
        // 'taxonomies' => array(
        //     'post_tag',
        //     'category'
        // ),
        'show_in_rest' => true,
        'rest_controller_class' => 'WT_REST_Posts_Controller'
    ));
}


// featured_languages, public_status
// Add custom columns to the 'fellows' post type
function add_videos_custom_columns($columns) {
	unset($columns['date']);
	$columns['featured_languages'] = __('Languages', 'videos');
	$columns['public_status'] = __('Public Status', 'videos');

	return $columns;
}
add_filter('manage_videos_posts_columns', 'add_videos_custom_columns');

function fill_videos_custom_columns($column, $post_id) {
	switch ($column) {
        case 'featured_languages':
            $featured_languages = get_field('featured_languages', $post_id);
            if ($featured_languages instanceof WP_Post) {
                // If the ACF field returns a post object, display the post title
                echo esc_html($featured_languages->post_title);
            } elseif (is_array($featured_languages)) {
                // If it's an array of post objects (multiple posts selected)
                $titles = wp_list_pluck($featured_languages, 'post_title'); // Get titles of all posts
                echo esc_html(implode(', ', $titles)); // Display them as a comma-separated list
            } else {
                echo __('No related post found', 'videos'); // Handle cases where the field is empty or invalid
            }
            break;

        case 'public_status':
            $public_status = get_field('public_status', $post_id);
            echo esc_html($public_status);
            break;
	}
}
add_action('manage_videos_posts_custom_column', 'fill_videos_custom_columns', 10, 2);

function make_videos_columns_sortable($columns) {
	$columns['featured_languages'] = 'featured_languages';
	$columns['public_status'] = 'public_status';
	return $columns;
}
add_filter('manage_edit-videos_sortable_columns', 'make_videos_columns_sortable');

// // Modify the query to sort by custom fields
function videos_custom_column_orderby($query) {
	if (!is_admin()) {
			return;
	}

	$orderby = $query->get('orderby');
	if ('featured_languages' == $orderby) {
			$query->set('meta_key', 'featured_languages');
			$query->set('orderby', 'meta_value');
	}
	if ('public_status' == $orderby) {
			$query->set('meta_key', 'public_status');
			$query->set('orderby', 'meta_value');
	}
}
add_action('pre_get_posts', 'videos_custom_column_orderby');