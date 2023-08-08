<section class="wt_newsletter"></section>

<footer class="wt_footer">

	<section class="wt_footer__section">
		<!-- footer navigation -->
		<nav class=wt_footer__nav></nav>

		<!-- contact information -->
		<aside class="wt_footer__meta">
			<a class="wt_footer__logo" href="<?php bloginfo('url'); ?>">
				<img src="<?php the_field('footer_logo', 'options'); ?>"
				     alt="Wikitongues Logo">
			</a>
			<p class="wt_footer__address">
				<!-- acf fields -->
			</p>
			<p class="wt_footer__email-and-phone">
				<!-- acf fields -->
			</p>
		</aside>

		<!-- clear floats -->
		<div class="clear"></div>
	</section>

	<!-- land acknowledgement and copyright notice -->
	<section class="wt_footer__section">
		<p class="wt_footer__land-acknowledgement">
			<!-- acf fields -->
		</p>
		<p class="wt_footer__copyright">
			&#169; Copyright <?php echo date('Y'); ?> Wikitongues, All Rights Reserved.
		</p>
	</section>
	
	<?php wp_footer(); ?>
</footer>