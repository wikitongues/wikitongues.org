<?php

function update_video_featured_language_iso_codes($post_id) {
    // Only run this for 'videos' post type
    if (get_post_type($post_id) !== 'videos') {
        return;
    }

    // Check if this is an autosave or a revision. If so, return.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    error_log('Updating language ISO codes for post ID: ' . $post_id);

    // Get the 'featured_languages' field
    $featured_languages = get_field('featured_languages', $post_id);

    if ($featured_languages) {
        error_log('Featured languages: ' . print_r($featured_languages, true));
    } else {
        error_log('No featured languages found.');
    }

    if ($featured_languages && is_array($featured_languages)) {
        $iso_codes = array();

        // Loop through the languages and collect the ISO codes
        foreach ($featured_languages as $language) {
            if (isset($language->post_name)) {
                $iso_codes[] = $language->post_name;
            }
        }

        // Convert the array of ISO codes to a comma-separated string
        $iso_code_string = implode(',', $iso_codes);

        // Update the 'language_iso_codes' field with the string of ISO codes
        update_field('language_iso_codes', $iso_code_string, $post_id);
    } else {
        // If there are no languages, clear the field
        update_field('language_iso_codes', '', $post_id);
    }
}
add_action('save_post', 'update_video_featured_language_iso_codes');
