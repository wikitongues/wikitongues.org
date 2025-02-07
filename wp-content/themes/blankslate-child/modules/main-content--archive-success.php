<?php
if (have_rows('custom_faq_posts')) {
  echo '<div class="wt_archive__faq">';
  echo '<h4>Frequently Asked Questions</h4>';
	echo '<ul>';
	while (have_rows('custom_faq_posts')) {
			the_row();
			$custom_posts = get_sub_field('custom_gallery_post');

			if ($custom_posts) {
					foreach ($custom_posts as $post) {
							setup_postdata($post);

							echo '<li>';
							echo '<strong>'.esc_html(get_the_title($post)).'</strong>';
              echo apply_filters('the_content', get_the_content($post));
							echo '</li>';
					}

					wp_reset_postdata();
			}
	}
	echo '</ul>';
  echo '</div>';
}
?>