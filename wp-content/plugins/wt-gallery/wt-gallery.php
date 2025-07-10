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
  $data_attributes = esc_attr(json_encode($atts));
  $total_items = $query->found_posts;

  if ($query->have_posts()) {
      ob_start();
      echo render_gallery_items($query, $atts, $gallery_id, $paged, $data_attributes);
      $html = ob_get_clean();

      wp_send_json_success([
        'html' => $html,
        'total' => $total_items,
        'page' => $paged,
      ]);
  } else {
      wp_send_json_success([
        'html' => '<p>No posts found.</p>',
        'total' => 0,
        'page' => $paged,
    ]);
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
      $page_banner = get_field('fellow_banner');
      if( $page_banner && is_array($page_banner) && isset($page_banner['banner_image']['url']) ) {
        return esc_html( $page_banner['banner_image']['url'] );
      } else {
          // Handle the case where the field isn't set or doesn't have the expected structure.
          return null;
      }
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
		'title'           => '',
    'subtitle'        => '',
    'show_total'      => 'false',
		'custom_class'    => '',
		'post_type'       => 'post',
		'columns'         => 1,
		'posts_per_page'  => 3,
		'orderby'         => 'date',
		'order'           => 'desc',
		'pagination'      => 'false',
		'meta_key'        => '',
		'meta_value'      => '',
		'selected_posts'  => '',
		'display_blank'   => 'false',
		'exclude_self'    => 'true',
		'taxonomy'        => '',
		'term'            => ''
	];

	$args = wp_parse_args($params, $defaults);

	return do_shortcode('[custom_gallery '.
    'title="'         . $args['title']          . '" '.
    'subtitle="'      . $args['subtitle']       . '" '.
    'show_total="'    . $args['show_total']     . '" '.
    'custom_class="'  . $args['custom_class']   . '" '.
    'post_type="'     . $args['post_type']      . '" '.
    'columns="'       . $args['columns']        . '" '.
    'posts_per_page="'. $args['posts_per_page'] . '" '.
    'orderby="'       . $args['orderby']        . '" '.
    'order="'         . $args['order']          . '" '.
    'pagination="'    . $args['pagination']     . '" '.
    'meta_key="'      . $args['meta_key']       . '" '.
    'meta_value="'    . $args['meta_value']     . '" '.
    'selected_posts="'. $args['selected_posts'] . '" '.
    'display_blank="' . $args['display_blank']  . '" '.
    'exclude_self="'  . $args['exclude_self']   . '" '.
    'taxonomy="'      . $args['taxonomy']       . '" '.
    'term="'          . $args['term']           . '" '.
    ']');
}

function custom_gallery($atts) {
  static $gallery_counter = 0;
  $gallery_counter++;

  // Set up default attributes and merge with user-supplied attributes
  $atts = shortcode_atts(array(
    'title'           => '',
    'subtitle'        => '',
    'show_total'      => '',
    'custom_class'    => '',
    'post_type'       => 'languages', // videos, languages, fellows
    'columns'         => 3,
    'posts_per_page'  => 6,
    'orderby'         => 'date',
    'order'           => 'desc',
    'meta_key'        => '',
    'meta_value'      => '',
    'pagination'      => 'false', // string true or false
    'gallery_id'      => 'gallery_' . $gallery_counter,
    'selected_posts'  => array(),
    'display_blank'   => 'false', // define whether to use the default blank state or string true or false
    'exclude_self'    => 'true', // define whether to show or hide the current post entry string true or false
    'taxonomy'        => '',
    'term'            => '',
  ), $atts, 'custom_gallery');

  // ACF Custom posts
  if (!empty($atts['selected_posts'])) {
    $selected_posts = explode(',', $atts['selected_posts']);
    $args['post__in'] = $selected_posts; // Limit query to these posts only
    $args['orderby'] = 'post__in'; // Preserve order if needed
  }

  $paged = get_query_var('paged') ? get_query_var('paged') : 1;
  // Query setup
  $args = array(
    'post_type'       => $atts['post_type'],
    'posts_per_page'  => $atts['posts_per_page'],
    'orderby'         => $atts['orderby'],
    'order'           => $atts['order'],
    'meta_key'        => $atts['meta_key'],
    'meta_value'      => $atts['meta_value'],
    'paged'           => $paged,
    'columns'         => $atts['columns'],
    'pagination'      => $atts['pagination'],
    'display_blank'   => $atts['display_blank'],
    'exclude_self'    => $atts['exclude_self'],
    'taxonomy'        => $atts['taxonomy'],
    'term'            => $atts['term'],
    'custom_class'    => $atts['custom_class'],
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

  $count = (int) $query->found_posts;
  $post_type_obj = get_post_type_object( $atts['post_type'] );

  $singular = $post_type_obj ? $post_type_obj->labels->singular_name : rtrim( $atts['post_type'], 's' );
  $plural   = $post_type_obj ? $post_type_obj->labels->name          : $singular . 's';

  $label = _n( $singular, $plural, $count, 'my-text-domain' ); // note: the empty '' at the end is for translation purposes, it can be left empty if not needed.
  $header = $atts['show_total'] === 'true'
	? $atts['title'] . '<span>' . $count . ' ' . $label . '</span>'
	: $atts['title'];

  $output = '';
  if ($query->have_posts() || $atts['display_blank']==='true') {
    $output = '<div class="' . $classes . '">';
    $output .= $atts['title'] ? '<strong class="wt_sectionHeader">'. $header .'</strong>' : '';
    $output .= $atts['subtitle'] ? '<p class="wt_subtitle">'.$atts['subtitle'].'</p>' : '';
    if ($query->have_posts()) {
      $output .= render_gallery_items($query, $atts, $atts['gallery_id'], $paged, $data_attributes);
    } else {
      $output .= '<p>There are no '.$atts['post_type'].' to display—<a href="'.home_url('/submit-a-'.rtrim($atts['post_type'], 's'), 'relative').'">yet</a>.</p>';
    }
    $output .= '</div>';
  }

  return $output;
}

add_shortcode('custom_gallery', 'custom_gallery');