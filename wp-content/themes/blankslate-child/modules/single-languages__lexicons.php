<?php
$title = 'dictionaries, phrase books, and other lexicons';
$title_name = $standard_name;
if (substr($standard_name, -7) !== 'anguage') {
	$title_name = $standard_name . ' language';
}
$title = $title_name . ' dictionaries, phrase books, and other lexicons';
?>
<div id="wt_single-languages__lexicons" class="wt_single-languages__contents">
	<div class="custom-gallery">
		<strong class="wt_sectionHeader"><?php echo $title; ?></strong>
		<p class="wt_subtitle">Wikitongues collects vocabulary samples of every language in the world.</p>
	</div>
	<?php
		// Gallery
		$params = [
			'title' => "{$standard_name} to other languages",
			'subtitle' => '',
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
			'subtitle' => '',
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