<main class="wt_single-languages__content">
	<div id="wt_single-languages__videos" class="wt_single-languages__contents">
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
				$content_block_image = get_field('video_thumbnail_v2');
				$video_title = get_field('video_title');
				$video_custom_title = get_field('video_custom_title');
				if ( $video_custom_title ) {
					$content_block_header = $video_custom_title;
				} else {
					$content_block_header = $video_title;
				}
				$content_block_cta_link = get_the_permalink();
				$content_block_cta_text = 'Watch';

				// include content block template
				include( 'content-block--grid.php' );

			} wp_reset_postdata(); 
			?>
			</ul>
		<?php else: ?>
			<p>There are no videos to display—yet. <a href="<?php bloginfo('url'); ?>/submit-a-video">Submit a video</a>.</p>
		<?php endif; ?>
	</div>