<?php // vars
$bank_balance = get_field('bank_balance');
$previous_bank_balance = get_field('previous_bank_balance');
$runway = get_field('runway');
$previous_runway = get_field('previous_runway');
$projected_runway = get_field('projected_runway');
$previous_projected_runway = get_field('previous_projected_runway');
$fundraising_progress = get_field('fundraising_progress');
$revision_notes = get_field('revision_notes');
?>

<main class="wt_reports__main">
	<h1 class="wt_reports__main--title">
		<?php the_title(); ?>
	</h1>

	<?php if( $revision_notes ): ?>
	<div class="wt_reports__main--notes">
		Note: <?php echo $revision_notes; ?>
	</div>
	<?php endif; ?>

	<article class="wt_reports__main--content">
		<h2 class="wt_reports__main--subheader">Financials</h2>
		<ul>
			<li>
				<strong>Bank balance</strong><br/>
				<em>As of the report date</em><br/>
				<span>$<?php echo number_format($bank_balance); ?></span>
				<?php if( $previous_bank_balance): ?>
					<?php if ( ($bank_balance-$previous_bank_balance)>0 ): ?>
						<span>(<i class="fa-solid fa-up"></i>)</span>
					<?php else: ?>
						<span>(<i class="fa-solid fa-down"></i>)</span>
					<?php endif; ?>
				<?php endif; ?>
			</li>
			<li>
				<strong>Hard Runway</strong><br/>
				<em>Bank balance minus expenses</em><br/>
				<span><?php echo $runway; ?></span>
				<span>
					<?php if( $runway<2 ): ?>month<?php else: ?>months<?php endif; ?>
				</span>
				<?php if( $previous_runway ): ?>
					<?php if( ($runway-$previous_runway)>0 ): ?>
						<span>(<i class="fa-solid fa-up"></i>)</span>
					<?php else:?> 
						<span>(<i class="fa-solid fa-down"></i>)</span>
					<?php endif; ?>
				<?php endif; ?>
			</li>
			<li>
				<strong>Projected Runway</strong><br/>
				<em>Bank balance and expected income minus expenses</em><br/>
				<span><?php echo $projected_runway; ?></span>
				<span>
					<?php if( $projected_runway<2 ): ?>month<?php else: ?>months<?php endif; ?>
				</span>
				<?php if( $previous_projected_runway ): ?>
					<?php if( ($projected_runway-$previous_projected_runway)>0 ): ?>
						<span>(<i class="fa-solid fa-up"></i>)</span>
					<?php else: ?>
						<span>(<i class="fa-solid fa-up"></i>)</span>
					<?php endif; ?>
				<?php endif; ?>
			</li>
			<li>
				<strong>Fundraising Target</strong><br/>
				<em>Amount needed to fully fund next year's programs</em><br/>
				<span>$<?php echo number_format($fundraising_progress); ?></span>
			</li>
			<li>
				<strong>Downloads</strong><br />
				<?php 
				if( have_rows('downloads') ){
					while( have_rows('downloads') ){
						the_row();

						echo '<a href="'.get_sub_field('download_file').'">'.
							 get_sub_field('download_name').
							 '</a>';
					}
				} else {
					echo '<em>This month\'s financial statements are not yet available.</em>';
				} ?>
			</li>
		</ul>

		<?php if( have_rows('project_updates') ): ?>
		<h2 class="wt_reports__main--subheader">Project Updates</h2>
		<ul>
			<?php while( have_rows('project_updates') ): the_row(); ?>
			<li>
				<strong><?php the_sub_field('project_name'); ?></strong><br/>
				<span><?php the_sub_field('project_update'); ?></span> 
			</li>
			<?php endwhile; ?>
		</ul>
		<?php endif; ?>
	</article>
</main>
