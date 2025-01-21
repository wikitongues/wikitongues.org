<?php
if (defined('WP_CLI') && WP_CLI) {
	WP_CLI::add_command('import-captions', 'import_captions_command');
}

function get_safe_value($value) {
	return is_array($value) ? implode(', ', $value) : (string) $value;
}

function safe_dropbox_url($url) {
	$parts = parse_url($url);

	if (!isset($parts['path'])) return $url;

	$path_segments = explode('/', $parts['path']);
	$encoded_path = implode('/', array_map('rawurlencode', $path_segments));

	// Reconstruct the URL
	$final_url = "{$parts['scheme']}://{$parts['host']}{$encoded_path}";
	if (isset($parts['query'])) {
			$final_url .= '?' . $parts['query'];
	}

	return $final_url;
}

function get_post_by_exact_title($title, $post_type = 'post') {
	$query = new WP_Query([
		'post_type' => $post_type,
		'title' => $title,
		'post_status' => ['publish', 'private', 'acf-disabled'],
		'posts_per_page' => -1,
	]);

	foreach ($query->posts as $post) {
		if ($post->post_title === $title) {
			return $post;
		}
	}

	return null;
}

function import_captions_command($args, $assoc_args) {
	$csv_file = $args[0] ?? null;

	if (!$csv_file || !file_exists($csv_file)) {
			WP_CLI::error("CSV file not found or not provided.");
			return;
	}

	$log_file = __DIR__ . '/import-log-' . date('Y-m-d_H-i-s') . '.log';
	$data = array_map('str_getcsv', file($csv_file));
	$headers = array_shift($data);
	$imported_count = 0;
	$error_count = 0;

	foreach ($data as $row) {
			$row = array_combine($headers, $row);

			// Validate fields
			if (empty($row['Caption Name']) || empty($row['Source Video']) || empty($row['Source Language']) || empty($row['File'])) {
					$error_count++;
					log_message($log_file, "Missing required fields: " . json_encode($row));
					continue;
			}

			if (!filter_var($row['File'], FILTER_VALIDATE_URL)) {
					$error_count++;
					log_message($log_file, "Invalid URL for file: {$row['File']}");
					continue;
			}

			try {
					$post_title = get_safe_value($row['Caption Name']);

					$source_video = get_post_by_exact_title($row['Source Video'], 'videos');
					if (!$source_video) {
						throw new Exception("Source video not found: {$row['Source Video']}");
					}

					$source_language_slugs = array_map('trim', explode(',', $row['Source Language']));
					$language_posts = [];

					foreach ($source_language_slugs as $slug) {
						$language_post = get_post_by_exact_title($slug, 'languages');
						if ($language_post) {
							$language_posts[] = $language_post->ID;
						} else {
							error_log("Source language not found: $slug");
						}
					}

					// If none were found, throw a general error
					if (empty($language_posts)) {
							throw new Exception("Source language not found: {$row['Source Language']}");
					}

					$creator = !empty($row['Creator']) ? get_safe_value($row['Creator']) : null;
					$file_url = safe_dropbox_url($row['File']);

					$post_id = wp_insert_post([
							'post_type'   => 'captions',
							'post_status' => 'publish',
							'post_title'  => $post_title,
					]);

					if (is_wp_error($post_id)) {
							throw new Exception("Error creating post: " . $post_id->get_error_message());
					}

					update_field('source_video', $source_video->ID, $post_id);
					update_field('source_language', $language_posts, $post_id);
					if ($creator) update_field('creator', $creator, $post_id);
					update_field('file_url', $file_url, $post_id);
					echo "File_url: {$file_url}\n";

					echo "Imported: {$post_title}\n";
					log_message($log_file, "Imported: {$post_title}");
					$imported_count++;
			} catch (Exception $e) {
					$error_count++;
					log_message($log_file, "Error processing row: " . json_encode($row) . ". Error: " . $e->getMessage());
			}
	}

	WP_CLI::success("Import completed: $imported_count imported, $error_count errors.");
}

function log_message($log_file, $message) {
	$timestamp = date('Y-m-d H:i:s');
	file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}
