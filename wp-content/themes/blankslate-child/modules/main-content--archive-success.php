<?php
if (have_rows('custom_faq_posts')) {
  echo '<div class="wt_archive__faq">';
  echo '<h1>Frequently Asked Questions</h1>';
	echo '<ul>';
	while (have_rows('custom_faq_posts')) {
			the_row();
			$custom_posts = get_sub_field('custom_gallery_post');

			if ($custom_posts) {
					foreach ($custom_posts as $post) {
							setup_postdata($post);

							echo '<li>';
							echo '<h2>'.esc_html(get_the_title($post)).'</h2>';
              echo '<p>'.apply_filters('the_content', get_the_content($post)).'</p>';
							echo '</li>';
					}

					wp_reset_postdata();
			}
	}
	echo '</ul>';
  echo '</div>';
}
?>