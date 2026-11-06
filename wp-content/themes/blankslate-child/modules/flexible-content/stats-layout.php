<?php
// Stats Band: each stat is a live count or typed text. See donate-content.php.
$row   = wt_donate_row();
$stats = (array) ( $row['stats'] ?? array() );
if ( ! $stats ) {
	return;
}
?>
<section class="wt-donate wt-donate__reach" aria-label="Our reach">
	<ul class="wt-donate__wrap wt-donate__grid">
		<?php foreach ( $stats as $stat ) : ?>
			<li>
				<strong>
					<?php if ( ! empty( $stat['stat_prefix'] ) ) : ?>
						<span class="wt-donate__stat-prefix"><?php echo esc_html( $stat['stat_prefix'] ); ?></span>
					<?php endif; ?>
					<?php echo esc_html( wt_donate_stat_display( $stat ) ); ?>
				</strong>
				<?php echo esc_html( $stat['stat_label'] ?? '' ); ?>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
