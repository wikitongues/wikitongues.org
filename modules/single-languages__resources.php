	<div id="wt_single-languages__lexicons" class="wt_single-languages__contents">
		<p>
			<strong>External resources</strong>
		</p>
		<?php if ( $external_resources ): ?>
			<ul>
			<?php 
			// loop through available lexicons
			foreach( $external_resources as $post ) {
				// foreach video, setup posts data
				setup_postdata( $post );

				// define hard variables
				$content_block_image = null;
				$content_block_header = get_the_title();
				$content_block_copy = null;
				$content_block_cta_link = get_the_permalink();
				$content_block_cta_text = 'View';				

				// include content block template
				include( 'content-block--grid.php' );

			} wp_reset_postdata(); 
			?>
			</ul>
		<?php else: ?>
			<p>There are no resources to display—yet. <a href="<?php bloginfo('url'); ?>/submit-a-video">Recommend a resource</a>.</p>
		<?php endif; ?>
	</div>
</main><!-- move to end of last module after lexicons, etc -->
<div class="clear"></div>