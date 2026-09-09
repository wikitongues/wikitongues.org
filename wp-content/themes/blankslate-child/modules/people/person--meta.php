<?php
/*
 * Shared person detail block: name, role, languages, social links, bio.
 *
 * Included by person--wide.php (alongside a portrait) and person--list.php
 * (without one). $person_variant names the BEM block so each layout keeps its
 * own styles — 'wide' or 'list'.
 */
$person_variant     = isset( $person_variant ) ? $person_variant : 'wide';
$person_block       = 'wt_person--' . $person_variant;
$personal_languages = (array) ( $personal_languages ?? array() );
?>
<aside class="<?php echo esc_attr( $person_block ); ?>__meta">
	<h4><?php echo $name; ?></h4>
	<strong><?php echo $title; ?></strong>
	<?php if ( $personal_languages ) : ?>
		<?php
		$first_name = explode( ' ', trim( $name ) )[0];
		echo '<p>' . esc_html( $first_name ) . ' speaks</p>';
		?>

		<ul>
			<?php
			/*
			 * Resolve each language by ID rather than setup_postdata(). This module
			 * renders inside render_gallery_items(), where $post is a function-local
			 * variable — so setup_postdata() never reached the global that
			 * the_permalink()/the_field() read from, and every name came back empty
			 * (leaving a row of bare commas). Resolving explicitly also avoids
			 * wp_reset_postdata() clobbering the gallery's own loop.
			 */
			$last = count( $personal_languages ) - 1;
			foreach ( array_values( $personal_languages ) as $index => $language ) :
				$language_id = $language instanceof WP_Post ? $language->ID : (int) $language;
				if ( ! $language_id ) {
					continue;
				}
				$language_name = get_field( 'standard_name', $language_id );
				$language_name = $language_name ? $language_name : get_the_title( $language_id );
				?>
				<li>
					<a href="<?php echo esc_url( get_permalink( $language_id ) ); ?>"><?php echo esc_html( $language_name ); ?></a><?php echo $index < $last ? ',' : ''; ?>
				</li>
				<?php
			endforeach;
			?>
		</ul>
	<?php endif; ?>

	<ul class="social">
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

	<?php if ( $bio ) : ?>
		<p><?php echo $bio; ?></p>
	<?php endif; ?>
</aside>
