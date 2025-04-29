<?php
// ====================
// Register Custom Post Type: Territories
// ====================
add_action('init', 'create_post_type_territories');
function create_post_type_territories()
{
		// register_taxonomy_for_object_type('regions', 'territories');
		register_post_type('territories',
        array(
            'labels' => array(
                'name' => __('Territories', 'territories'),
                'singular_name' => __('Territory', 'territories'),
                'add_new' => __('Add New', 'territories'),
                'add_new_item' => __('Add New Territory', 'territories'),
                'edit' => __('Edit', 'territories'),
                'edit_item' => __('Edit Territory', 'territories'),
                'new_item' => __('New Territory', 'territories'),
                'view' => __('View Territory', 'territories'),
                'view_item' => __('View Territory', 'territories'),
                'search_items' => __('Search Territories', 'territories'),
                'not_found' => __('No Territories found', 'territories'),
                'not_found_in_trash' => __('No Territory items found in Trash', 'territories')
            ),
            'public' => true,
            'hierarchical' => false,
            'menu_icon' => 'dashicons-admin-site-alt3',
            'has_archive' => 'territories',
						'rewrite' => ['slug' => 'territories/%region%'],
            'supports' => array(
                'title', 'editor', 'thumbnail', 'excerpt'
            ),
            'can_export' => true,
            'show_in_rest' => true,
            'rest_controller_class' => 'WT_REST_Posts_Controller',
						'taxonomies' => array(
							'region'
						)

        )
    );
}

// Register Hierarchical Taxonomy: Continent
add_action('init', 'wt_register_region_taxonomy');
function wt_register_region_taxonomy() {
    register_taxonomy('region', ['territories'], [
        'labels' => [
            'name' => __('Regions'),
            'singular_name' => __('Region'),
        ],
        'hierarchical' => true, // Enables parent-child relationships
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => [
            'slug' => 'territories',
            'hierarchical' => false,
            'with_front' => false,
        ],
    ]);
}

// Custom Rewrite Rules to Enable Nested URLs
add_filter('post_type_link', 'wt_territory_permalink', 10, 2);
function wt_territory_permalink($post_link, $post) {
    if ($post->post_type != 'territories') return $post_link;

    $terms = wp_get_object_terms($post->ID, 'region');
    if ($terms) {
        $region_slug = $terms[0]->slug;
        return str_replace('%region%', $region_slug, $post_link);
    }

    return str_replace('%region%', 'uncategorized', $post_link);
}

// Register Custom Query Vars
add_filter('query_vars', 'wt_register_query_vars');
function wt_register_query_vars($vars) {
    $vars[] = 'region';
    return $vars;
}

// Handling Custom Archives (Continent-specific pages)
add_action('init', 'wt_custom_rewrite_rules');
function wt_custom_rewrite_rules() {
    // For region archives: /territories/{region}/
		add_rewrite_rule('^territories/([^/]+)/?$', 'index.php?region=$matches[1]', 'top');

    // For single territories: /territories/{region}/{territory}/
		add_rewrite_rule('^territories/([^/]+)/([^/]+)/?$', 'index.php?territories=$matches[2]&region=$matches[1]', 'top');
}

add_filter('post_type_link', function($post_link, $post, $leavename) {
    if ( 'territories' !== $post->post_type ) {
        return $post_link;
    }

    $terms = wp_get_object_terms($post->ID, 'region');

    if ( ! empty($terms) && ! is_wp_error($terms) ) {
        $region_slug = $terms[0]->slug;
    } else {
        $region_slug = 'uncategorized'; // fallback
    }

    return str_replace('%region%', $region_slug, $post_link);
}, 10, 3);