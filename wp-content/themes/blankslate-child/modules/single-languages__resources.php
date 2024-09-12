<div id="wt_single-languages__resources" class="wt_single-languages__contents">
	<h2 class="wt_sectionHeader">External resources</h2>
	<?php if ( $external_resources ): ?>
		<ul>
		<?php
		// loop through available lexicons
		foreach( $external_resources as $post ) {
			// foreach video, setup posts data
			setup_postdata( $post );

			// define hard variables
			$resource_post_title = get_the_title();
			$resource_custom_title = get_field('resource_title');
			$content_block_image = null;
			$content_block_copy = null;
			$content_block_cta_link = get_field('resource_url');
			$content_block_cta_text = 'View Resource';

			// define conditional variables
			if ( $resource_custom_title ) {
				$content_block_header = $resource_custom_title;
			} else {
				$content_block_header = get_the_title();
			}

			// include content block template
			include( 'content-block--grid.php' );

		} wp_reset_postdata();
		?>
		</ul>
	<?php else: ?>
		<p>There are no resources to display—yet. <a href="<?php echo home_url() ;?>/submit-a-resource">Recommend a resource</a>.</p>
	<?php endif; ?>
</div>