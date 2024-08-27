<?php
/* CONSIDER BREAKING INTO MULTIPLE FUNCTIONS FILES */

require_once('includes/class-wt-rest-posts-controller.php');
require_once('includes/update-videos.php');

// update video language ISOs when editing featured languages in admin
require_once('includes/update-video-featured-language-iso-codes.php');

// enqueue stylesheets
add_action( 'wp_enqueue_scripts', 'wt_enqueue_styles' );

function wt_enqueue_styles() {
    $parenthandle = 'blankslate-style';
    $theme = wp_get_theme();
    wp_enqueue_style( $parenthandle, get_template_directory_uri() . '/style.css',
        array(),
        $theme->parent()->get('Version')
    );
    wp_enqueue_style( 'child-style', get_stylesheet_uri(),
        array( $parenthandle ),
        $theme->get('Version')
    );
}

// enqueue custom js with jquery dependency
function wt_enqueue_js() {
    wp_register_script(
        'wt_js',
        get_stylesheet_directory_uri() . '/js/custom.js',
        array('jquery'), true);

    wp_enqueue_script('wt_js');
}
add_action( 'wp_enqueue_scripts', 'wt_enqueue_js' );

function searchfilter($query)
{
    if ($query->is_search && !is_admin()) {
        $languages_search = get_query_var('s');
        if (empty($query->query_vars['post_type']) && !empty($languages_search)) {
            // only display results from these post types
            $query->set('post_type', array('languages', 'videos'));
            $query->set('order', 'ASC');

            // clear the default search query
            $query->set('s', '');

            $iso_code_regex = '#^w?[a-z]{3}$#';  // Also accounts for 4-letter Wikitongues-assigned codes
            $glottocode_regex = '#^[[:alnum:]]{4}\d{4}$#';
            preg_match($iso_code_regex, $languages_search, $iso_match);
            preg_match($glottocode_regex, $languages_search, $glottocode_match);

            if ($iso_match) {
                $query->set('meta_query', array(
                    array(
                        'key' => 'iso_code',
                        'value' => $languages_search,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'standard_name',
                        'value' => $languages_search,
                        'compare' => '='
                    ),
                    'relation' => 'OR'
                ));
            } else if ($glottocode_match) {
                $query->set('meta_query', array(
                    array(
                        'key' => 'glottocode',
                        'value' => $languages_search,
                        'compare' => '='
                    )
                ));
            } else {
                $query->set('meta_query', array(
                    array(
                        'key' => 'standard_name',
                        'value' => $languages_search,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'alternate_names',
                        'value' => $languages_search,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'nations_of_origin',
                        'value' => $languages_search,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'writing_systems',
                        'value' => $languages_search,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'linguistic_genealogy',
                        'value' => $languages_search,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'video_title',
                        'value' => $languages_search,
                        'compare' => 'LIKE'
                    ),
                    'relation' => 'OR'
                ));
            }
        }
        // $query->set('post_type',array('languages','videos', 'lexicons', 'resources'));

        // for languages post type, search by X fields

            // post_title - identical
            // iso_code - identical
            // glottocode - identical
            // standard_name - like
            // alternate_names - like
            // nations_of_origin - like
            // linguistic_genealogy - identical
            // anything else included in Scott's code from January

        // for videos post type, search by Y fields

            // video_title
            // featured_languages
            // consider for later version: new fields that pull standard_name and alternate_names from featured_languages

        // for lexicons post type, search by Z fields

            // post_title

        // for resrouces post type search by Q fields
    }
    return $query;
}

add_filter('pre_get_posts', 'searchfilter');

// remove header bump from core css output
add_action('get_header', 'my_filter_head');

function my_filter_head() {
   remove_action('wp_head', '_admin_bar_bump_cb');
}

// initiate options page - consider deprecating this
if( function_exists('acf_add_options_page') ) {
    acf_add_options_page();
}

// add archive menu
function wt_archive_menu() {
  register_nav_menu('archive-menu',__( 'Archive Menu' ));
}

add_action( 'init', 'wt_archive_menu' );

// add revitalization menu
function wt_revitalization_menu() {
  register_nav_menu('revitalization-menu',__( 'Revitalization Menu' ));
}

add_action( 'init', 'wt_revitalization_menu' );

// add about menu
function wt_about_menu() {
  register_nav_menu('about-menu',__( 'About Menu' ));
}

add_action( 'init', 'wt_about_menu' );

// add footer menu
function wt_footer_menu() {
  register_nav_menu('footer-menu',__( 'Footer Menu' ));
}

add_action( 'init', 'wt_footer_menu' );

// add mobile menu
function wt_mobile_menu() {
  register_nav_menu('mobile-menu',__( 'Mobile Menu' ));
}

add_action( 'init', 'wt_mobile_menu' );

function get_environment() {
    if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        return 'localhost';
    } elseif (strpos($_SERVER['HTTP_HOST'], 'staging') !== false) {
        return 'staging';
    } else {
        return '';
    }
}

function modify_page_title() {
    $environment = get_environment();
    if ($environment) {
        echo "<script>document.title = '" . ucfirst($environment) . " | ' + document.title;</script>";
    }
}
add_action('wp_head', 'modify_page_title');

add_action('rest_api_init', function () {
    $routes = rest_get_server()->get_routes();
    error_log(print_r($routes, true));
});

function custom_register_search_endpoint() {
    register_rest_route('custom/v1', '/search', array(
        'methods' => 'GET',
        'callback' => 'custom_search_callback',
        'permission_callback' => '__return_true'
    ));
}

add_action('rest_api_init', 'custom_register_search_endpoint');

function custom_search_callback($request) {
    $query = sanitize_text_field($request['query']);
    error_log('Search query: ' . $query);

    // Refine the search to include only the alternate_names field
    $meta_query = array(
        array(
            'key' => 'alternate_names',
            'value' => $query,
            'compare' => 'LIKE'
        )
    );

    $args = array(
        'post_type' => 'languages',
        'post_status' => 'publish',
        'posts_per_page' => 100,
        'meta_query' => $meta_query
    );

    error_log('WP_Query args: ' . print_r($args, true));

    $posts = get_posts($args);

    error_log('Found posts: ' . print_r($posts, true));

    $results = array();
    foreach ($posts as $post) {
        $meta = get_post_meta($post->ID);
        error_log('Post meta for ' . $post->ID . ': ' . print_r($meta, true));

        $results[] = array(
            'id' => $post->ID,
            'label' => isset($meta['standard_name'][0]) ? $meta['standard_name'][0] : '',
            'identifier' => $post->post_name,
            'alternate_names' => isset($meta['alternate_names'][0]) ? $meta['alternate_names'][0] : '',
            'nations_of_origin' => isset($meta['nations_of_origin'][0]) ? $meta['nations_of_origin'][0] : '',
            'iso_code' => isset($meta['iso_code'][0]) ? $meta['iso_code'][0] : '',
            'glottocode' => isset($meta['glottocode'][0]) ? $meta['glottocode'][0] : ''
        );
    }

    error_log('Search results: ' . print_r($results, true));

    return $results;
}

// Pagination for paged posts, Page 1, Page 2, Page 3, with Next and Previous Links, No plugin
function html5wp_pagination()
{
    global $wp_query;
    $big = 999999999;
    echo paginate_links(array(
        'base' => str_replace($big, '%#%', get_pagenum_link($big)),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $wp_query->max_num_pages
    ));
}

// add custom post types for language archive
add_action('init', 'create_post_type_languages');
add_action('init', 'create_post_type_videos');
add_action('init', 'create_post_type_lexicons');
add_action('init', 'create_post_type_resources'); // indexing misc.
// revitalization project
// translation/interpretation
// language learning options

// add custom post types for wikitongues team and alumni
add_action('init', 'create_post_type_fellows'); // change to awardee/fellows
add_action('init', 'create_post_type_team');
add_action('init', 'create_post_type_partners');
add_action('init', 'create_post_type_reports');

// Team
function create_post_type_team()
{
    register_taxonomy_for_object_type('category', 'team');
    register_taxonomy_for_object_type('post_tag', 'team');
    register_post_type('team',
        array(
        'labels' => array(
            'name' => __('Team', 'team'),
            'singular_name' => __('Team', 'team'),
            'add_new' => __('Add New', 'team'),
            'add_new_item' => __('Add New team', 'team'),
            'edit' => __('Edit', 'team'),
            'edit_item' => __('Edit Team', 'team'),
            'new_item' => __('New Team', 'team'),
            'view' => __('View Team', 'team'),
            'view_item' => __('View Team', 'team'),
            'search_items' => __('Search Team', 'team'),
            'not_found' => __('No Team found', 'team'),
            'not_found_in_trash' => __('No Team found in Trash', 'team')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-megaphone',
        'has_archive' => true,
        'supports' => array(
            'title',
            'thumbnail'
        ),
        'can_export' => true,
        'taxonomies' => array(
            'post_tag',
            'category'
        ),
        'show_in_rest' => true
    ));
}

// Partners
function create_post_type_partners()
{
    register_taxonomy_for_object_type('category', 'partners');
    register_taxonomy_for_object_type('post_tag', 'partners');
    register_post_type('partners',
        array(
        'labels' => array(
            'name' => __('Partners', 'partner'),
            'singular_name' => __('Partner', 'partner'),
            'add_new' => __('Add New', 'partner'),
            'add_new_item' => __('Add New Partner', 'partner'),
            'edit' => __('Edit', 'partner'),
            'edit_item' => __('Edit Partner', 'partner'),
            'new_item' => __('New Partner', 'partner'),
            'view' => __('View Partner', 'partner'),
            'view_item' => __('View Partner', 'partner'),
            'search_items' => __('Search Partners', 'partner'),
            'not_found' => __('No Partners found', 'partner'),
            'not_found_in_trash' => __('No Partners found in Trash', 'partner')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-heart',
        'has_archive' => true,
        'supports' => array(
            'title'
        ),
        'can_export' => true,
        'taxonomies' => array(
            'post_tag',
            'category'
        )
    ));
}

// Languages
function create_post_type_languages()
{
    register_taxonomy_for_object_type('category', 'languages');
    register_taxonomy_for_object_type('post_tag', 'languages');
    register_post_type('languages',
        array(
        'labels' => array(
            'name' => __('Languages', 'language'),
            'singular_name' => __('Language', 'language'),
            'add_new' => __('Add New', 'language'),
            'add_new_item' => __('Add New Language', 'language'),
            'edit' => __('Edit', 'language'),
            'edit_item' => __('Edit Language', 'language'),
            'new_item' => __('New Language', 'language'),
            'view' => __('View Language', 'language'),
            'view_item' => __('View Language', 'language'),
            'search_items' => __('Search Languages', 'language'),
            'not_found' => __('No Languages found', 'language'),
            'not_found_in_trash' => __('No language Items found in Trash', 'language')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-translation',
        'has_archive' => true,
        'supports' => array(
            'title',
            'thumbnail',
            'excerpt'
        ),
        'can_export' => true,
        'taxonomies' => array(
            'post_tag',
            'category'
        ),
        'show_in_rest' => true,
        'rest_controller_class' => 'WT_REST_Posts_Controller'
    ));
}

// Videos
function create_post_type_videos()
{
    register_taxonomy_for_object_type('category', 'videos');
    register_taxonomy_for_object_type('post_tag', 'videos');
    register_post_type('videos',
        array(
        'labels' => array(
            'name' => __('Videos', 'video'),
            'singular_name' => __('Video', 'video'),
            'add_new' => __('Add New', 'video'),
            'add_new_item' => __('Add New Video', 'video'),
            'edit' => __('Edit', 'video'),
            'edit_item' => __('Edit Video', 'video'),
            'new_item' => __('New Video', 'video'),
            'view' => __('View Video', 'video'),
            'view_item' => __('View Video', 'video'),
            'search_items' => __('Search Videos', 'video'),
            'not_found' => __('No Videos found', 'video'),
            'not_found_in_trash' => __('No Videos found in Trash', 'video')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-video-alt3',
        'has_archive' => true,
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'thumbnail'
        ),
        'can_export' => true,
        'taxonomies' => array(
            'post_tag',
            'category'
        ),
        'show_in_rest' => true,
        'rest_controller_class' => 'WT_REST_Posts_Controller'
    ));
}

// Lexicons
function create_post_type_lexicons()
{
    register_taxonomy_for_object_type('category', 'lexicons');
    register_taxonomy_for_object_type('post_tag', 'lexicons');
    register_post_type('lexicons',
        array(
        'labels' => array(
            'name' => __('Lexicons', 'lexicons'),
            'singular_name' => __('Lexicon', 'lexicon'),
            'add_new' => __('Add New', 'lexicon'),
            'add_new_item' => __('Add New Lexicon', 'lexicon'),
            'edit' => __('Edit', 'lexicon'),
            'edit_item' => __('Edit Lexicon', 'lexicon'),
            'new_item' => __('New Lexicon', 'lexicon'),
            'view' => __('View Lexicon', 'lexicon'),
            'view_item' => __('View Lexicon', 'lexicon'),
            'search_items' => __('Search Lexicons', 'lexicon'),
            'not_found' => __('No Lexicons found', 'lexicon'),
            'not_found_in_trash' => __('No Lexicon found in Trash', 'lexicon')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-format-status',
        'has_archive' => true,
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'thumbnail'
        ),
        'can_export' => true,
        'taxonomies' => array(
            'post_tag',
            'category'
        ),
        'show_in_rest' => true,
        'rest_controller_class' => 'WT_REST_Posts_Controller'
    ));
}

// External Resources
function create_post_type_resources()
{
    register_taxonomy_for_object_type('category', 'resources');
    register_taxonomy_for_object_type('post_tag', 'resources');
    register_post_type('resources',
        array(
        'labels' => array(
            'name' => __('Resources', 'resource'),
            'singular_name' => __('Resource', 'resource'),
            'add_new' => __('Add New', 'resource'),
            'add_new_item' => __('Add New Resource', 'resource'),
            'edit' => __('Edit', 'resource'),
            'edit_item' => __('Edit Resource', 'resource'),
            'new_item' => __('New Resource', 'resource'),
            'view' => __('View Resource', 'resource'),
            'view_item' => __('View Resource', 'resource'),
            'search_items' => __('Search Resources', 'resource'),
            'not_found' => __('No Resources found', 'resource'),
            'not_found_in_trash' => __('No Resources found in Trash', 'resource')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-format-quote',
        'has_archive' => true,
        'supports' => array(
            'title',
            'editor',
            'excerpt'
            // 'thumbnail'
        ),
        'can_export' => true,
        'taxonomies' => array(
            'post_tag',
            'category'
        ),
        'show_in_rest' => true,
        'rest_controller_class' => 'WT_REST_Posts_Controller'
    ));
}


// Fellows
function create_post_type_fellows()
{
    register_taxonomy_for_object_type('category', 'fellows');
    register_taxonomy_for_object_type('post_tag', 'fellows');
    register_post_type('fellows',
        array(
        'labels' => array(
            'name' => __('Fellows', 'fellows'),
            'singular_name' => __('Fellow', 'fellows'),
            'add_new' => __('Add New', 'fellows'),
            'add_new_item' => __('Add New Fellow', 'fellows'),
            'edit' => __('Edit', 'fellows'),
            'edit_item' => __('Edit Fellow', 'fellows'),
            'new_item' => __('New Fellow', 'fellows'),
            'view' => __('View Fellow', 'fellows'),
            'view_item' => __('View Fellow', 'fellows'),
            'search_items' => __('Search Fellows', 'fellows'),
            'not_found' => __('No Fellows found', 'fellows'),
            'not_found_in_trash' => __('No Fellows found in Trash', 'fellows')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-money-alt',
        'has_archive' => true,
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'thumbnail'
        ),
        'can_export' => true,
        'taxonomies' => array(
            'post_tag',
            'category'
        )
    ));
}

// Reports
function create_post_type_reports()
{
    register_taxonomy_for_object_type('category', 'reports');
    register_taxonomy_for_object_type('post_tag', 'reports');
    register_post_type('reports',
        array(
        'labels' => array(
            'name' => __('Reports', 'reports'),
            'singular_name' => __('Report', 'reports'),
            'add_new' => __('Add New', 'reports'),
            'add_new_item' => __('Add New Report', 'reports'),
            'edit' => __('Edit', 'reports'),
            'edit_item' => __('Edit Report', 'reports'),
            'new_item' => __('New Report', 'reports'),
            'view' => __('View Report', 'reports'),
            'view_item' => __('View Report', 'reports'),
            'search_items' => __('Search Reports', 'reports'),
            'not_found' => __('No reports found', 'reports'),
            'not_found_in_trash' => __('No reports found in Trash', 'reports')
        ),
        'public' => true,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-clipboard',
        'has_archive' => true,
        'supports' => array(
            'title'
        ),
        'can_export' => true,
        'taxonomies' => array(
            'post_tag',
            'category'
        )
    ));
}