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
$downloads = get_field('downloads');

?>

<article class="single_reports__report">
	<h1>
		<?php echo the_title(); ?>
	</h1>

	<?php if ( $bank_balance ): ?>
	<p>	<!-- how to add commas etc? -->
		<?php echo '$'.$bank_balance; ?>
	</p>
	<?php endif; ?>

	<?php if ( $runway ): ?>
	<p>
		<?php echo $runway; ?>
	</p>
	<?php endif; ?>

	<?php if ( $baseline_fundraising_target ): ?>
	<p>
		<?php echo '$'.$baseline_fundraising_target; ?>
	</p>
	<?php endif; ?>

	<?php if ( $ideal_fundraising_target ): ?>
	<p>
		<?php echo '$'.$ideal_fundraising_target; ?>
	</p>
	<?php endif; ?>

	<?php if ( $fundraising_progress ): ?>
	<p>
		<?php echo '$'.$fundraising_progress; ?>
	</p>
	<?php endif; ?>

	<?php if ( $fundraising_progress ): ?>
	<p>
		<?php echo round((($fundraising_progress/$baseline_fundraising_target)*100)).'%'; ?>
	</p>
	<?php endif; ?>

	<?php if ( $fundraising_progress ): ?>
	<p>
		<?php echo round((($fundraising_progress/$ideal_fundraising_target)*100)).'%'; ?>
	</p>
	<?php endif; ?>

	<!-- add downloads -->


</article>
