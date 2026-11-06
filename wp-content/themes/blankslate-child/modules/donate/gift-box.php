<?php
// Set by template-donate.php before this partial is required.
$gift            = $gift ?? array();
$campaign_id     = $campaign_id ?? '';
$checkout_page   = $checkout_page ?? (string) get_permalink();
$checkout        = $checkout ?? '';
$once_amounts    = $once_amounts ?? array( 50, 100, 250 );
$monthly_amounts = $monthly_amounts ?? array( 5, 10, 25 );
?>
<div class="wt-donate__gift">
	<h2 id="gift-box-title"><?php echo esc_html( $gift['title'] ?? '' ); ?></h2>
	<?php if ( $campaign_id ) : ?>
		<form action="<?php echo esc_url( $checkout_page ); ?>" method="get" aria-labelledby="gift-box-title" data-donate-form
			data-amounts-once="<?php echo esc_attr( implode( ',', $once_amounts ) ); ?>"
			data-amounts-monthly="<?php echo esc_attr( implode( ',', $monthly_amounts ) ); ?>"
			data-note-once="<?php echo esc_attr( $gift['note_once'] ?? '' ); ?>"
			data-note-monthly="<?php echo esc_attr( $gift['note_monthly'] ?? '' ); ?>"
			hidden>
			<fieldset class="wt-donate__frequency">
				<legend>How often?</legend>
				<label><input type="radio" name="recurring" value="once" checked><span>Give once</span></label>
				<label><input type="radio" name="recurring" value="monthly"><span>Monthly</span></label>
			</fieldset>
			<fieldset class="wt-donate__amounts">
				<legend>Amount (USD)</legend>
				<?php foreach ( $once_amounts as $i => $preset ) : ?>
					<label><input type="radio" name="preset" value="<?php echo esc_attr( (string) $preset ); ?>" <?php checked( 1, $i ); ?>><span>$<?php echo esc_html( (string) $preset ); ?></span></label>
				<?php endforeach; ?>
				<label><input type="radio" name="preset" value="other"><span>Other</span></label>
			</fieldset>
			<label class="wt-donate__custom" data-custom-amount hidden>
				Your amount (USD)
				<input type="number" inputmode="decimal" min="1" step="0.01" disabled>
			</label>
			<input type="hidden" name="form" value="<?php echo esc_attr( $campaign_id ); ?>">
			<input type="hidden" name="amount" value="<?php echo esc_attr( (string) $once_amounts[1] ); ?>">
			<input type="hidden" name="currency" value="USD">
			<p data-donate-note><?php echo esc_html( $gift['note_once'] ?? '' ); ?></p>
			<button type="submit" class="wt-donate__button" data-cta-location="donate-page-top-single">Donate $<?php echo esc_html( (string) $once_amounts[1] ); ?></button>
		</form>
	<?php endif; ?>
	<div class="wt-donate__checkout-fallback" data-donate-fallback>
		<a class="wt-donate__button" data-cta-location="donate-page-top-single" href="<?php echo esc_url( $checkout ); ?>"><?php echo esc_html( $gift['fallback_button'] ?? '' ); ?></a>
		<p><?php echo esc_html( $gift['fallback_note'] ?? '' ); ?></p>
	</div>
	<p class="wt-donate__legal"><?php echo esc_html( $gift['legal'] ?? '' ); ?></p>
</div>
