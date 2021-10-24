<?php $signature = get_field('footer_logo','options'); ?>
<div class="wt_banner">
	<aside class="wt_banner__image"
		   style="background-image:url(<?php echo $banner_image['url']; ?>);"
		   role="img"
		   aria-label="<?php echo $banner_image['alt']; ?>">
	</aside>
	<aside class="wt_banner__text">
		<div class="wt_aligncenter">
			<h1>
				<?php echo $banner_header; ?>
			</h1>
			<h2>
				<?php echo $banner_copy; ?>
			</h2>
			<?php if ( $signature ): ?>
			<div class="wt_banner__logo">
				<a href="<?php bloginfo('url'); ?>">
					<img src="<?php echo $signature; ?>"
					     alt="Wikitongues Logo">
				</a>
			</div>
			<?php endif; ?>
		</div>
	</aside>
	<div class="clear"></div>
</div>