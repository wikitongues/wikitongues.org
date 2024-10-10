<section class="wt_fellow__meta">
	<section class="head">
		<div class="image" style="background-image:url('<?php echo esc_html($page_banner['banner_image']['url'])?>'"></div>
		<div class="name">
			<?php
				echo '<h1>' . esc_html($fellow_name) . '</h1>';?>
			<?php if (array_filter(array_column($social_links, 'url'))): ?>
				<article class="wt_fellow__meta--social">
						<!-- <strong>Follow <?php echo esc_html(get_field('first_name')); ?></strong><br/> -->
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
			<?php

			?>
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

							if ($fellow_language instanceof WP_Post) {
									// Handle single language, use the global preferred name if passed
									$preferred_name = get_post_meta($fellow_language->ID, 'standard_name', true);
									print_r($preferred_name);
									$output .= generate_language_link($fellow_language, $preferred_name);
							} elseif (is_array($fellow_language)) {
								if (count($fellow_language) > 1) {
									foreach ($fellow_language as $language) {
										if ($language instanceof WP_Post) {
												$preferred_name = get_post_meta($language->ID, 'standard_name', true);
												$output .= generate_language_link($language, $preferred_name);
										} else {
												$output .= generate_language_link($language);
										}
									}
								} else {
									foreach ($fellow_language as $language) {
										if ($language instanceof WP_Post) {
												$preferred_name = get_field('fellow_language_preferred_name');
												$output .= generate_language_link($language, $preferred_name);
										} else {
												$output .= generate_language_link($language);
										}
									}
								}

							} else {
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