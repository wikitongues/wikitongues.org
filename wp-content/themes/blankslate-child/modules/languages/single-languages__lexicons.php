<?php
$title      = 'dictionaries, phrase books, and other lexicons';
$title_name = $standard_name;
if ( substr( $standard_name, -7 ) !== 'anguage' ) {
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
		$params = wt_gallery_params(
			array(
				'title'          => "{$standard_name} to other languages",
				'post_type'      => 'lexicons',
				'show_total'     => 'false',
				'columns'        => 3,
				'posts_per_page' => 6,
				'orderby'        => 'date',
				'meta_key'       => 'source_languages',
				'meta_value'     => $language,
				'display_blank'  => 'true',
				'exclude_self'   => 'true',
			)
		);
		echo create_gallery_instance( $params );

		$params = wt_gallery_params(
			array(
				'title'          => "Other languages to {$standard_name}",
				'post_type'      => 'lexicons',
				'show_total'     => 'false',
				'columns'        => 3,
				'posts_per_page' => 6,
				'orderby'        => 'date',
				'meta_key'       => 'target_languages',
				'meta_value'     => $language,
				'display_blank'  => 'true',
				'exclude_self'   => 'true',
			)
		);
		echo create_gallery_instance( $params );
		?>

	<div class="custom-cta-container">
		<section class="custom-gallery-video-cta">
			<a href="<?php echo home_url( '/submit-a-lexicon', 'relative' ); ?>">Contribute a lexicon</a>
		</section>
	</div>
</div>