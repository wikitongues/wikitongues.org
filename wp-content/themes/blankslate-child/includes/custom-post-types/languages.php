<?php
// ====================
// Register Custom Post Type
// ====================
add_action('init', 'create_post_type_languages');
function create_post_type_languages()
{
    register_taxonomy_for_object_type('category', 'languages');
    register_taxonomy_for_object_type('post_tag', 'languages');
    register_post_type('languages',
        array(
        'labels' => array(
            'name' => __('Languages', 'language'),
            'singular_name' => __('Language', 'language'),
            'add_new' => __('Add New', 'language'),
            'add_new_item' => __('Add New Language', 'language'),
            'edit' => __('Edit', 'language'),
            'edit_item' => __('Edit Language', 'language'),
            'new_item' => __('New Language', 'language'),
            'view' => __('View Language', 'language'),
            'view_item' => __('View Language', 'language'),
            'search_items' => __('Search Languages', 'language'),
            'not_found' => __('No Languages found', 'language'),
            'not_found_in_trash' => __('No language Items found in Trash', 'language')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-translation',
        'has_archive' => true,
        'supports' => array(
            'title', 'thumbnail', 'excerpt'
        ),
        'can_export' => true,
        'show_in_rest' => true,
        'rest_controller_class' => 'WT_REST_Posts_Controller'
    ));
}

// ====================
// Manage Custom Columns
// ====================
// Add custom columns to the 'languages' post type
add_filter('manage_languages_posts_columns', 'add_languages_custom_columns');
function add_languages_custom_columns($columns) {
	unset($columns['date']);
	$columns['standard_name'] = __('Name', 'languages');
	$columns['nations_of_origin'] = __('Nations', 'languages');
    $columns['speakers_recorded'] = __('Videos', 'languages');
    $columns['lexicons'] = __('Lexicons', 'languages');
    $columns['external_resources'] = __('Resources', 'languages');

	return $columns;
}

add_action('manage_languages_posts_custom_column', 'fill_languages_custom_columns', 10, 2);
function fill_languages_custom_columns($column, $post_id) {
	switch ($column) {
        case 'standard_name':
        case 'nations_of_origin':
            $value = get_field($column, $post_id);
            echo esc_html($value);
            break;

        case 'speakers_recorded':
        case 'lexicons':
        case 'external_resources':
            $field = get_field($column, $post_id);
            $count = is_array($field) ? count($field) : (($field instanceof WP_Post) ? 1 : 0);
            echo esc_html($count);
            break;
	}
}

add_filter('manage_edit-languages_sortable_columns', 'make_languages_columns_sortable');
function make_languages_columns_sortable($columns) {
	$columns['standard_name'] = 'standard_name';
    $columns['nations_of_origin'] = 'nations_of_origin';
    $columns['speakers_recorded'] = 'speakers_recorded';
    $columns['lexicons'] = 'lexicons';
    $columns['external_resources'] = 'external_resources';
	return $columns;
}

// ====================
// Handle Sorting by Custom Fields
// ====================
// // Modify the query to sort by custom fields
add_action('pre_get_posts', 'languages_custom_column_orderby');
function languages_custom_column_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $orderby = $query->get('orderby');
    $order = $query->get('order') ? $query->get('order') : 'ASC';

    if (in_array($orderby, ['standard_name', 'nations_of_origin'])) {
        $query->set('meta_key', $orderby);
        $query->set('orderby', 'meta_value');
    } elseif (in_array($orderby, ['speakers_recorded', 'lexicons', 'external_resources'])) {
        // Map the column to the meta key
        $meta_key = $orderby . '_count';
        $meta_query_key = $orderby . '_clause';

        // Get existing meta queries if any
        $meta_query = $query->get('meta_query');
        if (!is_array($meta_query)) {
            $meta_query = [];
        }

        // Add our meta query clause
        $meta_query[] = [
            'key' => $meta_key,
            'type' => 'NUMERIC',
        ];
        $query->set('meta_query', $meta_query);

        // Set the orderby to use our meta query clause
        $query->set('orderby', [
            'meta_value_num' => $order,
            'title' => 'ASC',
        ]);

        // Ensure meta_key is not set to prevent exclusion of posts
        $query->set('meta_key', '');
    }
}



// ====================
// Update Counts for Custom Fields
// ====================
function update_custom_fields_counts($post_id) {
    if (get_post_type($post_id) !== 'languages') {
        return;
    }

    $fields = ['speakers_recorded', 'lexicons', 'external_resources'];

    foreach ($fields as $field) {
        $value = get_field($field, $post_id);

        $count = is_array($value) ? count($value) : ((!empty($value)) ? 1 : 0);

        update_post_meta($post_id, "{$field}_count", $count);
    }

    // Conditional logging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Updated counts for post ID: $post_id");
    }
}

add_action('save_post_languages', 'update_custom_fields_counts_on_save');
function update_custom_fields_counts_on_save($post_id) {
    // Check if batch update is currently in progress
    if (!empty($GLOBALS['batch_update_in_progress'])) {
        return;
    }

    // Check if this is an autosave or a revision.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check if relevant fields have changed
    $fields = ['speakers_recorded', 'lexicons', 'external_resources'];
    $fields_changed = false;

    foreach ($fields as $field) {
        if (isset($_POST['acf'][$field])) {
            $fields_changed = true;
            break;
        }
    }

    if ($fields_changed) {
        update_custom_fields_counts($post_id);
    }
}