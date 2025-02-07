<?php
/*
Plugin Name: Wikitongues Gallery Plugin
Description: A plugin to create a customizable gallery displaying posts.
Version: 1.0
Author: Frederico Andrade
Author URI: https://www.wikitongues.org/
*/

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

function custom_gallery_plugin_activate() {
  // Activation code here, if needed
}
register_activation_hook(__FILE__, 'custom_gallery_plugin_activate');

function custom_gallery_plugin_deactivate() {
  // Deactivation code here, if needed
}
register_deactivation_hook(__FILE__, 'custom_gallery_plugin_deactivate');

function custom_gallery_enqueue_scripts() {
  // Enqueue jQuery (if not already included)
  wp_enqueue_script('jquery');

  // Enqueue custom script for AJAX handling
  wp_enqueue_script('custom-gallery-ajax', plugin_dir_url(__FILE__) . '/js/custom-gallery-ajax.js', array('jquery'), null, true);

  // Localize the script with the AJAX URL and nonce
  wp_localize_script('custom-gallery-ajax', 'custom_gallery_ajax_params', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('custom_gallery_nonce'),
  ));
}
add_action('wp_enqueue_scripts', 'custom_gallery_enqueue_scripts');

require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/queries.php';
require_once plugin_dir_path(__FILE__) . 'includes/render_gallery_items.php';


// AJAX callback function to load more gallery items
function load_custom_gallery_ajax_callback() {
  check_ajax_referer('custom_gallery_nonce', 'nonce'); // Security check

  $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
  $gallery_id = isset($_POST['gallery_id']) ? sanitize_text_field($_POST['gallery_id']) : '';

  // Retrieve gallery attributes from the AJAX request
  $atts = isset($_POST['gallery_atts']) ? json_decode(stripslashes($_POST['gallery_atts']), true) : array();

  // Ensure 'paged' is set correctly
  $atts['paged'] = $paged;

  // Fetch query results
  $query = get_custom_gallery_query($atts);

  if ($query->have_posts()) {
      ob_start();
      echo render_gallery_items($query, $atts, $gallery_id, $paged, $data_attributes);
      echo ob_get_clean();
  } else {
      echo '<p>No posts found.</p>';
  }

  wp_die(); // End the AJAX request
}
add_action('wp_ajax_load_custom_gallery', 'load_custom_gallery_ajax_callback');
add_action('wp_ajax_nopriv_load_custom_gallery', 'load_custom_gallery_ajax_callback');

// Function to get the custom image based on post type
function get_custom_image($post_type) {
  switch ($post_type) {
    case 'videos':
      $video_thumbnail = wp_get_attachment_url(get_field('video_thumbnail_v2'));
      return $video_thumbnail;
    case 'fellows':
      return wp_get_attachment_url(get_field('fellow_headshot'));
    case 'languages':
      return null; // No image field available
    default:
      return null;
  }
}

function get_custom_title($post_type) {
  switch ($post_type) {
    case 'videos':
      return  get_field('video_title');
    case 'fellows':
      $first_name = get_field('first_name');
      $last_name = get_field('last_name');
      $full_name = $first_name . ' ' . $last_name;
      return $full_name;
    case 'languages':
      return get_field('standard_name');
    default:
      return null;
  }
}

function create_gallery_instance($params) {
	$defaults = [
		'title' => '',
		'custom_class' => '',
		'post_type' => 'post',
		'columns' => 1,
		'posts_per_page' => 3,
		'orderby' => 'date',
		'order' => 'desc',
		'pagination' => 'false',
		'meta_key' => '',
		'meta_value' => '',
		'selected_posts' => '',
		'display_blank' => 'true',
		'taxonomy' => '',
		'term' => ''
	];

	$args = wp_parse_args($params, $defaults);

	return do_shortcode('[custom_gallery title="'.
  $args['title'].'" custom_class="'.
  $args['custom_class'].'" post_type="'.
  $args['post_type'].'" columns="'.
  $args['columns'].'" posts_per_page="'.
  $args['posts_per_page'].'" orderby="'.
  $args['orderby'].'" order="'.
  $args['order'].'" pagination="'.
  $args['pagination'].'" meta_key="'.
  $args['meta_key'].'" meta_value="'.
  $args['meta_value'].'" selected_posts="'.
  $args['selected_posts'].'" display_blank="'.
  $args['display_blank'].'" taxonomy="'.
  $args['taxonomy'].'" term="'.
  $args['term'].'"]');
}

function custom_gallery($atts) {
  static $gallery_counter = 0;
  $gallery_counter++;

  // Set up default attributes and merge with user-supplied attributes
  $atts = shortcode_atts(array(
    'title' => '',
    'custom_class' => '',
    'post_type' => 'languages', // videos, languages, fellows
    'columns' => 3,
    'posts_per_page' => 6,
    'orderby' => 'date',
    'order' => 'DESC',
    'meta_key' => '',
    'meta_value' => '',
    'pagination' => 'true', // string true or false
    'gallery_id' => 'gallery_' . $gallery_counter,
    'selected_posts' => array(),
    'display_blank' => 'true', // define whether to use the default blank state or string true or false
    'taxonomy' => '',
    'term' => '',
  ), $atts, 'custom_gallery');

  $paged = get_query_var('paged') ? get_query_var('paged') : 1;

  // ACF Custom posts
  if (!empty($atts['selected_posts'])) {
    $selected_posts = explode(',', $atts['selected_posts']);
    $args['post__in'] = $selected_posts; // Limit query to these posts only
    $args['orderby'] = 'post__in'; // Preserve order if needed
  }

  // Query setup
  $args = array(
    'post_type' => $atts['post_type'],
    'posts_per_page' => $atts['posts_per_page'],
    'orderby' => $atts['orderby'],
    'order' => $atts['order'],
    'meta_key' => $atts['meta_key'],
    'meta_value' => $atts['meta_value'],
    'paged' => $paged,
    'columns' => $atts['columns'],
    'pagination' => $atts['pagination'],
    'display_blank' => $atts['display_blank'],
    'taxonomy' => $atts['taxonomy'],
    'term' => $atts['term'],
  );


  // Merge in any additional arguments (like selected posts)
  if (!empty($selected_posts)) {
      $args['post__in'] = $selected_posts;
  }

  $data_attributes = esc_attr(json_encode($args));

  $query = get_custom_gallery_query($args);

  $classes = 'custom-gallery ' . $atts['post_type'];
  if (!empty($atts['custom_class'])) {
    $classes .= ' ' . $atts['custom_class'];
  }

  $output = '';
  if ($query->have_posts()) {
    $output = '<div class="' . $classes . '">';
      if ($atts['title']) {
        $output .= '<h2 class="wt_sectionHeader">'.$atts['title'].'</h2>';
      }
      $output .= render_gallery_items($query, $atts, $atts['gallery_id'], $paged, $data_attributes);
    $output .= '</div>';
  } else {
      if ($atts['display_blank']==='true') {
        $output = '<div class="' . $classes . '">';
        if ($atts['title']) {
          $output .= '<h2 class="wt_sectionHeader">'.$atts['title'].'</h2>';
        }
        $output .= '<p>There are no other '.$atts['post_type'].' to display—<a href="'.home_url('/submit-a-'.rtrim($atts['post_type'], 's'), 'relative').'">yet</a>.</p>';
        $output .= '</div>';
      }
  }

  return $output;
}

add_shortcode('custom_gallery', 'custom_gallery');