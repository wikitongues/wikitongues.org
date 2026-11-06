<?php
// Requires $donate_id from template-donate.php.
$donate_id     = $donate_id ?? (int) get_the_ID();
$stories_intro = (array) wt_donate_field( 'donate_stories_intro', $donate_id );
$featured      = (array) wt_donate_field( 'donate_featured', $donate_id );
$stories       = (array) wt_donate_field( 'donate_stories', $donate_id );

/**
 * Prints a story's photo, or its language name when there is no photo.
 *
 * @param array<string, mixed> $story Story fields.
 */
$story_art = static function ( $story ) {
	$image = $story['image'] ?? null;
	if ( is_array( $image ) && ! empty( $image['ID'] ) ) {
		echo wp_get_attachment_image( (int) $image['ID'], 'large', false, array( 'class' => 'wt-donate__story-art wt-donate__story-art--photo' ) );
		return;
	}
	echo '<div class="wt-donate__story-art" aria-hidden="true">' . esc_html( $story['art_text'] ?? '' ) . '</div>';
};
?>
<section class="wt-donate__rule" aria-labelledby="gift-stories">
	<div class="wt-donate__wrap wt-donate__section">
		<div class="wt-donate__heading">
			<p class="wt-donate__eyebrow"><?php echo esc_html( $stories_intro['eyebrow'] ?? '' ); ?></p>
			<h2 id="gift-stories"><?php echo esc_html( $stories_intro['title'] ?? '' ); ?></h2>
		</div>
		<?php if ( ! empty( $featured['title'] ) ) : ?>
			<article class="wt-donate__feature">
				<?php $story_art( $featured ); ?>
				<div class="wt-donate__story-copy">
					<p class="wt-donate__eyebrow"><?php echo esc_html( $featured['eyebrow'] ?? '' ); ?></p>
					<h3><?php echo esc_html( $featured['title'] ); ?></h3>
					<p><?php echo wt_donate_marked( $featured['text'] ?? '', 'wt-donate__underline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in wt_donate_marked(). ?></p>
				</div>
			</article>
		<?php endif; ?>
		<?php if ( $stories ) : ?>
			<div class="wt-donate__grid wt-donate__stories">
				<?php foreach ( $stories as $story ) : ?>
					<article>
						<?php $story_art( $story ); ?>
						<div class="wt-donate__story-copy">
							<p class="wt-donate__eyebrow"><?php echo esc_html( $story['eyebrow'] ?? '' ); ?></p>
							<h3><?php echo esc_html( $story['name'] ?? '' ); ?></h3>
							<p><?php echo esc_html( $story['text'] ?? '' ); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $stories_intro['link_label'] ) ) : ?>
			<a href="<?php echo esc_url( wt_donate_url( (string) ( $stories_intro['link_url'] ?? '' ) ) ); ?>"><?php echo esc_html( $stories_intro['link_label'] ); ?></a>
		<?php endif; ?>
	</div>
</section>
