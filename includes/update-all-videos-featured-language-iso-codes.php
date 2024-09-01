<?php

function update_all_video_language_iso_codes() {
  // Query all video posts
  $args = array(
      'post_type' => 'videos',
      'posts_per_page' => -1, // Get all video posts
      'fields' => 'ids', // Only get post IDs for performance
  );

  $video_posts = new WP_Query($args);

  if ($video_posts->have_posts()) {
      foreach ($video_posts->posts as $post_id) {
          // Get the 'featured_languages' field
          $featured_languages = get_field('featured_languages', $post_id);

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
  }

  // Optionally, you can display a message or log the completion
  error_log('All video records have been updated.');
}
// Run the function once
update_all_video_language_iso_codes();