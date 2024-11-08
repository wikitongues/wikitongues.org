<?php
/**
 * Retrieves a list of posts for the custom gallery.
 *
 * @param array $args Arguments to customize the WP_Query.
 * @return WP_Query The query result.
 */

 function get_custom_gallery_query($atts = array()) {
    $defaults = array(
        'post_type' => 'languages', // videos, languages, fellows
        'posts_per_page' => 6,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_key' => '',
        'meta_value' => '',
        'paged' => 1,
    );

    $args = wp_parse_args($atts, $defaults);

    $meta_query = array();

    if (!empty($atts['meta_key']) && !empty($atts['meta_value'])) {
        $val_array = explode(',',$atts['meta_value']);
        $val_array = array_map('trim', $val_array);

        // Default compare operator
        $compare_operator = 'LIKE';

        // If dealing with fellows or a serialized array, wrap ID in quotes for serialized match
        if ($atts['meta_key'] === 'fellow_language') {
            // For fellow_language, wrap ID in quotes for serialized match
            $compare_operator = 'LIKE';
            $val_array = array_map(function($value) {
                return '"' . intval($value) . '"'; // Wrap each value in quotes for serialized match
            }, $val_array);
        } elseif ($atts['meta_key'] === 'nations_of_origin') {
            $compare_operator = '=';
        } elseif ($atts['meta_key'] === 'language_iso_codes') {
            $compare_operator = '=';
        }

        if (count($val_array) > 1) {
            $meta_query = array('relation' => 'OR');
            foreach ($val_array as $value) {
                if (!empty($value)) {
                    $meta_query[] = array(
                        'key' => $atts['meta_key'],
                        'value' => $value,
                        'compare' => $compare_operator,
                    );
                }
            }
        } else {
            $meta_query[] = array(
                'key' => $atts['meta_key'],
                'value' => $val_array[0],
                'compare' => $compare_operator,
            );
        }

        $args['meta_query'] = $meta_query;
        unset($args['meta_key']);
        unset($args['meta_value']);
    }

    // // Exclude current post from the query
    $current_post_type = get_post_type();
    if ($current_post_type === $args['post_type']) {
        $args['post__not_in'] = array(get_the_ID());
    }

    $query = new WP_Query($args);
    return $query;
}


/**
 * Retrieves a random video for a given language ISO code.
 *
 * @param string $iso_code The ISO code of the language.
 * @return WP_Query The query result.
 */
function get_videos_by_featured_language($language_title) {
    // Use WP_Query to find the language post by title
    $language_query = new WP_Query(array(
        'post_type' => 'languages', // Adjust this to your custom post type if needed
        'title' => $language_title,
        'posts_per_page' => 1, // Only need one post
        'fields' => 'ids', // Only get the ID to optimize performance
    ));

    // Check if a language post is found
    if (!$language_query->have_posts()) {
        // If no post is found with the given title, return an empty WP_Query
        return new WP_Query();
    }

    // Get the language post ID
    $language_post_id = $language_query->posts[0];

    // Prepare the meta query to match the 'featured_languages' field by the language post ID
    $args = array(
        'post_type' => 'videos',
        'posts_per_page' => 1, // Adjust as needed to control the number of videos returned
        'orderby' => 'rand', // Random order
        'meta_query' => array(
            array(
                'key' => 'featured_languages', // ACF relationship field
                'value' => '"' . $language_post_id . '"', // Match against the serialized ID in the array
                'compare' => 'LIKE', // Partial match to find serialized post IDs
            ),
        ),
    );

    // Execute the query
    $video_query = new WP_Query($args);

    return $video_query;
}
