<section class="wt_newsletter">
	<h2>Discover new languages, cultures, and stories.</h2>
	<p>Sign up for our newsletter and get the latest news, stories, and ways to make an impact.</p>
	<?php echo get_field('newsletter_signup_embed', 'options'); ?>
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