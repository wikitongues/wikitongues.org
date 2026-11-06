<?php
// Set by template-donate.php before this partial is required.
$campaign_id   = $campaign_id ?? '';
$checkout_page = $checkout_page ?? get_permalink();
$checkout      = $checkout ?? 'https://wikitongues.donorsupport.co/-/XTRAFEBU';
?>
<div class="wt-donate__gift">
	<h2 id="gift-box-title">Make a gift</h2>
	<?php if ( $campaign_id ) : ?>
		<form action="<?php echo esc_url( $checkout_page ); ?>" method="get" aria-labelledby="gift-box-title" data-donate-form hidden>
			<fieldset class="wt-donate__frequency">
				<legend>How often?</legend>
				<label><input type="radio" name="recurring" value="once" checked><span>Give once</span></label>
				<label><input type="radio" name="recurring" value="monthly"><span>Monthly</span></label>
			</fieldset>
			<fieldset class="wt-donate__amounts">
				<legend>Amount (USD)</legend>
				<?php foreach ( array( 50, 100, 250 ) as $preset ) : ?>
					<label><input type="radio" name="preset" value="<?php echo esc_attr( (string) $preset ); ?>" <?php checked( 100, $preset ); ?>><span>$<?php echo esc_html( (string) $preset ); ?></span></label>
				<?php endforeach; ?>
				<label><input type="radio" name="preset" value="other"><span>Other</span></label>
			</fieldset>
			<label class="wt-donate__custom" data-custom-amount hidden>
				Your amount (USD)
				<input type="number" inputmode="decimal" min="1" step="0.01" disabled>
			</label>
			<input type="hidden" name="form" value="<?php echo esc_attr( $campaign_id ); ?>">
			<input type="hidden" name="amount" value="100">
			<input type="hidden" name="currency" value="USD">
			<p data-donate-note>Your gift supports all of Wikitongues’ work, including the fellowship.</p>
			<button type="submit" class="wt-donate__button" data-cta-location="donate-page-top-single">Donate $100</button>
		</form>
	<?php endif; ?>
	<div class="wt-donate__checkout-fallback" data-donate-fallback>
		<a class="wt-donate__button" data-cta-location="donate-page-top-single" href="<?php echo esc_url( $checkout ); ?>">Continue to checkout</a>
		<p>Choose your amount and whether to give once or monthly at checkout.</p>
	</div>
	<p class="wt-donate__legal">Payments are completed at our secure checkout. Wikitongues is a 501(c)(3) nonprofit. Gifts are tax-deductible in the US.</p>
</div>
