<?php
// Register Document Post Type
add_action('init', 'register_document_post_type');
function register_document_post_type() {
    $args = [
        'label'               => 'Documents',
        'public'              => true,
        'has_archive'         => true,
        'supports'            => ['title', 'thumbnail'],
        'show_in_rest'        => true,
        'menu_icon'           => 'dashicons-download',
    ];

    register_post_type('document', $args);
}

// Register Download File Post Type
add_action('init', 'register_document_file_post_type');
function register_document_file_post_type() {
    $args = [
        'label'               => 'Document Files',
        'public'              => false,
        'show_ui'             => true,
        'has_archive'         => false,
        'supports'            => [''],
        'menu_icon'           => 'dashicons-media-document',
    ];

    register_post_type('document_files', $args);
}

// ====================
// Manage Custom Columns
// ====================
// Add custom columns to the 'document_files' post type
add_filter('manage_document_files_posts_columns', 'add_document_files_custom_columns');
function add_document_files_custom_columns($columns) {
	unset($columns['date']);
	$columns['parent_download'] = __('Version of', 'document_files');
	$columns['version'] = __('Version Number', 'document_files');
	$columns['language'] = __('Language', 'document_files');

	return $columns;
}

add_action('manage_document_files_posts_custom_column', 'fill_document_files_custom_columns', 10, 2);
function fill_document_files_custom_columns($column, $post_id) {
	switch ($column) {
        case 'parent_download':
						$document_files = get_field($column, $post_id);
						echo esc_html($document_files->post_title);
						break;

				case 'version':
						$version = get_field($column, $post_id);
						echo esc_html($version);
						break;

        case 'language':
						$language = get_field($column, $post_id);
						echo esc_html($language->post_title);
						break;
	}
}

add_filter('manage_edit-document_files_sortable_columns', 'make_document_files_columns_sortable');
function make_document_files_columns_sortable($columns) {
	$columns['parent_download'] = 'parent_download';
	$columns['version'] = 'version';
	$columns['language'] = 'language';
	return $columns;
}

// ====================
// Handle Sorting by Custom Fields
// ====================
// // Modify the query to sort by custom fields
add_action('pre_get_posts', 'document_files_custom_column_orderby');
function document_files_custom_column_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $orderby = $query->get('orderby');
    $order = $query->get('order') ? $query->get('order') : 'ASC';

    if (in_array($orderby, ['parent_download', 'version', 'language'])) {
        $query->set('meta_key', $orderby);
        $query->set('orderby', 'meta_value');
    }
}