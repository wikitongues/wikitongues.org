	<div id="single-languages__lexicons" class="single-languages__content">
	<p>
		<strong>Dictionaries, phrase books, and other lexicons</strong>
	</p>
	<?php if ( $lexicons ): ?>
		<ul>
		<?php 
		// loop through available lexicons
		foreach( $lexicons as $post ) {
			// foreach video, setup posts data
			setup_postdata( $post );

			// define variables
			$lexicon_title = get_field('lexicon_custom_title');
			$source_languages = get_field('source_languages');
			$target_languages = get_field('target_languages');
			$video_custom_title = get_field('video_custom_title');
			$language_names = array();
			$language_names = array(); 

			if ( $source_languages->have_posts() ) {

				while ( $source_languages->have_posts( ) ) {
					
					the_post();

					array_push( $language_names );
				}
			}

			if ( $target_languages->have_posts() ) {

				while ( $target_languages->have_posts( ) ) {
					
					the_post();

					array_push( $language_names );
				}
			}

			if ( $lexicon_title ) {

				$content_block_header = $lexicon_title;
				$content_block_copy = printArray( $language_names );

			} else {

				$content_block_header = printArray( $language_names );
				$content_block_copy = null;
			}

			$content_block_cta = get_the_permalink();

			// include content block template
			include( 'modules/content-block--thirds.php' );

		} wp_reset_postdata(); 
		?>
		</ul>
	<?php else: ?>
		<p>There are no lexicons to display yet. Submit one <a href="<?php bloginfo('url'); ?>/submit-a-video">here</a>.</p>
	<?php endif; ?>
</div>