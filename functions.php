<?php
require_once('includes/class-wt-rest-posts-controller.php');

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
add_action( 'wp_enqueue_scripts', 'wt_enqueue_js' );

function wt_enqueue_js() {
    wp_register_script(
        'wt_js',
        get_stylesheet_directory_uri() . '/js/custom.js', 
        array('jquery'), true);

    wp_enqueue_script('wt_js');
}
  
// remove header bump from core css output
// add_action('get_header', 'my_filter_head');

// function my_filter_head() {
//    remove_action('wp_head', '_admin_bar_bump_cb');
// } 

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

// add footer menu
function wt_footer_menu() {
  register_nav_menu('footer-menu',__( 'Footer Menu' ));
}

add_action( 'init', 'wt_footer_menu' );

// Register custom query vars -https://codex.wordpress.org/Plugin_API/Filter_Reference/query_vars

function wt_register_query_vars($vars)
{
    $vars[] = 'site_search';
    $vars[] = 'videos_search';
    $vars[] = 'languages_search';
    return $vars;
}
add_filter('query_vars', 'wt_register_query_vars');

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

// Add Google Maps - consider deprecating
// function my_acf_google_map_api( $api ){
//     $api['key'] = 'AIzaSyBLLj4cU0Q9fvHECR-OizyBuMvEt7jHua8';
//     return $api;
// }
// add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');

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