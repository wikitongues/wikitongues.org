<?php
/**
 * Retrieves a list of posts for the custom gallery.
 *
 * @param array $args Arguments to customize the WP_Query.
 * @return WP_Query The query result.
 */

 function get_custom_gallery_query($atts = array()) {
    $defaults = array(
        'post_status' => 'publish',
        'post_type' => 'languages', // videos, languages, fellows
        'posts_per_page' => 6,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_key' => '',
        'meta_value' => '',
        'paged' => 1,
        'taxonomy' => '',
        'term' => '',
        'exclude_self' => 'false',
    );

    $args = wp_parse_args($atts, $defaults);

    $meta_query = array();

    if (!empty($atts['meta_key']) && !empty($atts['meta_value'])) {
        $val_array = array_map('trim', explode(',', $atts['meta_value']));
        // Special handling for featured_languages
        if ($atts['meta_key'] === 'featured_languages') {
            global $wpdb;
            $placeholders = implode(',', array_fill(0, count($val_array), '%s'));
            $language_posts = $wpdb->get_col($wpdb->prepare("
                SELECT ID FROM $wpdb->posts
                WHERE post_type = 'languages'
                AND post_status = 'publish'
                AND post_title IN ($placeholders)
            ", $val_array));

            if (!empty($language_posts)) {
                // Step 2: Query videos by these IDs
                $meta_query = ['relation' => 'OR'];

                foreach ($language_posts as $language_id) {
                    $meta_query[] = [
                        'key' => 'featured_languages',
                        'value' => '"' . $language_id . '"',
                        'compare' => 'LIKE',
                    ];
                }

                $args['meta_query'] = $meta_query;

            } else {
                // Force empty result if no language posts found
                return new WP_Query(['post__in' => [0]]);
            }

        } else {
            // Non-featured_languages keys follow original logic
            $compare_operator = 'LIKE';
            if ($atts['meta_key'] === 'fellow_language') {
                $val_array = array_map(fn($v) => '"' . intval($v) . '"', $val_array);
            } elseif ($atts['meta_key'] === 'nations_of_origin') {
                $compare_operator = '=';
            }

            if (count($val_array) > 1) {
                $meta_query = ['relation' => 'OR'];
                foreach ($val_array as $value) {
                    if (!empty($value)) {
                        $meta_query[] = [
                            'key' => $atts['meta_key'],
                            'value' => $value,
                            'compare' => $compare_operator,
                        ];
                    }
                }
            } else {
                $meta_query[] = [
                    'key' => $atts['meta_key'],
                    'value' => $val_array[0],
                    'compare' => $compare_operator,
                ];
            }

            $args['meta_query'] = $meta_query;
        }

        unset($args['meta_key']);
        unset($args['meta_value']);
    } else if (!empty($atts['taxonomy']) && !empty($atts['term'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => $atts['taxonomy'],
                'field' => 'slug',
                'terms' => $atts['term'],
            ),
        );

    }

    // Exclude current post from the query
    if ($args['exclude_self'] === 'true') {
        $current_post_type = get_post_type();
        if ($current_post_type === $args['post_type']) {
            $args['post__not_in'] = array(get_the_ID());
        }
    }

    $query = new WP_Query($args);
    if (!empty($args['tax_query'])) {
        $tax_query = $args['tax_query'][0];
        $terms = explode(',', $tax_query['terms']);
        $terms = array_map('trim', $terms);

        if (count($terms) > 1) {
            $args['tax_query'] = array('relation' => 'OR');
            foreach ($terms as $term) {
                if (!empty($term)) {
                    $args['tax_query'][] = array(
                        'taxonomy' => $tax_query['taxonomy'],
                        'field' => $tax_query['field'],
                        'terms' => $term,
                    );
                }
            }
        } else {
            $args['tax_query'][0]['terms'] = $terms[0];
        }
    }

    $query = new WP_Query($args);
    // log_data($query);
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
