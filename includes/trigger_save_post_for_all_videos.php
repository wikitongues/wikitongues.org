<?php

// Load WordPress environment
require_once( dirname(__FILE__) . '/wp-load.php' );

function trigger_save_post_for_all_videos() {
    // Get all posts of post type 'videos'
    $args = array(
        'post_type' => 'videos',
        'posts_per_page' => -1, // Get all posts
        'post_status' => 'any', // Include all post statuses if needed
    );

    $video_posts = get_posts($args);

    if ($video_posts) {
        foreach ($video_posts as $post) {
            // Create an array with the post ID to pass to wp_update_post()
            $post_data = array(
                'ID' => $post->ID,
            );

            // Trigger save_post by updating the post
            wp_update_post($post_data);
        }
        echo count($video_posts) . " video records updated.";
    } else {
        echo "No video records found.";
    }
}

// Run the function
trigger_save_post_for_all_videos();
