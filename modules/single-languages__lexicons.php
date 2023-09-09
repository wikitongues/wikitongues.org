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

			// loop
			if ( $lexicon_title ) {

				$content_block_header = $lexicon_title;

				if ( $source_languages && $target_languages ) {

					$content_block_copy;

				} elseif ( $source_languages && !$target_languages ) {

					$content_block_copy;

				} else {
					
					$content_block_copy;

				}
				
			} else {

				$content_block_header = 'hello';
				$content_block_copy = null;
			}

			$content_block_cta = get_the_permalink();

			// include content block template
			include( 'content-block--grid.php' );

		} wp_reset_postdata(); 
		?>
		</ul>
	<?php else: ?>
		<p>There are no lexicons to display yet. Submit one <a href="<?php bloginfo('url'); ?>/submit-a-video">here</a>.</p>
	<?php endif; ?>
</div>