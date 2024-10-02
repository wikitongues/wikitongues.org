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


// ====================
// Manage Language Page Titles
// ====================
add_filter('the_title', 'change_videos_post_title', 10, 2);
function change_videos_post_title($title, $post_id) {
    if ('videos' === get_post_type($post_id)) {
        $video_title = get_post_meta($post_id, 'video_title', true);
        if ($video_title) {
            $title = $video_title;
        }
    }
    return $title;
}

// ====================
// Manage Custom Columns
// ====================
add_filter('manage_videos_posts_columns', 'add_videos_custom_columns');
function add_videos_custom_columns($columns) {
	unset($columns['date']);
	$columns['featured_languages'] = __('Languages', 'videos');
	$columns['public_status'] = __('Public Status', 'videos');

	return $columns;
}

add_action('manage_videos_posts_custom_column', 'fill_videos_custom_columns', 10, 2);
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

add_filter('manage_edit-videos_sortable_columns', 'make_videos_columns_sortable');
function make_videos_columns_sortable($columns) {
	$columns['featured_languages'] = 'featured_languages';
	$columns['public_status'] = 'public_status';
	return $columns;
}

// ====================
// Handle Sorting by Custom Fields
// ====================
// // Modify the query to sort by custom fields
add_action('pre_get_posts', 'videos_custom_column_orderby');
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

// ====================
// Update ISO Codes on Save
// ====================
function update_video_featured_language_iso_codes($post_id) {
    // Only run this for 'videos' post type
    if (get_post_type($post_id) !== 'videos') {
        return;
    }

    // Get the 'featured_languages' field
    $featured_languages = get_field('featured_languages', $post_id);

    // Log the featured languages for debugging
    if ($featured_languages) {
        error_log('Featured languages: ' . print_r($featured_languages, true));
    } else {
        error_log('No featured languages found.');
    }

    if ($featured_languages && is_array($featured_languages)) {
        $iso_codes = array();

        foreach ($featured_languages as $language) {
            if (isset($language->post_name)) {
                $iso_codes[] = $language->post_name;
            }
        }

        $iso_code_string = implode(',', $iso_codes);
        update_field('language_iso_codes', $iso_code_string, $post_id);
    } else {
        update_field('language_iso_codes', '', $post_id);
    }

    // Conditional logging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Updated ISO codes for post ID: $post_id");
    }
}

// Trigger ISO code update on save
add_action('save_post', 'update_video_featured_language_iso_codes_on_save');
function update_video_featured_language_iso_codes_on_save($post_id) {
    // Check if batch update is currently in progress
    if (!empty($GLOBALS['batch_update_in_progress'])) {
        return;
    }

    // Only run this for 'videos' post type
    if (get_post_type($post_id) !== 'videos') {
        return;
    }

    // Check if this is an autosave or a revision.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    update_video_featured_language_iso_codes($post_id);
}