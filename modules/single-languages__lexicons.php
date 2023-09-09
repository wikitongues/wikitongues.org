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
			$lexicon_default_title = get_the_title();
			$lexicon_custom_title = get_field('lexicon_custom_title');
			$source_languages = get_field('source_languages');
			$target_languages = get_field('target_languages');
			$source_languages_names = array();
			$target_languages_names = array();

			foreach ( $source_languages as $post ) {
				setup_postdata( $post );

				$source_language_name = get_field('standard_name');

				array_push($source_languages_names,$source_language_name);
			} wp_reset_postdata();

			foreach ( $target_languages as $post ) {
				setup_postdata( $post );

				$target_language_name = get_field('standard_name');

				array_push($target_languages_names,$target_language_name);
			} wp_reset_postdata();
 
			// loop
			if ( $lexicon_custom_title ) {

				$content_block_header = $lexicon_custom_title;

				if ( $source_languages && $target_languages ) {

					$content_block_copy = implode(',',$source_languages_names) . ' to ' . implode(', ',$target_languages_names); 

				} elseif ( $source_languages && !$target_languages ) {

					$content_block_copy = implode(',',$source_languages_names);

				} else {
					
					$content_block_copy;

				}
				
			} else {

				$content_block_header = $lexicon_default_title;
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