<div class="wt_thumbnails">
	<div class="wt_thumbnails__wrapper">
	<?php
	// run through thumbnail loop
	foreach ( $thumbnail as $post ) {
		// initiate post data
		setup_postdata( $post );

		// display different thumbnail templates per post type
		if ( $post->post_type == 'videos' ) {
			// grab video thumbnail template
			include( locate_template('modules/video-thumbnail.php') );

		} elseif ( $post->post_type == 'lexicons' ) {
			// grab lexicon thumbnail template
			include( locate_template('modules/lexicon-thumbnail.php') );

		} elseif ( $post->post_type == 'resources') {
			// if resource is published
			if ( $post->post_status == 'publish' ) {
				// grab external resource thumbnail template
				include( locate_template('modules/resource-thumbnail.php') );

			}			
		} elseif ( is_page_template('template-toolkit.php') ) {
			// grab download thumbnail template
			include( locate_template('modules/download-thumbnail.php') );

		} elseif ( $post->post_type == 'languages' ) {
			// grab languages thumbnail template
			include( locate_template('modules/language-thumbnail.php') );
			
		} else {
			// error page
			include( locate_template('modules/error.php') );
		}
	} wp_reset_postdata();
	?> <!-- to do: paginate all content sections -->
	</div>

	<?php if ( $thumbnail_cta_text ): ?>
	<a class="wt_thumbnails__cta" href="<?php echo $thumbnail_cta_link; ?>">
		<?php echo $thumbnail_cta_text; ?>
	</a>
	<?php endif; ?>
</div>