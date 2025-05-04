<li class="gallery-item">
  <?php
	$language_query = new WP_Query(array(
		'post_type' => 'languages',
		'posts_per_page' => -1,
		'meta_query' => array(
			array(
				'key' => 'territories',
				'value' => $query->post->ID,
				'compare' => 'LIKE'
			)
		)
	));
  $url = get_permalink();
  $title = get_the_title();
	$language_count = '<aside>'.esc_html($language_query->post_count).'</aside>';

	usort($language_query->posts, function($a, $b) {
		$speakers_a = get_field('speakers_recorded', $a->ID);
		$speakers_b = get_field('speakers_recorded', $b->ID);
		$count_a = is_array($speakers_a) ? count($speakers_a) : 0;
		$count_b = is_array($speakers_b) ? count($speakers_b) : 0;
		return $count_b - $count_a; // Descending order
	});
	$random_posts = wp_list_pluck($language_query->posts, 'ID');
	shuffle($random_posts);
	$random_posts = array_slice($random_posts, 0, 4);
	$random_posts = array_slice($language_query->posts, 0, 4);

	$thumbnail_object = '';

	foreach ($random_posts as $post_id) {
		$language_name = get_field('standard_name', $post_id);
		$speakers_recorded = get_field('speakers_recorded', $post_id);
		$speakers_count = is_array($speakers_recorded) ? count($speakers_recorded) : 0;
		$video_query = get_videos_by_featured_language(get_the_title($post_id));
		if ($video_query->have_posts()) {
			while ($video_query->have_posts()) {
				$video_query->the_post();
				$thumbnail = get_custom_image('videos');
				$video_title = $language_name;
				$thumbnail_object .= '<div class="thumbnail" style="background-image:url('.esc_url($thumbnail).');" alt="' . get_the_title() . '" title=""> <p>' . $video_title . '</p></div>';
			}
		} else {
			$thumbnail_object .= '<div class="no-thumbnail"><p>'.$language_name.'</p></div>';
		}
	}

  echo '<a href="' . esc_url($url) . '">';
  echo '<div class="metadata"><h6>' . $title . '</h6>' . $language_count . '</div>';
  echo '<div class="languages">';
  echo $thumbnail_object;
  echo '</div>';
  echo '</a>';
  ?>
</li>
