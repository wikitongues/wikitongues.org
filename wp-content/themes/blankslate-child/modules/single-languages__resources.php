<div id="wt_single-languages__resources" class="wt_single-languages__contents">
		<?php
			// Gallery
			$params = [
				'title' => 'External Resources',
				'post_type' => 'resources',
				'custom_class' => '',
				'columns' => 3,
				'posts_per_page' => 6,
				'orderby' => 'date',
				'order' => 'asc',
				'pagination' => 'true',
				'meta_key' => 'resource_language',
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
				<a href="<?php echo home_url('/submit-a-resource', 'relative'); ?>">Contribute a resource</a>
			</section>
		</div>
</div>