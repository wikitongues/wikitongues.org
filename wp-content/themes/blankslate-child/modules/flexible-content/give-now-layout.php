<?php
// Give Now Pair: a one-time pitch and a dark monthly box. See donate-content.php.
$row    = wt_donate_row();
$halves = array(
	array( (array) ( $row['give_once'] ?? array() ), '', 'donate-page-middle-single', 'wt-donate__label' ),
	array( (array) ( $row['give_monthly'] ?? array() ), 'wt-donate__monthly', 'donate-page-middle-recurring', 'wt-donate__eyebrow' ),
);
?>
<section class="wt-donate wt-donate__rule" aria-label="Why give now">
	<div class="wt-donate__wrap wt-donate__section wt-donate__pair">
		<?php foreach ( $halves as list( $half, $half_class, $location, $label_class ) ) : ?>
			<div<?php echo $half_class ? ' class="' . esc_attr( $half_class ) . '"' : ''; ?>>
				<p class="<?php echo esc_attr( $label_class ); ?>"><?php echo esc_html( $half['section_eyebrow'] ?? '' ); ?></p>
				<h2><?php echo esc_html( $half['section_header'] ?? '' ); ?></h2>
				<p><?php echo esc_html( $half['section_copy'] ?? '' ); ?></p>
				<?php wt_donate_link( $half['section_cta'] ?? null, 'wt-donate__button', $location ); ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
