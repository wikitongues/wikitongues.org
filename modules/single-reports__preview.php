<?php 

// vars
$bank_balance = get_field('bank_balance');
$previous_bank_balance = get_field('previous_bank_balance');
$runway = get_field('runway');
$previous_runway = get_field('previous_runway');
$projected_runway = get_field('projected_runway');
$previous_projected_runway = get_field('previous_projected_runway');
$baseline_fundraising_target = get_field('baseline_fundraising_target');
$ideal_fundraising_target = get_field('ideal_fundraising_target');
$fundraising_progress = get_field('fundraising_progress');
?>

<article class="single_reports__report main-content">
	<h1>
		<?php echo the_title(); ?>
	</h1>

	<?php if ( $bank_balance ): ?>
	<p>
		<strong>Bank Balance</strong><br>
		<?php echo '$'.number_format($bank_balance,0); ?>
	</p>
	<?php endif; ?>

	<?php if ( $runway ): ?>
	<p>
		<strong>Actual Runway</strong><br>
		<?php echo $runway.' months'; ?>
	</p>
	<?php endif; ?>

	<?php if ( $projected_runway ): ?>
	<p>
		<strong>Projected Runway</strong><br>
		<?php echo $projected_runway.' months'; ?>
	</p>
	<?php endif; ?>

	<?php if ( $baseline_fundraising_target ): ?>
	<p>
		<strong>Baseline Fundraising Target</strong></br>
		<?php echo '$'.number_format($baseline_fundraising_target,0); ?>
	</p>
	<?php endif; ?>

	<?php if ( $ideal_fundraising_target ): ?>
	<p>
		<strong>Ideal Fundraising Target</strong></br>
		<?php echo '$'.number_format($ideal_fundraising_target,0); ?>
	</p>
	<?php endif; ?>

	<?php if ( $fundraising_progress ): ?>
	<p>
		<strong>Fundraising Progress</strong></br>
		<?php echo '$'.number_format($fundraising_progress,0); ?>
	</p>
	<?php endif; ?>

	<?php if ( $fundraising_progress ): ?>
	<p>
		<strong>Fundraising Progress to Baseline Goal</strong></br>
		<?php echo round((($fundraising_progress/$baseline_fundraising_target)*100)).'%'; ?>
	</p>
	<?php endif; ?>

	<?php if ( $fundraising_progress ): ?>
	<p>
		<strong>Fundraising Progress to Ideal Goal</strong></br>
		<?php echo round((($fundraising_progress/$ideal_fundraising_target)*100)).'%'; ?>
	</p>
	<?php endif; ?>

	<?php if ( have_rows('downloads') ): ?>
	<p>
		<strong>File Downloads</strong>
	</p>
	<ul>
		<?php while( have_rows('downloads') ): the_row(); ?>
		<li>
			<a target="_blank" href="<?php echo get_sub_field('download_file'); ?>">
				<?php echo get_sub_field('download_name'); ?>
			</a>
		</li>
		<?php endwhile; ?>
	</ul>
	<?php endif; ?>
	<!-- add downloads -->
</article>
