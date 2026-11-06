<?php
// Stats Band: live counts with editable labels. See donate-content.php.
$row   = wt_donate_row();
$stats = (array) ( $row['stats'] ?? array() );
if ( ! $stats ) {
	return;
}
?>
<section class="wt-donate wt-donate__reach" aria-label="Our reach">
	<ul class="wt-donate__wrap wt-donate__grid">
		<?php foreach ( $stats as $stat ) : ?>
			<li><strong><?php echo esc_html( number_format_i18n( wt_donate_stat( (string) ( $stat['stat'] ?? '' ) ) ) ); ?></strong> <?php echo esc_html( $stat['stat_label'] ?? '' ); ?></li>
		<?php endforeach; ?>
	</ul>
</section>
