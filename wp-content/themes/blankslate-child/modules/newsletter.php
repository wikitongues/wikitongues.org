<?php
	$user = get_field('mailchimp_newsletter_user', 'options');
	$id = get_field('mailchimp_newsletter_id', 'options');
	$newsletter_title = get_field('block_title', 'options');
	$newsletter_copy = get_field('block_copy', 'options');
?>
<section class="wt_newsletter">
	<h2><?php echo $newsletter_title; ?></h2>
	<p><?php echo $newsletter_copy; ?></p>
	<div id="mc_embed_signup">
		<label for="mce-EMAIL">Your email</label>
		<?php
			echo '<form onsubmit="return gtag_report_conversion();" action="https://wikitongues.us9.list-manage.com/subscribe/post?u='.$user.'&amp;id='.$id.'" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate="novalidate">'
		?>
			<div id="mc_embed_signup_scroll">
				<div class="mc-field-group">
					<input type="email" value="" name="EMAIL" placeholder="hello@wikitongues.org" class="required email" id="mce-EMAIL" aria-required="true">
				</div>
				<div id="mce-responses" class="clear">
					<div class="response" id="mce-error-response" style="display:none"></div>
					<div class="response" id="mce-success-response" style="display:none"></div>
				</div>
				<div style="position: absolute; left: -5000px;" aria-hidden="true">
					<?php echo '<input type="text" name="b_'.$user.'_'.$id.'" tabindex="-1" value="">'; ?>
				</div>
				<div class="clear">
					<input type="submit" value="Stay informed" name="subscribe" id="mc-embedded-subscribe" class="button wt_cta">
				</div>
			</div>
		</form>
	</div>
</section>