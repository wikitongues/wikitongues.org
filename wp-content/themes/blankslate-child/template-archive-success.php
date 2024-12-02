<?php  /* Template name: Success */

get_header();

$page_banner = get_field('archive_success_banner');
include( 'modules/banner--main.php' );

include( 'modules/main-content--archive-success.php' ); // Success page FAQ

if ( have_rows( 'custom_gallery_posts' ) ) {
  while ( have_rows( 'custom_gallery_posts') ) {
    the_row();
    $row_id = get_sub_field('custom_gallery_id');
    if ( $row_id === 'videos') {
      $custom_posts = get_sub_field('custom_gallery_post');

      if ($custom_posts) {
        $post_ids = implode(',', wp_list_pluck($custom_posts, 'ID'));
      }
      $custom_title = get_sub_field('custom_gallery_title');
			$custom_post_type = 'videos'; // videos, languages, fellows
			$custom_class = 'full';
			$custom_columns = 3;
			$custom_posts_per_page = 3;
			$custom_orderby = 'rand';
			$custom_order = 'asc';
			$custom_pagination = 'false'; // string true or false
			$custom_meta_key = '';
			$custom_meta_value = '';
			$custom_selected_posts = esc_attr($post_ids);
			echo do_shortcode('[custom_gallery title="'.$custom_title.'" custom_class="'.$custom_class.'" post_type="'.$custom_post_type.'" columns="'.$custom_columns.'" posts_per_page="'.$custom_posts_per_page.'" orderby="'.$custom_orderby.'" order="'.$custom_order.'" pagination="'.$custom_pagination.'" meta_key="'.$custom_meta_key.'" meta_value="'.$custom_meta_value.'" selected_posts="'.$custom_selected_posts.'"]');
		}
  }
}

echo '<div class="success_cta"><h1>Have more to say?</h1> <br> <h2>Submit another video <a href="'.home_url('/archive/submit-a-video/', 'relative').'">here</a>.</h2></div>';

include( 'modules/newsletter.php' );

get_footer();
