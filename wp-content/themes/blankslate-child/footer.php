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

<footer class="wt_footer">

	<section class="wt_footer__logo">
		<a href="<?php echo home_url(); ?>">
			<img src="<?php the_field('footer_logo', 'options'); ?>" alt="Wikitongues Logo">
		</a>
	</section>

	<section class="wt_footer__section" id="content">
		<?php wp_nav_menu(
			array(
				'theme_location' => 'footer-menu',
				'container' => 'nav',
				'container_class' => 'wt_footer__nav'
			)
		); ?>
		<aside class="wt_footer__meta">
			<section>
				<div>
				<p class="wt_footer__address">
					<?php the_field('wikitongues_address', 'options'); ?>
				</p>
				<p class="wt_footer__contact">
					<?php the_field('wikitongues_email', 'options'); ?><br />
					<?php the_field('wikitongues_phone', 'options'); ?>
				</p>
				</div>
				<div>
				<a class="wt_footer__candid" href="https://www.guidestar.org/profile/shared/8eef7d54-d184-4013-aad1-0e3ada544f64" target="_blank"><img src="https://widgets.guidestar.org/TransparencySeal/9488075" /></a>
				</div>
			</section>
		</aside>
	</section>

	<section class="wt_footer__section" id="colophon">
		<p class="wt_footer__land-acknowledgement">
			<?php the_field('land_acknowledgement', 'options'); ?>
			<br>
			<span class="wt_footer__copyright">&#169; Copyright <?php echo date('Y'); ?> Wikitongues, All Rights Reserved</span>
		</p>
	</section>

	<?php wp_footer(); ?>
</footer>
</body>
</html>