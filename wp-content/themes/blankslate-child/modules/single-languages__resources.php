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
			$custom_title = 'External Resources';
			$custom_post_type = 'resources';
			$custom_class = '';
			$custom_columns = 3;
			$custom_posts_per_page = 6;
			$custom_orderby = 'date';
			$custom_order = 'asc';
			$custom_pagination = 'true';
			$custom_meta_key = 'resource_language';
			$custom_meta_value = $language;
			$custom_selected_posts = '';
			echo do_shortcode('[custom_gallery title="'.$custom_title.'" custom_class="'.$custom_class.'" post_type="'.$custom_post_type.'" columns="'.$custom_columns.'" posts_per_page="'.$custom_posts_per_page.'" orderby="'.$custom_orderby.'" order="'.$custom_order.'" pagination="'.$custom_pagination.'" meta_key="'.$custom_meta_key.'" meta_value="'.$custom_meta_value.'" selected_posts="'.$custom_selected_posts.'"]');
		?>
		<div class="custom-cta-container">
			<section class="custom-gallery-video-cta">
				<a href="<?php echo home_url(); ?>/submit-a-video">Contribute a resource</a>
			</section>
		</div>
</div>