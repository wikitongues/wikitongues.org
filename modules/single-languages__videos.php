<div id="wt_single-languages__videos" class="single-languages__contents">
	<p>
		<strong>Videos</strong>
	</p>
	<?php if ( $videos ): ?>
		<ul>
		<?php 
		// loop through available videos
		foreach( $videos as $post ) {
			// foreach video, setup posts data
			setup_postdata( $post );

			// define variables
			$content_block_image = get_sub_field('video_thumbnail');
			$video_title = get_field('video_title');
			$video_custom_title = get_field('video_custom_title');
			if ( $video_custom_title ) {
				$content_block_header = $video_custom_title;
			} else {
				$content_block_header = $video_title;
			}
			$content_block_cta = get_the_permalink();

			// include content block template
			include( 'content-block--thirds.php' );

		} wp_reset_postdata(); 
		?>
		</ul>
	<?php else: ?>
		<p>There are no videos to display yet. Submit one <a href="<?php bloginfo('url'); ?>/submit-a-video">here</a>.</p>
	<?php endif; ?>
</div>