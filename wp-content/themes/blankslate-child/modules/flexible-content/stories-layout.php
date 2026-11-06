<?php
// Fellow Stories: the first story large, the rest as cards. See donate-content.php.
$row         = wt_donate_row();
$stories     = array_values( array_filter( array_map( 'wt_donate_story', (array) ( $row['stories'] ?? array() ) ) ) );
$show_photos = 'Yes' === ( $row['display_photos'] ?? 'Yes' );
$featured    = array_shift( $stories );

/**
 * Prints a story's photo, or its language name.
 *
 * @param array<string, string|int> $story From wt_donate_story().
 */
$story_art = static function ( $story ) use ( $show_photos ) {
	if ( $show_photos && $story['photo_id'] ) {
		echo wp_get_attachment_image( (int) $story['photo_id'], 'large', false, array( 'class' => 'wt-donate__story-art wt-donate__story-art--photo' ) );
		return;
	}
	echo '<div class="wt-donate__story-art" aria-hidden="true">' . esc_html( (string) $story['language'] ) . '</div>';
};
$join      = static function ( $parts ) {
	return implode( ' · ', array_filter( array_map( 'strval', $parts ) ) );
};
?>
<section class="wt-donate wt-donate__rule" aria-labelledby="gift-stories">
	<div class="wt-donate__wrap wt-donate__section">
		<div class="wt-donate__heading">
			<p class="wt-donate__label"><?php echo esc_html( $row['section_eyebrow'] ?? '' ); ?></p>
			<h2 id="gift-stories"><?php echo esc_html( $row['section_header'] ?? '' ); ?></h2>
		</div>
		<?php if ( $featured ) : ?>
			<article class="wt-donate__feature">
				<?php $story_art( $featured ); ?>
				<div class="wt-donate__story-copy">
					<p class="wt-donate__eyebrow"><?php echo esc_html( $join( array( $featured['year'] ? 'Fellow since ' . $featured['year'] : '', $featured['location'] ) ) ); ?></p>
					<h3><?php echo esc_html( $featured['header'] ? $featured['header'] : $featured['name'] ); ?></h3>
					<p><?php echo wt_donate_marked( (string) $featured['copy'], 'wt-donate__underline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in wt_donate_marked(). ?></p>
				</div>
			</article>
		<?php endif; ?>
		<?php if ( $stories ) : ?>
			<div class="wt-donate__grid wt-donate__stories">
				<?php foreach ( $stories as $story ) : ?>
					<article>
						<?php $story_art( $story ); ?>
						<div class="wt-donate__story-copy">
							<p class="wt-donate__eyebrow"><?php echo esc_html( $join( array( $story['language'], $story['location'], $story['year'] ) ) ); ?></p>
							<h3><?php echo esc_html( (string) $story['name'] ); ?></h3>
							<p><?php echo esc_html( (string) $story['copy'] ); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php wt_donate_link( $row['footer_link'] ?? null ); ?>
	</div>
</section>
