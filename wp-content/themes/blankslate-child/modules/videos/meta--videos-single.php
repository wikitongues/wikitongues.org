<div class="wt_meta--videos-single">
	<div class="wt_meta__video-downloads">
		<?php
		if ( $video_license && $video_license !== 'none' ) {
			echo '<section>';
			echo '<strong>Video license</strong>';
			echo '<a class="license" href="' . $video_license_url . '">' . $video_license . '</a>';
			echo '</section>';
		}
		?>

		<?php
		// Get captions linked to this video.
		// source_video is an ACF post_object field but the sync stores the value
		// as a serialized array, so we match against the serialized form with LIKE.
		$video_id      = get_the_ID();
		$language_slug = ( ! empty( $featured_languages ) && isset( $featured_languages[0]->post_name ) )
			? $featured_languages[0]->post_name
			: null;
		$captions      = get_posts(
			array(
				'post_type'      => 'captions',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => 'source_video',
						'value'   => '"' . $video_id . '"',
						'compare' => 'LIKE',
					),
				),
			)
		);

		if ( $captions ) {
			$caption_items = '';
			foreach ( $captions as $caption ) {
				$file_url         = get_field( 'file_url', $caption->ID );
				$source_languages = get_field( 'source_language', $caption->ID ); // array of WP_Posts

				if ( ! $file_url ) {
					continue;
				}

				if ( is_array( $source_languages ) && ! empty( $source_languages ) ) {
					$caption_language_names = array_map(
						function ( $lang_post ) {
							return get_field( 'standard_name', $lang_post->ID );
						},
						$source_languages
					);
					$label                  = implode( ', ', array_filter( $caption_language_names ) );
				} else {
					$label = 'Unknown Language';
				}

				if ( shortcode_exists( 'gateway_download' ) && GATEWAY_ENABLED ) {
					// Render via gateway so the download is logged and gated per policy.
					// Build the anchor inline to safely handle language names with apostrophes.
					$caption_policy   = \WT\DownloadGateway\PolicyResolver::resolve( $caption->ID );
					$caption_disabled = ( $caption_policy === \WT\DownloadGateway\SettingsRepository::POLICY_DISABLED );
					if ( ! $caption_disabled ) {
						$caption_intake = \WT\DownloadGateway\IntakeResolver::resolve( $caption->ID );
						$caption_dl_url = rest_url( GATEWAY_REST_NAMESPACE . '/download/' . $caption->ID );
						$caption_items .= '<li><a href="' . esc_url( $caption_dl_url ) . '" class="gateway-download-link"'
							. ' data-post-id="' . esc_attr( (string) $caption->ID ) . '"'
							. ' data-policy="' . esc_attr( $caption_policy ) . '"'
							. ' data-post-type="captions"'
							. ' data-intake-set="' . esc_attr( $caption_intake['set'] ) . '"'
							. ' data-intake-always="' . ( $caption_intake['always'] ? '1' : '0' ) . '"'
							. ( $language_slug ? ' data-language-slug="' . esc_attr( $language_slug ) . '"' : '' )
							. ' data-download-source="resource-page">'
							. esc_html( $label ) . ' (.srt)</a></li>';
					}
				} else {
					$caption_items .= '<li><a href="' . esc_url( $file_url ) . '" target="_blank">' . esc_html( $label ) . ' (.srt)</a></li>';
				}
			}

			if ( $caption_items ) {
				echo '<section>';
				echo '<strong>Available captions</strong>';
				echo '<ul>' . $caption_items . '</ul>';
				echo '</section>';
			}
		}
		?>

		<section>
		<strong>Video file downloads</strong>
		<?php
		if ( $public_status === 'Public' ) {
			$has_dropbox   = $dropbox_link && $dropbox_link !== 'none';
			$has_wikimedia = ! empty( $wikimedia_commons_link );

			if ( $has_dropbox || $has_wikimedia ) {
				echo '<ul>';

				$gateway_active = shortcode_exists( 'gateway_download' ) && GATEWAY_ENABLED;
				$video_policy   = 'none';
				$video_disabled = false;
				if ( $gateway_active ) {
					$video_policy   = \WT\DownloadGateway\PolicyResolver::resolve( $video_id );
					$video_disabled = ( $video_policy === \WT\DownloadGateway\SettingsRepository::POLICY_DISABLED );
				}

				if ( $has_dropbox ) {
					if ( $gateway_active ) {
						if ( ! $video_disabled ) {
							$video_intake = \WT\DownloadGateway\IntakeResolver::resolve( $video_id );
							$video_dl_url = rest_url( GATEWAY_REST_NAMESPACE . '/download/' . $video_id );
							echo '<li><a href="' . esc_url( $video_dl_url ) . '" class="gateway-download-link"'
								. ' data-post-id="' . esc_attr( (string) $video_id ) . '"'
								. ' data-policy="' . esc_attr( $video_policy ) . '"'
								. ' data-post-type="videos"'
								. ' data-intake-set="' . esc_attr( $video_intake['set'] ) . '"'
								. ' data-intake-always="' . ( $video_intake['always'] ? '1' : '0' ) . '"'
								. ( $language_slug ? ' data-language-slug="' . esc_attr( $language_slug ) . '"' : '' )
								. ' data-download-source="resource-page">Dropbox (.mp4)</a></li>';
						}
					} else {
						echo '<li><a href="' . esc_url( $dropbox_link ) . '" target="_blank">Dropbox (.mp4)</a></li>';
					}
				}

				// Wikimedia Commons: gate fires (visitor data captured) but JS redirects
				// directly to the public URL — no server-side file resolution needed.
				if ( $has_wikimedia ) {
					if ( $gateway_active && ! $video_disabled ) {
						$video_intake = \WT\DownloadGateway\IntakeResolver::resolve( $video_id );
						echo '<li><a href="' . esc_url( $wikimedia_commons_link ) . '" class="gateway-download-link"'
							. ' data-post-id="' . esc_attr( (string) $video_id ) . '"'
							. ' data-policy="' . esc_attr( $video_policy ) . '"'
							. ' data-post-type="videos"'
							. ' data-file-url="' . esc_attr( $wikimedia_commons_link ) . '"'
							. ' data-intake-set="' . esc_attr( $video_intake['set'] ) . '"'
							. ' data-intake-always="' . ( $video_intake['always'] ? '1' : '0' ) . '"'
							. ( $language_slug ? ' data-language-slug="' . esc_attr( $language_slug ) . '"' : '' )
							. ' data-download-source="resource-page">Wikimedia Commons (.webm)</a></li>';
					} else {
						echo '<li><a href="' . esc_url( $wikimedia_commons_link ) . '" target="_blank">Wikimedia Commons (.webm)</a></li>';
					}
				}

				echo '</ul>';
			} else {
				echo '<p>File downloads are currently unavailable for this video.</p>';
			}
		} elseif ( $public_status === 'Processing' ) {
			echo '<p>File downloads will be ready when this is video is finished processing. Please check back soon.</p>'; // later version: 'subscribe for notifications'

		} elseif ( $public_status === 'Private' ) {
			echo '<p>File downloads are disabled because the creator of this video has chosen to make this video private.</p>';
		}
		?>
		</section>
	</div>

	<ul class="wt_meta__featured-languages">
		<?php
		foreach ( $featured_languages as $post ) :
			setup_postdata( $post );
			?>
			<li>
				<?php
				$language_url               = get_the_permalink();
				$standard_name              = get_field( 'standard_name' );
				$alternate_names            = get_field( 'alternate_names' );
				$nations_of_origin          = get_field( 'nations_of_origin' );
				$writing_system_terms       = get_the_terms( get_the_ID(), 'writing-system' );
				$linguistic_genealogy_terms = get_the_terms( get_the_ID(), 'linguistic-genealogy' );
				?>
				<strong class="wt_sectionHeader"><a href="<?php echo $language_url; ?>"><?php echo $standard_name; ?></a></strong>
				<ul>
				<?php if ( $nations_of_origin ) : ?>
					<li>
						<p>Countries of origin</p>
						<p class="wt_text--label">
							<?php echo $nations_of_origin; ?>
						</p>
					</li>
				<?php endif; ?>

				<?php if ( $writing_system_terms && ! is_wp_error( $writing_system_terms ) ) : ?>
					<li>
						<p>Writing systems</p>
						<p class="wt_text--label">
							<?php
							$ws_links = array();
							foreach ( $writing_system_terms as $ws_term ) {
								$ws_links[] = '<a href="' . esc_url( add_query_arg( 'writing_system', $ws_term->slug, get_post_type_archive_link( 'languages' ) ) ) . '">' . esc_html( $ws_term->name ) . '</a>';
							}
							echo implode( ', ', $ws_links );
							?>
						</p>
					</li>
				<?php endif; ?>

				<?php if ( $linguistic_genealogy_terms && ! is_wp_error( $linguistic_genealogy_terms ) ) : ?>
					<li>
						<p>Linguistic genealogy</p>
						<p class="wt_text--label">
							<?php
							$lg_links = array();
							foreach ( $linguistic_genealogy_terms as $lg_term ) {
								$lg_links[] = '<a href="' . esc_url( add_query_arg( 'genealogy', $lg_term->slug, get_post_type_archive_link( 'languages' ) ) ) . '">' . esc_html( $lg_term->name ) . '</a>';
							}
							echo implode( ', ', $lg_links );
							?>
						</p>
					</li>
				<?php endif; ?>
				<!-- EGIDS status? -->
				</ul>
			</li>
			<?php
		endforeach;
		wp_reset_postdata();
		?>
	</ul>
</div>
