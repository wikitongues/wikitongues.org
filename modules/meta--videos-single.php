<div class="wt_meta">	
	<div class="wt_meta__video-downloads">
		<p>
			<strong class="wt_text--uppercase">Video file downloads</strong>
		</p>

		<p>
		<?php if ( $public_status === 'Public' ) {

			if ( $dropbox_link ) {

				echo '<a href="'. $dropbox_link . '" target="_blank">
					Dropbox (.mp4)</a>';

			}

			if ( $wikimedia_commons_link ) {

				echo '<br /><a href="'. $wikimedia_commons_link . '" target="_blank">Wikimedia Commons (.webm)</a>';

			}

			// later version: captions
		} elseif ( $public_status === 'Processing' ) {

			echo 'File downloads will be ready when this is video is finished processing. Please check back soon.'; // later version: 'subscribe for notifications'

		} elseif ( $public_status === 'Private') {

			echo 'File downloads are disabled because the creator of this video has chosen to make this video private.';

		} ?>
		</p>
	</div>

	<ul class="wt_meta__featured-languages">
	<?php foreach( $featured_languages as $post ): setup_postdata( $post ); ?>
		<li>
			<strong class="wt_text--uppercase">About <?php the_field( 'standard_name' ); ?></strong>
			<p>
				<strong>Countries of origin</strong><br/>
				<span><?php the_field( 'countries_of_origin' ); ?></span>
			</p>
			<p>
				<strong>Linguistic genealogy</strong><br/>
				<span><?php the_field( 'linguistic_genealogy' ); ?></span>
			</p>
			<p>
				<strong>Writing system</strong><br/>
				<span><?php the_field( 'writing_system' ); ?></span>
			</p>
			<p>
				<strong>EGIDS status</strong><br/>
				<span><?php the_field( 'egids_status' ); ?></span>
			</p>
		</li>
	<?php enforeach; wp_reset_postdata(); ?> 
	</ul>
	
</div>