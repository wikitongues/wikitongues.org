<?php
// ====================
// Admin
// ====================
// Generate document file title automatically
function auto_generate_document_file_title( $post_id, $post, $update ) {
	if ( $post->post_type !== 'document_files' ) {
			return;
	}

	// Get parent, version, and language
	$parent   = get_field( 'parent_download', $post_id );
	$version  = get_field( 'version', $post_id );
	$language = get_field( 'language', $post_id );
	$format   = get_field( 'format', $post_id );

	if ( ! $parent || ! $version || ! $language || ! $format ) {
			return; // Don't override title if required fields are missing
	}

	$parent_title  = get_the_title( $parent );
	$language_name = get_the_title( $language );

	// Generate system title
	$new_title = $parent_title . '_v' . $version . '_' . $language_name . '_' . $format;

	// Prevent infinite loop when saving
	remove_action( 'save_post', 'auto_generate_document_file_title', 10 );
	wp_update_post(
		array(
			'ID'         => $post_id,
			'post_title' => $new_title,
			'post_name'  => sanitize_title( $new_title ),
		)
	);
	add_action( 'save_post', 'auto_generate_document_file_title', 10, 3 );
}
add_action( 'save_post', 'auto_generate_document_file_title', 10, 3 );

function enqueue_admin_document_validation_script( $hook ) {
	global $post;

	// Only load on post editing and creation screens
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
	}

	// Only load for 'document_files' post type
	if ( ! isset( $post ) || $post->post_type !== 'document_files' ) {
			return;
	}

	// Dynamically retrieve ACF field names (instead of hardcoded field keys)
	$acf_fields = array(
		'parent_download' => 'acf[field_' . sanitize_title( 'parent_download' ) . ']',
		'language'        => 'acf[field_' . sanitize_title( 'language' ) . ']',
		'version'         => 'acf[field_' . sanitize_title( 'version' ) . ']',
		'format'          => 'acf[field_' . sanitize_title( 'format' ) . ']',
	);

	// Enqueue JavaScript
	wp_enqueue_script(
		'document-files-validation',
		get_template_directory_uri() . '/js/document-files-validation.js',
		array( 'jquery' ),
		null,
		true
	);

	// Pass ACF field keys and AJAX URL to JavaScript
	wp_localize_script(
		'document-files-validation',
		'ajax_object',
		array(
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'current_post_id' => $post->ID,
			'acf_fields'      => $acf_fields,
		)
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_admin_document_validation_script' );

function validate_unique_document_file( $valid, $value, $field, $input_name ) {
	if ( ! $valid ) {
			return $valid; // Skip validation if already invalid
	}

	// Only run validation on specific fields
	$target_fields = array( 'parent_download', 'language', 'version', 'format' );
	if ( ! in_array( $field['name'], $target_fields, true ) ) {
			return $valid;
	}

	// Get the current post ID
	$post_id = $_POST['post_ID'] ?? 0;

	// Only validate once, on the 'version' field (so we don’t repeat for every field)
	if ( $field['name'] !== 'version' ) {
			return $valid;
	}

	// Retrieve ACF field values (update field keys if necessary)
	$parent_download = $_POST['acf']['field_67d59913af082'] ?? null;
	$language        = $_POST['acf']['field_67d59952af085'] ?? null;
	$version         = $_POST['acf']['field_67d59940af084'] ?? null;
	$format          = $_POST['acf']['field_67da007bd82c5'] ?? null;

	if ( ! $parent_download || ! $language || ! $version || ! $format ) {
			return $valid; // Skip validation if required fields are missing
	}

	// Convert values to proper formats
	$parent_download = intval( $parent_download );
	$language_id     = intval( $language );
	$version         = sanitize_text_field( $version );
	$format          = sanitize_text_field( $format );

	// Query for existing files with the same parent, language, and version
	$existing_files = new WP_Query(
		array(
			'post_type'      => 'document_files',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'post__not_in'   => array( $post_id ), // Exclude current post if editing
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'parent_download',
					'value'   => $parent_download,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => 'language',
					'value'   => $language_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => 'version',
					'value'   => $version,
					'compare' => '=',
				),
				array(
					'key'     => 'format',
					'value'   => $format,
					'compare' => '=',
				),
			),
		)
	);

	if ( $existing_files->have_posts() ) {
			return "Error: A file with this combination of 'Language - Format - Version' already exists.";
	}

	return $valid;
}
add_filter( 'acf/validate_value', 'validate_unique_document_file', 10, 4 );
