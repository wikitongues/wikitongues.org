<div id="wt_single-languages__lexicons" class="wt_single-languages__contents">
	<h2 class="wt_sectionHeader">Dictionaries, phrase books, and other lexicons</h2>
	<?php if ( $lexicons ): ?>
		<ul>
		<?php
		// loop through available lexicons
		foreach( $lexicons as $post ) {
			// foreach video, setup posts data
			setup_postdata( $post );

			// define hard variables
			$content_block_image = null;
			$lexicon_default_title = get_the_title();
			$lexicon_custom_title = get_field('lexicon_custom_title');
			$source_languages = get_field('source_languages');
			$target_languages = get_field('target_languages');
			$source_language_name = $source_languages->standard_name;
			$target_languages_names = array();
			$dropbox_link = get_field('dropbox_link');
			$external_link = get_field('external_link');
			$content_block_cta_text = 'View lexicon';

			// define conditional variables

			// grab language names from target languages
			foreach ( $target_languages as $post ) {
				setup_postdata( $post );

				$target_language_name = get_field('standard_name');

				// store language names in an array
				array_push($target_languages_names,$target_language_name);

			} wp_reset_postdata();

			// lexicon has a custom title
			if ( $lexicon_custom_title ) {

				// display custom title
				$content_block_header = $lexicon_custom_title;

			} else {

				// display default title
				$content_block_header = $lexicon_default_title;

			}

			// if source languages and target languages exist
			if ( $source_languages && $target_languages ) {

				// display both
				$content_block_copy = $source_language_name . ' to ' . implode(', ',$target_languages_names);

			// if only source languages are available
			} elseif ( $source_languages && !$target_languages ) {

				// display those
				$content_block_copy = $source_language_name . ' document';

			// if source and target languages are both empthy
			} else {

				// variable is null
				$content_block_copy = null;

			}

			// if dropbox link
			if ( $dropbox_link ) {
				// if dropbox link
				$content_block_cta_link = $dropbox_link;
			} elseif ( $external_link ) {
				// if external link
				$content_block_cta_link = $external_link;
			} else {
				//
			}

			// include content block template
			include( 'content-block--grid.php' );

		} wp_reset_postdata();
		?>
		</ul>
	<?php else: ?>
		<p>There are no lexicons to display—yet. <a href="<?php echo home_url('/submit-a-lexicon', 'relative') ;?>">Submit a lexicon</a>.</p>
	<?php endif; ?>
</div>