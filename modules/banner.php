<div class="wt_banner" role="img" aria-label="<?php echo $banner_image['alt']; ?>" style="background-image:url(<?php echo $banner_image['url']; ?>);">
	<div class="wt_banner__copy">
		<h1 class="wt_text--header">
			<?php echo $banner_header; ?>
		</h1>
		<p class="wt_text--body">
			<?php echo $banner_copy; ?>
		</p>
		<?php if ( $banner_cta ): ?>
			<a href="<?php echo $banner_cta['url']; ?>">
				<?php echo $banner_cta['title']; ?>
			</a>
		<?php elseif ( $banner_CTA_placeholder ): ?>
			<p class="wt_text--body">
				<strong>
					<?php echo $banner_CTA_placeholder; ?>
				</strong>
			</p>
		<?php else: ?>
			<!-- there is no CTA or helper text to display -->
		<?php endif; ?>
	</div>
</div>