<div class="wt_metrics">
	<?php if ( $metrics_subhead ): ?>
	<h2>
		<?php echo $metrics_subhead; ?>
	</h2>
	<?php endif; ?>

	<?php if ( have_rows('metrics') ): ?>
	<div class="wt_metrics__icons">
		<ul>
			<?php while ( have_rows('metrics') ): the_row(); ?>
			<li>
				<?php 
					$metric_image = get_sub_field('metric_image'); 
					$metric_text = get_sub_field('metric_text'); ?>

				<img src="<?php echo $metric_image['url']; ?>"
					 alt="<?php echo $metric_image['alt']; ?>">
					 
				<?php if ( $metric_text ): ?>
				<p><?php echo $metric_text; ?></p>
				<?php endif; ?>
			</li>
			<?php endwhile; ?>
		</ul>
	</div>
	<?php endif; ?>
</div>