<!-- donate form -->
<?php include( locate_template('modules/donate.php') ); ?>

<footer class="wt_footer">
	<!-- newsletter -->
	<?php include( locate_template('modules/newsletter.php') ); ?>

	<!-- footer logo -->
	<div class="wt_footer__logo">
		<a href="<?php bloginfo('url'); ?>">
			<img src="<?php the_field('footer_logo', 'options'); ?>"
			     alt="Wikitongues Logo">
		</a>
	</div>

	<!-- footer navigation -->
	<?php wp_nav_menu(
			array( 
				'theme_location' => 'footer-menu',
				'container' => '',
				'menu_id' => 'wt_footer__menu',
				'menu_class' => 'wt_footer__menu'
			)
		  ); ?>

	<!-- contact information -->
	<div class="wt_footer__contact">
		<p>
			<strong>Questions?</strong>
		</p>
		<p class="wt_footer__contact--address">
			Wikitongues<br />
			175 Pearl Street<br />
			Floors 1-3<br />
			Brooklyn, NY 11201
		</p>
		<p class="wt_footer__contact--information">
			hello@wikitongues.org<br />
			+1 (718) 865 2031
		</p>
		<p class="wt_footer__contact--legal">
			Wikitongues is a 501(3)(c) non-profit organization based on Lenape land in Brooklyn, NY, USA.<br />Thanks to <a href="https://www.greengeeks.com/affiliates/track.php?affiliate=wikitongues" target="_blank">GreenGeeks</a>, this website's carbon footprint is offset by wind credits.
		</p>
	</div>
	
	<?php wp_footer(); ?>
</footer>