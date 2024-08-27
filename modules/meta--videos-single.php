<div class="wt_meta--videos-single">
	<div class="wt_meta__video-downloads">
		<?php
			if ( $video_license && $video_license !== 'none' ) {
				echo '<h2>Video license</h2>';
				echo '<p><a class="license" href="' . $video_license_url . '">'. $video_license . '</a></p>';
			}
		?>
		<h2>Video file downloads</h2>

		<p>
		<?php if ( $public_status === 'Public' ) {
			if ( $dropbox_link && $dropbox_link !== 'none' ) {

				echo '<a href="'. $dropbox_link . '" target="_blank">
					Dropbox (.mp4)</a>';

			}

			if ( $wikimedia_commons_link ) {

				echo '<br /><a href="'. $wikimedia_commons_link . '" target="_blank">Wikimedia Commons (.webm)</a>';

			}

			if ( $dropbox_link === 'none' && !$wikimedia_commons_link ) {

				echo 'File downloads are currently unavailable for this video.';

			}

			if ( !$dropbox_link && !$wikimedia_commons_link ) {

				echo 'File downloads are currently unavailable for this video.';

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
				<?php
				$language_url = get_the_permalink();
				$standard_name = get_field('standard_name');
				$alternate_names = get_field('alternate_names');
				$nations_of_origin = get_field('nations_of_origin');
				$writing_systems = get_field('writing_systems');
				$linguistic_genealogy = get_field('linguistic_genealogy');
				?>
				<h2 class="wt_sectionHeader">About <a href="<?php echo $language_url; ?>"><?php echo $standard_name; ?></a></h2>
				<ul>
				<?php if ( $nations_of_origin ): ?>
					<li>
						<h2>Countries of origin</h2>
						<p class="wt_text--label">
							<?php echo $nations_of_origin; ?>
						</p>
					</li>
				<?php endif; ?>

				<?php if ( $writing_systems ): ?>
					<li>
						<h2>Writing systems</h2>
						<p class="wt_text--label">
							<?php echo $writing_systems; ?>
						</p>
					</li>
				<?php endif ;?>

				<?php if ( $linguistic_genealogy ): ?>
					<li>
						<h2>Linguistic genealogy</h2>
						<p class="wt_text--label">
							<?php echo $linguistic_genealogy; ?>
						</p>
					</li>
				<?php endif; ?>
				<!-- EGIDS status? -->
				</ul>
			</li>
		<?php endforeach; wp_reset_postdata(); ?>
	</ul>
</div>