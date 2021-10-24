<div class="wt_partners">
	<?php if ( $partners_header ): ?>
	<h1 class="wt_type__header">
		<?php echo $partners_header; ?>
	</h1>
	<?php endif; ?>

	<?php if ( have_rows('partners') ): ?>
	<div class="wt_partners__icons">
		<ul>
			<?php while ( have_rows('partners') ): the_row(); ?>
			<li>
				<?php 
					$partner_logo = get_sub_field('partner_logo'); 
					$partner_name = get_sub_field('partner_name'); ?>

				<img src="<?php echo $partner_logo['url']; ?>"
					 alt="<?php echo $partner_image['alt']; ?>">
					 
				<?php if ( $partner_name ): ?>
				<p class="wt_type__body"><?php echo $partner_name; ?></p>
				<?php endif; ?>
			</li>
			<?php endwhile; ?>
		</ul>
	</div>
	<?php endif; ?>
</div>