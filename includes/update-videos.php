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

// Function to trigger save_post for all 'videos' posts
function trigger_save_post_for_all_videos() {
    // Check for a custom admin action
    if (isset($_GET['trigger_save_post']) && $_GET['trigger_save_post'] == 'true') {
        // Log the start of the operation
        error_log('Triggering save_post for all videos...');

        // Initialize page number
        $paged = 1;

        // Define the query arguments for retrieving 'videos' posts
        $args = array(
            'post_type' => 'videos',
            'posts_per_page' => 50, // Get 50 posts at a time
            'post_status' => 'any', // Include all post statuses if needed
            'paged' => $paged, // Start from the first page
        );

        // Perform the query in a loop to handle pagination
        while (true) {
            // Log the current page being processed
            error_log('Processing page: ' . $paged);

            // Create a new WP_Query instance
            $query = new WP_Query($args);

            // If no posts are found, break the loop
            if (!$query->have_posts()) {
                error_log('No more posts found. Ending process.');
                break;
            }

            // Loop through the posts
            while ($query->have_posts()) {
                $query->the_post();

                // Get the current post ID
                $post_id = get_the_ID();

                // Trigger save_post by updating the post
                wp_update_post(array('ID' => $post_id));

                // Log the updated post ID
                error_log('Updated post ID: ' . $post_id);

                // Reset the global post data to free up memory
                wp_reset_postdata();
            }

            // Increment the page number
            $paged++;
            $args['paged'] = $paged; // Update the query for the next page
        }

        // Log the completion of the operation
        error_log('Done processing all video posts.');
    }
}
add_action('admin_notices', 'trigger_save_post_for_all_videos');
?>