<?php
function render_gallery_items($query, $atts, $gallery_id, $paged, $data_attributes) {
  ob_start();

  $classes = 'gallery-container';
  if (!empty($atts['pagination']) && $atts['pagination'] === 'true' && $query->max_num_pages > 1) {
      $classes .= ' paginated';
  }

  echo '<div class="'.$classes.'" id="' . esc_attr($atts['gallery_id']) . '" data-attributes="' . $data_attributes . '">';
  echo '<ul class="gallery-list" style="grid-template-columns: repeat('.$atts['columns'].', 1fr);">';

  while ($query->have_posts()) {
      $query->the_post();
      $post_type = $atts['post_type']; // Use post type dynamically
      $title = get_custom_title($atts['post_type']);

      // Include the template for the post type
      $template_file = plugin_dir_path(__FILE__) . 'templates/gallery-' . $post_type . '.php';
      if (file_exists($template_file)) {
          include $template_file;
      } else {
          echo '<li>No template found for ' . esc_html($post_type) . '</li>';
      }
  }

  echo '</ul>';
  if ($atts['pagination'] === 'true' && $query->max_num_pages > 1) {
      echo generate_gallery_pagination($query, $gallery_id, $paged);
  }
  echo '</div>';

  wp_reset_postdata();

  return ob_get_clean();
}

function generate_gallery_pagination($query, $gallery_id, $paged) {
  $max_pages = $query->max_num_pages;
  $pagination_html = '<ul class="gallery-pagination" data-gallery-id="' . esc_attr($gallery_id) . '">';

  $pages_to_show = 9;
  $half_pages_to_show = floor($pages_to_show / 2);

  // Start and end page calculation
  $start_page = max(1, $paged - $half_pages_to_show);
  $end_page = min($max_pages, $paged + $half_pages_to_show);

  // Adjust if we are near the beginning or end
  if ($paged <= $half_pages_to_show) {
      $end_page = min($max_pages, $pages_to_show);
  } elseif ($paged + $half_pages_to_show >= $max_pages) {
      $start_page = max(1, $max_pages - $pages_to_show + 1);
  }

  // Generate pagination links
  // Add "Previous" button if not on the first page
  $prev = '';
  if ($paged > 1) {
    $prev = '<a href="#" class="page-numbers" data-page="' . ($paged - 1) . '" data-gallery-id="' . esc_attr($gallery_id) . '">Previous</a>';
  }
  $pagination_html .= '<li class="page-helper">'.$prev.'</li>';

  // Loop through the range of pages to display
  for ($i = $start_page; $i <= $end_page; $i++) {
      $active_class = ($i == $paged) ? ' active' : ''; // Highlight current page
      $pagination_html .= '<li><a href="#" class="page-numbers' . $active_class . '" data-page="' . $i . '" data-gallery-id="' . esc_attr($gallery_id) . '">' . $i . '</a></li>';
  }

  // Add "Next" button if not on the last page
  $next = '';
  if ($paged < $max_pages) {
      $next .= '<a href="#" class="page-numbers" data-page="' . ($paged + 1) . '" data-gallery-id="' . esc_attr($gallery_id) . '">Next</a>';
  }
  $pagination_html .= '<li class="page-helper">'.$next.'</li>';

  $pagination_html .= '</ul>';
  return $pagination_html;
}