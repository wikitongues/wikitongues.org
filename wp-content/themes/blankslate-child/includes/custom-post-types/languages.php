<?php
// ====================
// Register Custom Post Type
// ====================
add_action( 'init', 'create_post_type_languages' );
function create_post_type_languages() {
	register_taxonomy_for_object_type( 'category', 'languages' );
	register_taxonomy_for_object_type( 'post_tag', 'languages' );
	register_post_type(
		'languages',
		array(
			'labels'                => array(
				'name'               => __( 'Languages', 'language' ),
				'singular_name'      => __( 'Language', 'language' ),
				'add_new'            => __( 'Add New', 'language' ),
				'add_new_item'       => __( 'Add New Language', 'language' ),
				'edit'               => __( 'Edit', 'language' ),
				'edit_item'          => __( 'Edit Language', 'language' ),
				'new_item'           => __( 'New Language', 'language' ),
				'view'               => __( 'View Language', 'language' ),
				'view_item'          => __( 'View Language', 'language' ),
				'search_items'       => __( 'Search Languages', 'language' ),
				'not_found'          => __( 'No Languages found', 'language' ),
				'not_found_in_trash' => __( 'No language Items found in Trash', 'language' ),
			),
			'public'                => true,
			'hierarchical'          => true,
			'menu_icon'             => 'dashicons-translation',
			'has_archive'           => true,
			'supports'              => array(
				'title',
				'thumbnail',
				'excerpt',
			),
			'can_export'            => true,
			'show_in_rest'          => true,
			'rest_controller_class' => 'WT_REST_Posts_Controller',
		)
	);
}

// ====================
// Register Linguistic Genealogy Taxonomy
// ====================
add_action( 'init', 'wt_register_linguistic_genealogy_taxonomy' );
function wt_register_linguistic_genealogy_taxonomy() {
	register_taxonomy(
		'linguistic-genealogy',
		array( 'languages' ),
		array(
			'labels'             => array(
				'name'          => __( 'Linguistic Genealogies' ),
				'singular_name' => __( 'Linguistic Genealogy' ),
			),
			'hierarchical'       => false,
			'public'             => true,
			'show_in_rest'       => true,
			'publicly_queryable' => false,
			'rewrite'            => false,
		)
	);
}

// ====================
// Register Writing System Taxonomy
// ====================
add_action( 'init', 'wt_register_writing_system_taxonomy' );
function wt_register_writing_system_taxonomy() {
	register_taxonomy(
		'writing-system',
		array( 'languages' ),
		array(
			'labels'             => array(
				'name'          => __( 'Writing Systems' ),
				'singular_name' => __( 'Writing System' ),
			),
			'hierarchical'       => false,
			'public'             => true,
			'show_in_rest'       => true,
			'publicly_queryable' => false,
			'rewrite'            => false,
		)
	);
}

// ====================
// Manage Custom Columns
// ====================
// Add custom columns to the 'languages' post type
add_filter( 'manage_languages_posts_columns', 'add_languages_custom_columns' );
function add_languages_custom_columns( $columns ) {
	unset( $columns['date'] );
	$columns['standard_name']      = __( 'Name', 'languages' );
	$columns['nations_of_origin']  = __( 'Nations', 'languages' );
	$columns['speakers_recorded']  = __( 'Videos', 'languages' );
	$columns['lexicons']           = __( 'Lexicons', 'languages' );
	$columns['external_resources'] = __( 'Resources', 'languages' );

	return $columns;
}

add_action( 'manage_languages_posts_custom_column', 'fill_languages_custom_columns', 10, 2 );
function fill_languages_custom_columns( $column, $post_id ) {
	switch ( $column ) {
		case 'standard_name':
		case 'nations_of_origin':
			$value = get_field( $column, $post_id );
			echo esc_html( $value );
			break;

		case 'speakers_recorded':
		case 'external_resources':
			$field = get_field( $column, $post_id );
			$count = is_array( $field ) ? count( $field ) : ( ( $field instanceof WP_Post ) ? 1 : 0 );
			echo esc_html( (string) $count );
			break;

		case 'lexicons':
			$source = get_field( 'lexicon_source', $post_id );
			$target = get_field( 'lexicon_target', $post_id );
			$count  = ( is_array( $source ) ? count( $source ) : 0 ) + ( is_array( $target ) ? count( $target ) : 0 );
			echo esc_html( (string) $count );
			break;
	}
}

add_filter( 'manage_edit-languages_sortable_columns', 'make_languages_columns_sortable' );
function make_languages_columns_sortable( $columns ) {
	$columns['standard_name']      = 'standard_name';
	$columns['nations_of_origin']  = 'nations_of_origin';
	$columns['speakers_recorded']  = 'speakers_recorded';
	$columns['lexicons']           = 'lexicons';
	$columns['external_resources'] = 'external_resources';
	return $columns;
}

// ====================
// Handle Sorting by Custom Fields
// ====================
// // Modify the query to sort by custom fields
add_action( 'pre_get_posts', 'languages_custom_column_orderby' );
function languages_custom_column_orderby( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$orderby = $query->get( 'orderby' );
	$order   = $query->get( 'order' ) ? $query->get( 'order' ) : 'ASC';

	if ( in_array( $orderby, array( 'standard_name', 'nations_of_origin' ), true ) ) {
		$query->set( 'meta_key', $orderby );
		$query->set( 'orderby', 'meta_value' );
	} elseif ( in_array( $orderby, array( 'speakers_recorded', 'lexicons', 'external_resources' ), true ) ) {
		// Map the column to the meta key
		$meta_key       = $orderby . '_count';
		$meta_query_key = $orderby . '_clause';

		// Get existing meta queries if any
		$meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}

		// Add our meta query clause
		$meta_query[] = array(
			'key'  => $meta_key,
			'type' => 'NUMERIC',
		);
		$query->set( 'meta_query', $meta_query );

		// Set the orderby to use our meta query clause
		$query->set(
			'orderby',
			array(
				'meta_value_num' => $order,
				'title'          => 'ASC',
			)
		);

		// Ensure meta_key is not set to prevent exclusion of posts
		$query->set( 'meta_key', '' );
	}
}

// ====================
// Update Counts for Custom Fields
// ====================
function update_custom_fields_counts( $post_id ) {
	if ( get_post_type( $post_id ) !== 'languages' ) {
		return;
	}

	$fields = array( 'speakers_recorded', 'lexicon_source', 'lexicon_target', 'external_resources' );

	$lexicons_total = 0;

	foreach ( $fields as $field ) {
		$value = get_field( $field, $post_id );
		$count = is_array( $value ) ? count( $value ) : ( ( ! empty( $value ) ) ? 1 : 0 );
		update_post_meta( $post_id, "{$field}_count", $count );

		if ( 'lexicon_source' === $field || 'lexicon_target' === $field ) {
			$lexicons_total += $count;
		}
	}

	// Combined count used for admin column sorting.
	update_post_meta( $post_id, 'lexicons_count', $lexicons_total );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( "Updated counts for post ID: $post_id" );
	}
}

// ====================
// Invalidate Archive Stats Transient
// ====================
add_action( 'save_post', 'wt_invalidate_archive_stats' );
function wt_invalidate_archive_stats( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	$watched = array( 'languages', 'videos', 'lexicons', 'resources', 'territories' );
	if ( in_array( get_post_type( $post_id ), $watched, true ) ) {
		delete_transient( 'wt_archive_stats' );
	}
}

// acf/save_post fires after ACF has written all field values, so get_field() returns
// the newly saved data. This replaces the previous save_post_languages hook which
// checked $_POST['acf'][$field_name] — a check that never matched because ACF keys
// POST data by field key, not field name.
add_action( 'acf/save_post', 'update_custom_fields_counts_on_save' );
function update_custom_fields_counts_on_save( $post_id ) {
	if ( ! empty( $GLOBALS['batch_update_in_progress'] ) ) {
		return;
	}
	update_custom_fields_counts( $post_id );
}
