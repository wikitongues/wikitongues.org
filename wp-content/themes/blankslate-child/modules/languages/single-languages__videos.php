<div id="wt_single-languages__videos" class="wt_single-languages__contents">
	<?php
		// Gallery
		$title = 'Videos';
		$title_name = $standard_name;
		if (substr($standard_name, -7) !== 'anguage') {
      $title_name .= ' language';
		}
		$title = $title_name . ' videos';
		$params = [
			'title' => $title,
			'subtitle' => 'Wikitongues crowd-sources video samples of every language in the world.',
			'show_total' => 'true',
			'post_type' => 'videos',
			'custom_class' => '',
			'columns' => 3,
			'posts_per_page' => 6,
			'orderby' => 'date',
			'order' => 'asc',
			'pagination' => 'true',
			'meta_key' => 'featured_languages',
			'meta_value' => get_the_title(),
			'selected_posts' => '',
			'display_blank' => 'true',
			'exclude_self' => 'true',
			'taxonomy' => '',
			'term' => ''
		];
		echo create_gallery_instance($params);
	?>
	<div class="custom-cta-container">
		<section class="custom-gallery-video-cta">
			<a href="<?php echo home_url('/submit-a-video', 'relative'); ?>">Contribute a video</a>
			<a href="<?php echo home_url('/wp-content/uploads/2024/09/Wikitongues-Recording-an-Oral-History-Sep-2024.pdf', 'relative'); ?>">How to create an oral history</a>
		</section>
	</div>
</div>