<section class="wt_single-videos__content--body">
	<div class="wt_meta--videos-single">	
		<div class="wt_meta__video-downloads">
		<?php
				if ( $video_license && $video_license !== 'none' ) { 
					echo '<p><strong class="wt_text--uppercase">Video license</strong></p>';
					echo '<p><a class="license" href="' . $video_license_url . '">'. $video_license . '</a></p>';
				}
			?>
			<p>
				<strong class="wt_text--uppercase">Video file downloads</strong>
			</p>

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
				<strong class="wt_text--uppercase">About <a href="<?php echo $language_url; ?>"><?php echo $standard_name; ?></a></strong>
				<ul>
				<?php if ( $nations_of_origin ): ?>
					<li>
						<p>Countries of origin</p>
						<p class="wt_text--label">
							<?php echo $nations_of_origin; ?>
						</p>
					</li>
				<?php endif; ?>

				<?php if ( $writing_systems ): ?>
					<li>
						<p>Writing systems</p>
						<p class="wt_text--label">
							<?php echo $writing_systems; ?>
						</p>
					</li>
				<?php endif ;?>

				<?php if ( $linguistic_genealogy ): ?>
					<li>
						<p>Linguistic genealogy</p>
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