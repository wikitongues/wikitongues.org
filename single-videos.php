<?php
get_header();

$youtube_id = get_field('youtube_id');
$youtube_link = get_field('youtube_link');
$language_iso_codes = get_field('language_iso_codes');
$dropbox_link = get_field('dropbox_link');
$dropbox_link_raw = str_replace("dl=0", "raw=1", $dropbox_link);
$wikimedia_commons_link = get_field('wikimedia_commons_link');
$public_status = get_field('public_status');
$video_license = get_field('video_license');
$video_license_url = array_pop(array_reverse(get_field('license_link' )));
$featured_languages = get_field('featured_languages');

$language_names_array = [];
$iso_codes_array = [];
$language_names = '';
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

  $custom_title = 'Other videos of ' . $language_names;
  $custom_post_type = 'videos';
  $custom_class = 'full';
  $custom_columns = 5;
  $custom_posts_per_page = 5;
  $custom_orderby = 'rand';
  $custom_order = 'asc';
  $custom_pagination = 'false';
  $custom_meta_key = 'language_iso_codes';
  $custom_meta_value = $language_iso_codes;
  $custom_selected_posts = '';
  echo do_shortcode('[custom_gallery title="'.$custom_title.'" custom_class="'.$custom_class.'" post_type="'.$custom_post_type.'" columns="'.$custom_columns.'" posts_per_page="'.$custom_posts_per_page.'" orderby="'.$custom_orderby.'" order="'.$custom_order.'" pagination="'.$custom_pagination.'" meta_key="'.$custom_meta_key.'" meta_value="'.$custom_meta_value.'" selected_posts="'.$custom_selected_posts.'"]');

  $cta_el = '<a href="'.home_url().'/submit-a-video">Contribute a video</a>';
  $cta_el .= '<a href="'.home_url().'/wp-content/uploads/2024/09/Wikitongues-Recording-an-Oral-History-Sep-2024.pdf">How to create an oral history</a>';
  $gallery_cta = '<div class="custom-cta-container full"><section class="custom-gallery-video-cta">'.$cta_el.'</section></div>';
  echo $gallery_cta;

echo '</main>';

get_footer();