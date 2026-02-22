<?php
// Function to generate a single language link with an optional preferred name
function generate_language_link( $language, $preferred_name = '' ) {
	if ( $language instanceof WP_Post ) {
			$language_url = home_url( '/languages/' . $language->post_name, 'relative' );
			return '<a class="language" href="' . esc_url( $language_url ) . '"><span class="identifier">' . esc_html( $language->post_title ) . '</span><p>' . esc_html( $preferred_name ) . '</p></a>';
	}
}

// Function to generate links for multiple languages
// If array, do not use preferred name. If single language, use preferred name if passed.
function generate_language_links( $fellow_language ) {
		$output = '';

	if ( $fellow_language instanceof WP_Post ) {
			// Handle single language, use the global preferred name if passed
			$output_name = get_post_meta( $fellow_language->ID, 'standard_name', true );
			$output     .= generate_language_link( $fellow_language, $output_name );
	} elseif ( is_array( $fellow_language ) ) {
		if ( count( $fellow_language ) > 1 ) {
			// If array, do not use preferred name since we cannot set distinct names per entry.
			foreach ( $fellow_language as $language ) {
				if ( $language instanceof WP_Post ) {
						$output_name = get_post_meta( $language->ID, 'standard_name', true );
						$output     .= generate_language_link( $language, $output_name );
				} else {
						$output .= generate_language_link( $language );
				}
			}
		} else {
			// If single language, use preferred name if passed.
			foreach ( $fellow_language as $language ) {
				if ( $language instanceof WP_Post ) {
						$preferred_name = get_field( 'fellow_language_preferred_name' );
						$standard_nmame = get_post_meta( $language->ID, 'standard_name', true );
						$output_name    = $preferred_name ? $preferred_name : $standard_nmame;
						$output        .= generate_language_link( $language, $output_name );
				} else {
						$output .= generate_language_link( $language );
				}
			}
		}
	} else {
			$output .= '<span class="identifier">' . esc_html( $fellow_language ) . '</span>';
	}

		return $output;
}
?>

<section class="wt_fellow__meta">
	<section class="head">
		<div class="image" style="background-image:url('<?php echo esc_html( $page_banner['banner_image']['url'] ); ?>'"></div>
		<div class="name">
			<?php
				echo '<h3>' . esc_html( $fellow_name ) . '</h3>';
			?>
			<?php if ( array_filter( array_column( $social_links, 'url' ) ) ) : ?>
				<article class="wt_fellow__meta--social">
						<ul>
								<?php foreach ( $social_links as $platform => $data ) : ?>
										<?php if ( $data['url'] ) : ?>
												<li>
														<a href="<?php echo esc_url( $data['url'] ); ?>">
																<?php echo wt_icon( $data['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
														</a>
												</li>
										<?php endif; ?>
								<?php endforeach; ?>
						</ul>
				</article>
			<?php endif; ?>
			<section class="info">
				<?php
				if ( $fellow_territory ) {
					$territories     = is_array( $fellow_territory ) ? $fellow_territory : array( $fellow_territory );
					$territory_links = array();
					foreach ( $territories as $terr ) {
						$territory_links[] = '<a href="' . esc_url( get_permalink( $terr->ID ) ) . '">' . esc_html( wt_prefix_the( $terr->post_title ) ) . '</a>';
					}
					echo '<p class="fellow-territory">' . implode( ', ', $territory_links ) . '</p>';
				}
				echo '<p>' . esc_html( $page_banner['banner_copy'] ) . '</p>';
				echo '<p class="categories">' . $category_names . '</p>';
				if ( $fellow_year ) {
					echo '<a class="cohort" href="' . $revitalization_fellows_url . '">' . esc_html( $fellow_year ) . ' cohort</a>';
				}

				$lang_output = generate_language_links( $fellow_language );

				echo '<span class="languages">' . $lang_output . '</span>';
				?>
			</section>
		</div>
	</section>

	<?php if ( have_rows( 'custom_links' ) ) : ?>
	<article class="wt_fellow__meta--links">
		<strong>Links</strong><br/>
		<ul>
		<?php
		while ( have_rows( 'custom_links' ) ) :
			the_row();
			?>
			<li>
				<a href="<?php echo get_sub_field( 'link_url' ); ?>">
					<?php echo get_sub_field( 'link_name' ); ?>
				</a>
			</li>
		<?php endwhile; ?>
		</ul>
	</article>
	<?php endif; ?>
</section>