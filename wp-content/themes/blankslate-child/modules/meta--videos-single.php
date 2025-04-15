<div class="wt_meta--videos-single">
	<div class="wt_meta__video-downloads">
		<?php
			if ( $video_license && $video_license !== 'none' ) {
				echo '<section>';
				echo '<strong>Video license</strong>';
				echo '<a class="license" href="' . $video_license_url . '">'. $video_license . '</a>';
				echo '</section>';
			}
		?>

		<?php
		// Get captions linked to this video
		$video_id = get_the_ID();

		$captions = get_posts([
				'post_type' => 'captions',
				'posts_per_page' => -1,
				'meta_query' => [
						[
								'key' => 'source_video',
								'value' => $video_id,
								'compare' => '='
						]
				]
		]);

		if ( $captions ) {
			echo '<section>';
			echo '<strong>Available captions</strong>';
			echo '<ul>';
			foreach ( $captions as $caption ) {
					$file_url = get_field( 'file_url', $caption->ID );
					$source_languages = get_field( 'source_language', $caption->ID ); // array of WP_Posts

					if ( is_array( $source_languages ) && !empty( $source_languages ) ) {
							$caption_language_names = array_map(function($lang_post) {
									return get_field('standard_name', $lang_post->ID);
							}, $source_languages);

							$label = implode(', ', array_filter($caption_language_names)); // avoid null values
					} else {
							$label = 'Unknown Language';
					}
					if ( $file_url ) {
							echo '<li><a href="' . esc_url( $file_url ) . '" target="_blank">' . esc_html( $label ) . ' (.srt)</a></li>';
					}
			}
			echo '</ul>';
			echo '</section>';
	}
		?>

		<section>
		<strong>Video file downloads</strong>
		<?php if ( $public_status === 'Public' ) {
			if ( ($dropbox_link && $dropbox_link !== 'none') || $wikimedia_commons_link ) {
				echo '<ul>';
				if ( $dropbox_link && $dropbox_link !== 'none' ) {
					echo '<li><a href="'. $dropbox_link . '" target="_blank">Dropbox (.mp4)</a></li>';
				}

				if ( $wikimedia_commons_link ) {
					echo '<li><a href="'. $wikimedia_commons_link . '" target="_blank">Wikimedia Commons (.webm)</a></li>';
				}
				echo '</ul>';
			}

			if ( ($dropbox_link === 'none' || !$dropbox_link) && !$wikimedia_commons_link ) {
				echo '<p>File downloads are currently unavailable for this video.</p>';
			}

		} elseif ( $public_status === 'Processing' ) {
			echo '<p>File downloads will be ready when this is video is finished processing. Please check back soon.</p>'; // later version: 'subscribe for notifications'

		} elseif ( $public_status === 'Private') {
			echo '<p>File downloads are disabled because the creator of this video has chosen to make this video private.</p>';
		} ?>
		</section>
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
				<strong class="wt_sectionHeader">About <a href="<?php echo $language_url; ?>"><?php echo $standard_name; ?></a></strong>
				<ul>
				<?php if ( $nations_of_origin ): ?>
					<li>
						<strong>Countries of origin</strong>
						<p class="wt_text--label">
							<?php echo $nations_of_origin; ?>
						</p>
					</li>
				<?php endif; ?>

				<?php if ( $writing_systems ): ?>
					<li>
						<strong>Writing systems</strong>
						<p class="wt_text--label">
							<?php echo $writing_systems; ?>
						</p>
					</li>
				<?php endif ;?>

				<?php if ( $linguistic_genealogy ): ?>
					<li>
						<strong>Linguistic genealogy</strong>
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