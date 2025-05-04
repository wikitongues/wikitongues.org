<?php  /* Template name: Success */

get_header();

$page_banner = get_field('archive_success_banner');
include( 'modules/banners/banner--main.php' );

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

			// Gallery
			$params = [
				'title' => get_sub_field('custom_gallery_title'),
				'subtitle' => '',
				'show_total' => 'false',
				'post_type' => 'videos',
				'custom_class' => 'full',
				'columns' => 3,
				'posts_per_page' => 3,
				'orderby' => 'rand',
				'order' => 'asc',
				'pagination' => 'false',
				'meta_key' => '',
				'meta_value' => '',
				'selected_posts' => esc_attr($post_ids),
				'display_blank' => 'false',
				'exclude_self' => 'true',
				'taxonomy' => '',
				'term' => '',
			];
			echo create_gallery_instance($params);
		}
  }
}
?>
	<div class="success_cta">
		<h2>Have more to say?</h2>
			<section>
				<a href="<?php echo home_url('/archive/submit-a-video/', 'relative'); ?>">Submit another video here.</a>
			</section>
		</div>
	<?php
	include( 'modules/newsletter.php' );

get_footer();
