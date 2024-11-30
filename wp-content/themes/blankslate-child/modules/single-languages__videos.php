<div id="wt_single-languages__videos" class="wt_single-languages__contents">
	<?php
		$custom_title = 'Videos';
		$custom_post_type = 'videos';
		$custom_class = '';
		$custom_columns = 3;
		$custom_posts_per_page = 6;
		$custom_orderby = 'date';
		$custom_order = 'asc';
		$custom_pagination = 'true';
		$custom_meta_key = 'language_iso_codes';
		$custom_meta_value = get_the_title();
		$custom_selected_posts = '';
		echo do_shortcode('[custom_gallery title="'.$custom_title.'" custom_class="'.$custom_class.'" post_type="'.$custom_post_type.'" columns="'.$custom_columns.'" posts_per_page="'.$custom_posts_per_page.'" orderby="'.$custom_orderby.'" order="'.$custom_order.'" pagination="'.$custom_pagination.'" meta_key="'.$custom_meta_key.'" meta_value="'.$custom_meta_value.'" selected_posts="'.$custom_selected_posts.'"]');
	?>
	<div class="custom-cta-container">
		<section class="custom-gallery-video-cta">
			<a href="<?php echo home_url('/submit-a-video', 'relative'); ?>">Contribute a video</a>
			<a href="<?php echo home_url('/wp-content/uploads/2024/09/Wikitongues-Recording-an-Oral-History-Sep-2024.pdf', 'relative'); ?>">How to create an oral history</a>
		</section>
	</div>
</div>