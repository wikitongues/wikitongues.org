<div id="wt_single-languages__resources" class="wt_single-languages__contents">
	<?php
	if (!function_exists('getDomainFromUrl')) {
    function getDomainFromUrl($url) {
        $host = parse_url($url, PHP_URL_HOST);
        if (substr($host, 0, 4) === 'www.') {
            $host = substr($host, 4);
        }
        return $host;
    }
}
	?>
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
				<a href="<?php echo home_url('/submit-a-video', 'relative'); ?>">Contribute a resource</a>
			</section>
		</div>
</div>