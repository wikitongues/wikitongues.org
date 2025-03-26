<?php
$video_title = get_field('video_title');
$youtube_id = get_field('youtube_id');
$youtube_link = get_field('youtube_link');
$language_iso_codes = get_field('language_iso_codes');
$dropbox_link = get_field('dropbox_link');
$dropbox_link_raw = str_replace("dl=0", "raw=1", $dropbox_link);
$wikimedia_commons_link = get_field('wikimedia_commons_link');
$public_status = get_field('public_status');
$video_license = get_field('video_license');
$license_link = get_field('license_link');
if (is_array($license_link)) {
  $reversed_license_link = array_reverse($license_link);
  $video_license_url = array_pop($reversed_license_link);
} else {
  $video_license_url = $license_link;
}
$featured_languages = get_field('featured_languages');

$language_names_array = [];
$iso_codes_array = [];
$language_names = '';

// ====================
// Manage Language Page Titles
// ====================
if (is_singular('videos')) {
  if ($video_title) {
      echo '<script>document.title = "Wikitongues | ' . esc_js($video_title) . '";</script>';
  }
}

get_header();

if ($featured_languages && is_array($featured_languages)) {
    foreach ($featured_languages as $language_post) {
        $standard_name = get_field('standard_name', $language_post->ID);
        $iso_code = get_field('iso_code', $language_post->ID);
        if ($standard_name) {
          $language_names_array[] = $standard_name;
        }
        if ($iso_code) {
          $iso_codes_array[] = $iso_code;
        }
    }
}

if (!empty($language_names_array)) {
  $last_name = array_pop($language_names_array);
  $sentence = implode(', ', $language_names_array);
  if ($sentence) {
      $sentence .= ' and ' . $last_name;
  } else {
      $sentence = $last_name;
  }

  $language_names = $sentence;
}
// video
echo '<main class="wt_single-videos__content">';
  include( 'modules/videos-single--embed.php');

  echo '<h1>' . get_field('video_title' ) . '</h1>';

  echo '<section class="wt_single-videos__content--body">';

    // left column - video metadata
    include( 'modules/meta--videos-single.php');

    // right column - video content
    include( 'modules/main-content--videos-single.php');

  echo '</section>';

  // Gallery
  $params = [
    'title' => 'Other videos of ' . $language_names,
    'subtitle' => '',
    'post_type' => 'videos',
    'custom_class' => 'full',
    'columns' => 5,
    'posts_per_page' => 5,
    'orderby' => 'rand',
    'order' => 'asc',
    'pagination' => 'false',
    'meta_key' => 'language_iso_codes',
    'meta_value' => $language_iso_codes,
    'selected_posts' => '',
    'display_blank' => 'false',
    'exclude_self' => 'true',
    'taxonomy' => '',
    'term' => ''
  ];
  echo create_gallery_instance($params);

  $cta_el = '<a href="'.home_url('/submit-a-video', 'relative').'">Contribute a video</a>';
  $cta_el .= '<a href="'.home_url('/wp-content/uploads/2024/09/Wikitongues-Recording-an-Oral-History-Sep-2024.pdf', 'relative').'">How to create an oral history</a>';
  $gallery_cta = '<div class="custom-cta-container full"><section class="custom-gallery-video-cta">'.$cta_el.'</section></div>';
  echo $gallery_cta;

echo '</main>';

include( 'modules/newsletter.php' );

get_footer();