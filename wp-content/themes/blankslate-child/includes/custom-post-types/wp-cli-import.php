<?php
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('import-captions', 'import_captions_command');
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
        if (empty($row['Caption Name']) || empty($row['Source Video']) || empty($row['Source Language']) || empty($row['Creator']) || empty($row['File'])) {
            $error_count++;
            log_message($log_file, "Missing required fields: " . json_encode($row));
            continue;
        }

        if (!filter_var($row['File'], FILTER_VALIDATE_URL)) {
            $error_count++;
            log_message($log_file, "Invalid URL for file: {$row['File']}");
            continue;
        }

        // Fetch source video and language
        $source_video = get_page_by_title($row['Source Video'], OBJECT, 'videos');
        $source_language = get_page_by_title($row['Source Language'], OBJECT, 'languages');

        if (!$source_video || !$source_language) {
            $error_count++;
            log_message($log_file, "Source Video or Language not found: " . json_encode($row));
            continue;
        }

        // Create post
        $post_id = wp_insert_post([
            'post_type' => 'captions',
            'post_status' => 'publish',
            'post_title' => $row['Caption Name'],
        ]);

        if (is_wp_error($post_id)) {
            $error_count++;
            log_message($log_file, "Error creating post: " . $post_id->get_error_message());
            continue;
        }

        // Update ACF fields
        update_field('source_video', $source_video->ID, $post_id);
        update_field('source_language', $source_language->ID, $post_id);
        update_field('creator', $row['Creator'], $post_id);
        update_field('file', $row['File'], $post_id);

        $imported_count++;
        log_message($log_file, "Imported: {$row['Caption Name']}");
    }

    WP_CLI::success("Import completed: $imported_count imported, $error_count errors.");
}

function log_message($log_file, $message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}
