<div class="wt_metrics">
	<?php if ( have_rows('metrics', 'options') ): ?>
	<div class="wt_metrics__icons">
		<ul>
			<?php while ( have_rows('metrics', 'options') ): the_row(); ?>
			<li>
				<?php 
					$metric_image = get_sub_field('metric_image', 'options'); 
					$metric_text = get_sub_field('metric_text', 'options'); ?>

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

	<?php if ( $metrics_subhead ): ?>
	<h2 class="wt_type__subhead">
		<?php echo $metrics_subhead; ?>
	</h2>
	<?php endif; ?>
</div>