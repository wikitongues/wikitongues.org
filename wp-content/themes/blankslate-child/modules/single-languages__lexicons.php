<div id="wt_single-languages__lexicons" class="wt_single-languages__contents">
	<div class="custom-gallery"><strong class="wt_sectionHeader">Dictionaries, phrase books, and other lexicons</strong></div>
	<?php
		// Gallery
		$params = [
			'title' => "{$standard_name} to other languages",
			'post_type' => 'lexicons',
			'custom_class' => '',
			'columns' => 3,
			'posts_per_page' => 6,
			'orderby' => 'date',
			'order' => 'asc',
			'pagination' => 'true',
			'meta_key' => 'source_languages',
			'meta_value' => $language,
			'selected_posts' => '',
			'display_blank' => 'true',
			'taxonomy' => '',
			'term' => '',
		];
		echo create_gallery_instance($params);

		$params = [
			'title' => "Other languages to {$standard_name}",
			'post_type' => 'lexicons',
			'custom_class' => '',
			'columns' => 3,
			'posts_per_page' => 6,
			'orderby' => 'date',
			'order' => 'asc',
			'pagination' => 'true',
			'meta_key' => 'target_languages',
			'meta_value' => $language,
			'selected_posts' => '',
			'display_blank' => 'true',
			'taxonomy' => '',
			'term' => '',
		];
		echo create_gallery_instance($params);
	?>

	<div class="custom-cta-container">
		<section class="custom-gallery-video-cta">
			<a href="<?php echo home_url('/submit-a-lexicon', 'relative'); ?>">Contribute a lexicon</a>
		</section>
	</div>
</div>