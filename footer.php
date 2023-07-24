<!-- content vars on "options" ACF field -->

<footer class="wt_footer">
	<!-- newsletter signup -->

	<!-- footer navigation -->

	<!-- footer logo -->
	<div class="wt_footer__logo">
		<a href="<?php bloginfo('url'); ?>">
			<img src="<?php the_field('footer_logo', 'options'); ?>"
			     alt="Wikitongues Logo">
		</a>
	</div>

	<!-- address & contact information -->
	
	<?php wp_footer(); ?>
</footer>