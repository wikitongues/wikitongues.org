<div class="single-languages__videos">
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
			$video_title = get_field('video_title');
			$video_custom_title = get_field('video_custom_title');
			if ( $video_custom_title ) {
				$content_block_header = $video_custom_title;
			} else {
				$content_block_header = $video_title;
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