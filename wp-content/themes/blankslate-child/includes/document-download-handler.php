<?php
// ====================
// User Interaction
// ====================
// Generate document file title automatically
function get_latest_document_file($document_id, $language_id = null) {
    $meta_query = [['key' => 'parent_download', 'value' => $document_id, 'compare' => '=']];
    if ($language_id) {
        $meta_query[] = ['key' => 'language', 'value' => $language_id, 'compare' => '='];
    }

    $query = new WP_Query([
        'post_type'      => 'document_files',
        'posts_per_page' => 1,
        'meta_query'     => $meta_query,
        'meta_key'       => 'version',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ]);

    return $query->have_posts() ? $query->posts[0] : null;
}

add_action('wp_ajax_fetch_document_files', function () {
	$parent_id = intval($_POST['parent_id']);
	$lang_id = intval($_POST['lang_id']); // it's a post ID now!
	// Query using post ID
	$files = get_posts([
			'post_type'  => 'document_files',
			'meta_query' => [
					['key' => 'parent_download', 'value' => $parent_id, 'compare' => '='],
					['key' => 'language', 'value' => $lang_id, 'compare' => '='], // compare post ID
			],
			'orderby' => 'meta_value_num',
			'meta_key' => 'version',
			'order'   => 'DESC',
	]);

	ob_start();
	foreach ($files as $file) {
			$version = get_field('version', $file->ID);
			$format = get_field('format', $file->ID);
			$language = get_field('language', $file->ID);
			$iso_code = is_object($language) ? $language->post_title : 'Unknown';
			$language_name = is_object($language) ? get_field('standard_name', $language->ID) : 'Unknown';

			echo '<tr>';
			echo '<td>' . esc_html($language_name) . ' (' . esc_html($iso_code) . ')</td>';
			echo '<td class="version">' . esc_html($version) . '</td>';
			echo '<td>' . esc_html($format) . '</td>';
			echo '<td><button class="download-btn" data-file-id="' . esc_attr($file->ID) . '">Download</button></td>';
			echo '</tr>';
	}
	$html = ob_get_clean();
	wp_send_json_success($html);
});

function handle_document_download() {
	if (!isset($_POST['file_id'])) {
			wp_send_json_error(['message' => 'Invalid file request.']);
	}

	$file_id = intval($_POST['file_id']);
	$file_url = get_field('file', $file_id);

	if (!$file_url) {
			wp_send_json_error(['message' => 'File not found.']);
	}

	wp_send_json_success(['file_url' => esc_url(site_url('/force-download/?file_id=' . $file_id))]);
}
add_action('wp_ajax_download_document', 'handle_document_download');
add_action('wp_ajax_nopriv_download_document', 'handle_document_download');

// Force file download
function force_download_file() {
	if (!isset($_GET['file_id'])) {
			wp_die('Invalid request.');
	}

	$file_id = intval($_GET['file_id']);
	$file_url = get_field('file', $file_id);

	if (!$file_url) {
			wp_die('File not found.');
	}

	$file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $file_url);

	if (!file_exists($file_path)) {
			wp_die('File does not exist.');
	}

	header('Content-Type: ' . mime_content_type($file_path));
	header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
	header('Content-Length: ' . filesize($file_path));

	readfile($file_path);
	exit;
}
add_action('init', function () {
	if (isset($_GET['file_id']) && strpos($_SERVER['REQUEST_URI'], '/force-download/') !== false) {
			force_download_file();
	}
});

// ====================
// Admin
// ====================
// Generate document file title automatically
function auto_generate_document_file_title($post_id, $post, $update) {
	if ($post->post_type !== 'document_files') {
			return;
	}

	// Get parent, version, and language
	$parent = get_field('parent_download', $post_id);
	$version = get_field('version', $post_id);
	$language = get_field('language', $post_id);
	$format = get_field('format', $post_id);

	if (!$parent || !$version || !$language || !$format) {
			return; // Don't override title if required fields are missing
	}

	$parent_title = get_the_title($parent);
	$language_name = get_the_title($language);

	// Generate system title
	$new_title = $parent_title . '_v' . $version . '_' . $language_name . '_' . $format;

	// Prevent infinite loop when saving
	remove_action('save_post', 'auto_generate_document_file_title', 10);
	wp_update_post(['ID' => $post_id, 'post_title' => $new_title, 'post_name' => sanitize_title($new_title)]);
	add_action('save_post', 'auto_generate_document_file_title', 10, 3);
}
add_action('save_post', 'auto_generate_document_file_title', 10, 3);

function enqueue_admin_document_validation_script($hook) {
	global $post;

	// Only load on post editing and creation screens
	if (!in_array($hook, ['post.php', 'post-new.php'])) {
			return;
	}

	// Only load for 'document_files' post type
	if (!isset($post) || $post->post_type !== 'document_files') {
			return;
	}

	// Dynamically retrieve ACF field names (instead of hardcoded field keys)
	$acf_fields = [
			'parent_download' => 'acf[field_' . sanitize_title('parent_download') . ']',
			'language' => 'acf[field_' . sanitize_title('language') . ']',
			'version' => 'acf[field_' . sanitize_title('version') . ']',
	];

	// Enqueue JavaScript
	wp_enqueue_script(
			'document-files-validation',
			get_template_directory_uri() . '/js/document-files-validation.js',
			['jquery'],
			null,
			true
	);

	// Pass ACF field keys and AJAX URL to JavaScript
	wp_localize_script('document-files-validation', 'ajax_object', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'current_post_id' => $post->ID,
			'acf_fields' => $acf_fields,
	]);
}
add_action('admin_enqueue_scripts', 'enqueue_admin_document_validation_script');

function validate_unique_document_file($valid, $value, $field, $input_name) {
	if (!$valid) {
			return $valid; // Skip validation if already invalid
	}

	// Only run validation on specific fields
	$target_fields = ['parent_download', 'language', 'version'];
	if (!in_array($field['name'], $target_fields)) {
			return $valid;
	}

	// Get the current post ID
	$post_id = $_POST['post_ID'] ?? 0;

	// Only validate once, on the 'version' field (so we don’t repeat for every field)
	if ($field['name'] !== 'version') {
			return $valid;
	}

	// Retrieve ACF field values (update field keys if necessary)
	$parent_download = $_POST['acf']['field_67d59913af082'] ?? null;
	$language = $_POST['acf']['field_67d59952af085'] ?? null;
	$version = $_POST['acf']['field_67d59940af084'] ?? null;

	if (!$parent_download || !$language || !$version) {
			return $valid; // Skip validation if required fields are missing
	}

	// Convert values to proper formats
	$parent_download = intval($parent_download);
	$language_id = intval($language);
	$version = sanitize_text_field($version);

	// Query for existing files with the same parent, language, and version
	$existing_files = new WP_Query([
			'post_type'      => 'document_files',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'post__not_in'   => [$post_id], // Exclude current post if editing
			'meta_query'     => [
					'relation' => 'AND',
					[
							'key'     => 'parent_download',
							'value'   => $parent_download,
							'compare' => '=',
							'type'    => 'NUMERIC',
					],
					[
							'key'     => 'language',
							'value'   => $language_id,
							'compare' => '=',
							'type'    => 'NUMERIC',
					],
					[
							'key'     => 'version',
							'value'   => $version,
							'compare' => '=',
					],
			],
	]);

	if ($existing_files->have_posts()) {
			return "Error: A file with this Language-Version combination already exists.";
	}

	return $valid;
}
add_filter('acf/validate_value', 'validate_unique_document_file', 10, 4);