<?php
$url = get_permalink();
$categories = get_the_terms(get_the_ID(), 'fellow-category');
$category_names = implode(', ', array_map('esc_html', wp_list_pluck($categories, 'name')));
$class = $atts['custom_class'];
$thumbnail_url = get_custom_image('fellows');
$fellow_year = get_field('fellow_year');
$location = get_field('fellow_location');
$standard_name = '';
$fellow_language = get_field('fellow_language');
$fellow_language_preferred_name = get_field('fellow_language_preferred_name');
$marketing_text = get_field('marketing_text');
$thumbnail = '';

if ($fellow_language instanceof WP_Post) {// Handle single language, use the global preferred name if passed
  $output_name = get_post_meta($fellow_language->ID, 'standard_name', true);
} elseif (is_array($fellow_language)) {
  if (count($fellow_language) > 1) {
    // If array, do not use preferred name since we cannot set distinct names per entry.
    foreach ($fellow_language as $language) {
      if ($language instanceof WP_Post) {
        $standard_name = get_post_meta($language->ID, 'standard_name', true);
        $output_name = $fellow_language_preferred_name ? $fellow_language_preferred_name : $standard_name;
      }
    }
  } else {
    // If single language, use preferred name if passed.
    foreach ($fellow_language as $language) {
      if ($language instanceof WP_Post) {
          $standard_name = get_post_meta($language->ID, 'standard_name', true);
          $output_name = $fellow_language_preferred_name ? $fellow_language_preferred_name : $standard_name;
      }
    }
  }
} else {
    $output .= '<span class="identifier">' . esc_html($fellow_language) . '</span>';
}

if ($thumbnail_url) {
	$thumbnail = '<div class="thumbnail" style="background-image:url('.esc_url($thumbnail_url).');" alt="' . get_the_title() . '"></div><span class="thumbnail-spacer">&nbsp;</span>';
} else {
	$thumbnail = '<div class="no-thumbnail"><p>Thumbnail unavailable</p></div><span class="thumbnail-spacer">&nbsp;</span>';
}

if ($class === "custom fundraiser") {
  echo '<li>';
  echo '<div class="thumbnail" style="background-image:url('.esc_url($thumbnail_url).');" alt="' . get_the_title() . '"></div>';
  echo '<section>';
  echo '<strong>'.$title.'<br>'.$location.'</strong>';
  echo '<p>'.$marketing_text.'</p>';
  echo '</section>';
  echo '</li>';
} else {
  echo '<li class="gallery-item">';
  echo '<a href="' . esc_url($url) . '">';
  echo $thumbnail;
  echo '<div><h5>' . $title . '</h5></div>';
  $metadata = '<div class="fellow-metadata"><h5>'.$output_name.'</h5>';
  $metadata .= '<p>'.$category_names.'</p><span><p>'.$location.'</p><p>'.$fellow_year.'</p></span></div>';
  echo $metadata;
  echo '</a>';
  echo '</li>';
}
