<?php
$url = get_permalink();
$categories = get_the_terms(get_the_ID(), 'fellow-category');
$category_names = implode(', ', array_map('esc_html', wp_list_pluck($categories, 'name')));
$class = $atts['custom_class'];
$thumbnail_url = get_custom_image('fellows');
$fellow_year = get_field('fellow_year');
$location = get_field('fellow_location');
$fellow_language_preferred_name = get_field('fellow_language_preferred_name');
$marketing_text = get_field('marketing_text');
$thumbnail = '';

if ($thumbnail_url) {
	$thumbnail = '<div class="thumbnail" style="background-image:url('.esc_url($thumbnail_url).');" alt="' . get_the_title() . '"></div><span class="thumbnail-spacer">&nbsp;</span>';
} else {
	$thumbnail = '<div class="no-thumbnail"><p>Thumbnail unavailable</p></div><span class="thumbnail-spacer">&nbsp;</span>';
}

if ($class === "custom fundraiser") {
  echo '<li>';
  echo '<div class="thumbnail" style="background-image:url('.esc_url($thumbnail_url).');" alt="' . get_the_title() . '"></div>';
  echo '<section>';
  echo '<h2>'.$title.'<br>'.$location.'</h2>';
  echo '<p>'.$marketing_text.'</p>';
  echo '</section>';
  echo '</li>';
} else {
  echo '<li class="gallery-item">';
  echo '<a href="' . esc_url($url) . '">';
  echo $thumbnail;
  echo '<div><h6>' . $title . '</h6></div>';
  $metadata = '<div class="fellow-metadata"><h6>'.$fellow_language_preferred_name.'</h6>';
  $metadata .= '<p>'.$category_names.'</p><span><p>'.$location.'</p><p>'.$fellow_year.'</p></span></div>';
  echo $metadata;
  echo '</a>';
  echo '</li>';
}
