<section class="wt_fellow__meta">
	<section class="head">
		<div class="image" style="background-image:url('<?php echo esc_html($page_banner['banner_image']['url'])?>'"></div>
		<div class="name">
			<?php
				echo '<h1>' . esc_html($fellow_name) . '</h1>';
				if (array_filter(array_column($social_links, 'url'))): ?>
				<article class="wt_fellow__meta--social">
					<ul>
						<?php foreach ($social_links as $platform => $data): ?>
							<?php if ($data['url']): ?>
								<li>
									<a href="<?php echo esc_url($data['url']); ?>">
										<i class="<?php echo esc_attr($data['icon']); ?>"></i>
									</a>
								</li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</article>
			<?php endif; ?>
			<section class="info">
				<?php
					echo '<p>' . esc_html($page_banner['banner_copy']) . '</p>';
					if ($fellow_year) {
						echo '<a class="cohort" href="'.$revitalization_fellows_url.'">' . esc_html($fellow_year) . ' cohort</a>';
					}

					// Function to generate a single language link with an optional preferred name
					function generate_language_link($language, $preferred_name = '') {
							if ($language instanceof WP_Post) {
									$language_url = home_url('/languages/' . $language->post_name);
									return '<a class="language" href="' . esc_url($language_url) . '"><span class="identifier">' . esc_html($language->post_title) . '</span><p>' . esc_html($preferred_name) . '</p></a>';
							}
					}

					// Function to generate links for multiple languages
					function generate_language_links($fellow_language) {
						$output = '';

						// Handle single language ID
						if (is_int($fellow_language)) {
							$preferred_name = get_post_meta($fellow_language, 'standard_name', true);
							$language_post = get_post($fellow_language); // Get the WP_Post object by ID
							$output .= generate_language_link($language_post, $preferred_name);

						// Handle multiple language IDs
						} elseif (is_array($fellow_language)) {
							foreach ($fellow_language as $language_id) {
								if (is_int($language_id)) {
									$preferred_name = get_post_meta($language_id, 'standard_name', true);
									$language_post = get_post($language_id); // Fetch WP_Post by ID
									$output .= generate_language_link($language_post, $preferred_name);
								} else {
									// In case the array item is not an ID, output directly
									$output .= '<span class="identifier">' . esc_html($language_id) . '</span>';
								}
							}
						} else {
							// Fallback if $fellow_language is not an ID or array of IDs
							$output .= '<span class="identifier">' . esc_html($fellow_language) . '</span>';
						}

						return $output;
					}

					// Main rendering block
					$lang_output = generate_language_links($fellow_language);

					echo $lang_output;
				?>
			</section>
		</div>
	</section>

	<!-- <article>
		<strong><?php echo $fellow_name; ?> is a member of the Wikitongues Language Revitalization Fellowship's <?php echo $fellow_year; ?> cohort.</strong>
	</article> -->

	<!-- links other than the fellow's personal social media or website -->
	<?php if ( have_rows('custom_links') ): ?>
	<article class="wt_fellow__meta--links">
		<strong>Links</strong><br/>
		<ul>
		<?php while ( have_rows('custom_links') ): the_row(); ?>
			<li>
				<a href="<?php echo get_sub_field('link_url'); ?>">
					<?php echo get_sub_field('link_name'); ?>
				</a>
			</li>
		<?php endwhile; ?>
		</ul>
	</article>
	<?php endif; ?>
</section>