<div id="wt_single-languages__videos" class="wt_single-languages__contents">
	<?php
		// Gallery
		$params = [
			'title' => 'Videos',
			'post_type' => 'videos',
			'custom_class' => '',
			'columns' => 3,
			'posts_per_page' => 6,
			'orderby' => 'date',
			'order' => 'asc',
			'pagination' => 'true',
			'meta_key' => 'language_iso_codes',
			'meta_value' => get_the_title(),
			'selected_posts' => '',
			'display_blank' => 'true',
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