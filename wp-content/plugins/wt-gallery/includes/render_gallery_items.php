<?php
function render_gallery_items($query, $atts, $gallery_id, $paged, $data_attributes) {
  ob_start();

  $classes = 'gallery-container';
  // $atts['pagination'] is a string
  if (!empty($atts['pagination']) && $atts['pagination'] === 'true' && $query->max_num_pages > 1) {
    $classes .= ' paginated';
  }

  echo '<div class="'.$classes.'" id="' . esc_attr($atts['gallery_id']) . '" data-attributes="' . $data_attributes . '">';

  echo '<ul class="gallery-list" style="grid-template-columns: repeat('.$atts['columns'].', 1fr);">';

  while ($query->have_posts()) {
    $query->the_post();
    $thumbnail = get_custom_image($atts['post_type']);
    $video_thumbnail_url = '';
    $video_thumbnail_object = '';
    $language_thumbnail_object = '';
    $iso_code_element = '';
    $fellow_el = '';
    $title = get_custom_title($atts['post_type']);
    $language_family = get_field('linguistic_genealogy');
    $nations_of_origin = get_field('nations_of_origin');
    $fellow_nation = get_field('fellow_location');
    $fellow_language_preferred_name = get_field('fellow_language_preferred_name');
    $writing_systems = get_field('writing_systems');
    $iso_code = get_the_title();
    $language_iso_codes = get_field('language_iso_codes');
    $post_link = get_permalink();
    $featured_languages = get_field('featured_languages');

    $video_query = get_videos_by_featured_language($iso_code);

    if ($thumbnail) {
      if (is_array($thumbnail)) {
        $video_thumbnail_url = $thumbnail['url'];
      } else {
        $video_thumbnail_url = $thumbnail;
      }
    }

    echo '<li class="gallery-item">';

    if ($atts['post_type'] === 'videos') {
      if ($video_thumbnail_url) {
        $video_thumbnail_object = '<div class="thumbnail" style="background-image:url('.esc_url($video_thumbnail_url).');" alt="' . get_the_title() . '"></div>';
      } else {
        $video_thumbnail_object = '<div class="no-thumbnail"><p>Thumbnail unavailable</p></div>';
      }
    }
    if ($atts['post_type'] === 'fellows') {
      if ($video_thumbnail_url) {
        $video_thumbnail_object = '<div class="thumbnail" style="background-image:url('.esc_url($video_thumbnail_url).');" alt="' . get_the_title() . '"></div><span>&nbsp;</span>';
      } else {
        $video_thumbnail_object = '<div class="no-thumbnail"><p>Thumbnail unavailable</p></div><span>&nbsp;</span>';
      }
      $fellow_el = '<div class="fellow-metadata"><p>'.$fellow_nation.'</p><h3>'.$fellow_language_preferred_name.'</h3><p>Revitalization</p></div>';
    }

    if ($atts['post_type'] === 'languages') {
      $iso_code_element = '<aside>'.esc_html(get_the_title()).'</aside>';
      if ($video_query->have_posts()) {
        while ($video_query->have_posts()) {
          $video_query->the_post();
          $thumbnail = get_custom_image('videos');
          $video_title = get_custom_title('videos');
          $language_thumbnail_object .= '<div class="thumbnail" style="background-image:url('.esc_url($thumbnail).');" alt="' . get_the_title() . '"> <p>' . $video_title . '</p></div>';
        }
      } else {
        $language_thumbnail_object .= '<div class="no-thumbnail"><p>No resources found to display – Yet.</p></div>';
      }
    }

    echo '<a href="' . $post_link . '">' . $video_thumbnail_object . '<div><h3>' . $title . '</h3>' . $iso_code_element . '</div>' . $language_thumbnail_object . $fellow_el . '</a>';

    echo '</li>';
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