<?php
// Load WordPress environment.
require_once('wp-load.php');

global $wpdb;

// Fetch all serialized license_link entries.
$serialized_entries = $wpdb->get_results("
    SELECT meta_id, meta_value
    FROM {$wpdb->postmeta}
    WHERE meta_key = 'license_link'
    AND (meta_value LIKE 'a:%' OR meta_value LIKE 's:%')
");

foreach ($serialized_entries as $entry) {
    $meta_id = $entry->meta_id;
    $serialized_value = maybe_unserialize($entry->meta_value);

    // Check if it's an array (from serialized data), get the first item; otherwise, use the value as-is.
    $plain_text_value = is_array($serialized_value) ? $serialized_value[0] : $serialized_value;

    // Update the meta_value with the plain text URL.
    $updated = $wpdb->update(
        $wpdb->postmeta,
        ['meta_value' => $plain_text_value],
        ['meta_id' => $meta_id],
        ['%s'],
        ['%d']
    );

    if ($updated) {
        echo "Updated meta_id {$meta_id} to: {$plain_text_value}\n";
    } else {
        echo "No update was made for meta_id {$meta_id}. It may already be plain text or an error occurred.\n";
    }
}

echo "All serialized entries for 'license_link' have been processed.\n";
