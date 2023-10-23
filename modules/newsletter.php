<?php
	$newsletter_header = get_field('newsletter_header', 'options');
	$newsletter_copy = get_field('newsletter_copy', 'options');
	$newsletter_signup_embed = get_field('newsletter_signup_embed', 'options'); ?>

<div class="wt_newsletter">
	<h1>
		<?php echo $newsletter_header; ?>
	</h1>
	<aside class="wt_newsletter__text">
		<?php echo $newsletter_copy; ?>
	</aside>
	<aside class="wt_newsletter__form">
		<?php echo $newsletter_signup_embed; ?>
	</aside>
	<div class="clear"></div>
</div>