<section class="wt_newsletter">
	<?php echo get_field('newsletter_signup_embed', 'options'); ?>
</section>

<footer class="wt_footer">

	<section class="wt_footer__section">
		<!-- footer navigation -->
		<?php
			wp_nav_menu(
				array( 
					'theme_location' => 'footer-menu',
					'container' => 'nav',
					'container_class' => 'wt_footer__nav'
				)
			); 
		?>

		<!-- contact information -->
		<aside class="wt_footer__meta">
			<a class="wt_footer__logo" href="<?php bloginfo('url'); ?>">
				<img src="<?php the_field('footer_logo', 'options'); ?>"
				     alt="Wikitongues Logo">
			</a>
			<p class="wt_footer__address">
				<?php the_field('wikitongues_address', 'options'); ?>
			</p>
			<p class="wt_footer__contact">
				<?php the_field('wikitongues_email', 'options'); ?><br />
				<?php the_field('wikitongues_phone', 'options'); ?>
			</p>
		</aside>

		<!-- clear floats -->
		<div class="clear"></div>
	</section>

	<!-- land acknowledgement and copyright notice -->
	<section class="wt_footer__section">
		<p class="wt_footer__land-acknowledgement">
			<?php the_field('land_acknowledgement', 'options'); ?>
		</p>
		<p class="wt_footer__copyright">
			<span>&#169; Copyright <?php echo date('Y'); ?> Wikitongues, All Rights Reserved</span>
		</p>
		<a class="wt_footer__candid" href="https://www.guidestar.org/profile/shared/8eef7d54-d184-4013-aad1-0e3ada544f64" target="_blank"><img src="https://widgets.guidestar.org/TransparencySeal/9488075" /></a>
	</section>
	
	<?php wp_footer(); ?>
</footer>