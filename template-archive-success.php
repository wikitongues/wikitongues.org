<?php  /* Template name: Success */

get_header();

$page_banner = get_field('archive_success_banner');
include( 'modules/banner--main.php' );
include( 'modules/main-content--archive-success.php' );

$thumbnail_carousel = get_field('thumbnail_carousel');

if ( have_rows( 'thumbnail_carousel' ) ) {
  the_row();
  include( 'modules/carousel--thumbnail.php' );
}

echo '<h1 class="success_cta">Have more to say? Submit another video <a href="http://localhost:8888/wikitongues/archive/submit-a-video/">here</a>.</h1>';

get_footer();

