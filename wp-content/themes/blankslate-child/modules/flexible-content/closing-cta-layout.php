<?php
// Closing Call to Action, with an optional share button. See donate-content.php.
$row = wt_donate_row();
if ( 'Yes' === ( $row['display_share'] ?? 'No' ) ) {
	wp_enqueue_script( 'wt-donate', get_stylesheet_directory_uri() . '/js/donate.js', array(), '1.2.0', true );
}
?>
<section class="wt-donate wt-donate__rule" aria-label="Give today">
	<div class="wt-donate__wrap wt-donate__section wt-donate__close">
		<h2><?php echo wt_donate_marked( $row['section_header'] ?? '', 'wt-donate__highlight' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in wt_donate_marked(). ?></h2>
		<?php if ( ! empty( $row['section_copy'] ) ) : ?>
			<p><?php echo esc_html( $row['section_copy'] ); ?></p>
		<?php endif; ?>
		<div class="wt-donate__actions">
			<?php wt_donate_link( $row['section_cta'] ?? null, 'wt-donate__button', 'donate-page-bottom' ); ?>
			<?php if ( 'Yes' === ( $row['display_share'] ?? 'No' ) ) : ?>
				<button type="button" class="wt-donate__button wt-donate__button--outline" data-donate-share hidden><?php echo esc_html( $row['share_label'] ?? '' ); ?></button>
			<?php endif; ?>
		</div>
		<p role="status" data-donate-share-status></p>
	</div>
</section>
