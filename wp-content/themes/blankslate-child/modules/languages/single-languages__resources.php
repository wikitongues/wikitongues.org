<div id="wt_single-languages__resources" class="wt_single-languages__contents">
		<?php
			// Gallery
			$title = 'External resources';
			$title_name = $standard_name;
			if (substr($standard_name, -7) !== 'anguage') {
				$title_name .= ' language';
			}
			$title = $title_name . ' external resources';
			$params = [
				'title' => $title,
				'subtitle' => 'Wikitongues indexes language resources from across the internet.',
				'show_total' => 'false',
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
				'exclude_self' => 'true',
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