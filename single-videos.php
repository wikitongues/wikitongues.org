<?php
  get_header();

  $youtube_id = get_field( 'youtube_id' );
  $youtube_link = get_field( 'youtube_link' );
  $dropbox_link = get_field( 'dropbox_link' );
  $dropbox_link_raw = str_replace("dl=0", "raw=1", $dropbox_link);
  $wikimedia_commons_link = get_field( 'wikimedia_commons_link' );
  $public_status = get_field( 'public_status' );
  $video_license = get_field( 'video_license' );
  $video_license_url = array_pop(array_reverse(get_field( 'license_link' )));
  $featured_languages = get_field( 'featured_languages' );
  $video_thumbnail = get_field('video_thumbnail_v2');

  // video
  include( 'modules/videos-single--embed.php' );

  echo '<h1>' . get_field( 'video_title' ) . '</h1>';

  // left column - video metadata
  include( 'modules/meta--videos-single.php' );

  // right column - video content
  include( 'modules/main-content--videos-single.php' );

  // related videos (thumbnail carousel)
  // include( 'modules/carousel--thumbnail.php' );

  get_footer();
?>