<?php // vars
$bank_balance = get_field('bank_balance');
$previous_bank_balance = get_field('previous_bank_balance');
$runway = get_field('runway');
$previous_runway = get_field('previous_runway');
$projected_runway = get_field('projected_runway');
$previous_projected_runway = get_field('previous_projected_runway');
$active_donors = get_field('active_donors');
$previous_active_donors = get_field('previous_active_donors');
$fundraising_target = get_field('fundraising_target');
$active_users = get_field('active_users');
$previous_active_users = get_field('previous_active_users');
$newsletter_subscribers = get_field('newsletter_subscribers');
$previous_newsletter_subscribers = get_field('previous_newsletter_subscribers');
$youtube_subscribers = get_field('youtube_subscribers');
$previous_youtube_subscribers = get_field('previous_youtube_subscribers');
$engaged_activists = get_field('engaged_activists');
$previous_engaged_activists = get_field('previous_engaged_activists');
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
				<strong>Cash balance</strong><br/>
				<em>As of the report date</em><br/>
				<span>$<?php echo number_format($bank_balance); ?></span>
				<?php if( $previous_bank_balance): ?>
					<?php if ( ($bank_balance-$previous_bank_balance)>0 ): ?>
						<span>(<i class="fa-solid fa-up"></i>)</span>
					<?php elseif ( ($bank_balance-$previous_bank_balance)===0 ): ?>
						<span>( - )</span>
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
					<?php elseif ( ($runway-$previous_runway)===0 ): ?>
						<span>( - )</span>
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
					<?php elseif ( ($projected_runway-$previous_projected_runway)===0 ): ?>
						<span>( - )</span>
					<?php else: ?>
						<span>(<i class="fa-solid fa-down"></i>)</span>
					<?php endif; ?>
				<?php endif; ?>
			</li>

			<?php if( $active_donors ): ?>
			<li>
				<strong>Active Donors</strong><br/>
				<em>People who donate monthly and or who have given within the last 12 months</em><br/>
				<span><?php echo $active_donors; ?></span>
				<?php if( $previous_active_donors ): ?>
					<?php if( $active_donors>$previous_active_donors ): ?>
						<span>(<i class="fa-solid fa-up"></i>)</span>
					<?php elseif ( ($active_donors-$previous_active_donors)===0 ): ?>
						<span>( - )</span>
					<?php else: ?>
						<span>(<i class="fa-solid fa-down"></i>)</span>
					<?php endif; ?>
				<?php endif; ?>
			</li>
			<?php endif; ?> 

			<li>
				<strong>Fundraising Target</strong><br/>
				<em>Amount needed to fully fund next year's programs</em><br/>
				<span>$<?php echo number_format($fundraising_target); ?></span>
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

		<h2 class="wt_reports__main--subheader">Community</h2>
		<!-- add active users, engaged activists next cycle -->
		<ul>
			<li>	
				<strong>Newsletter Subscribers</strong><br/>
				<em>As of the report date</em><br/>
				<span>
				<?php if( $newsletter_subscribers>0) {
					echo number_format($newsletter_subscribers);
				} else {
					echo 'Data for this month is not available.';
				}?>	
				</span>
				<?php if( $previous_newsletter_subscribers): ?>
					<?php if ( ($newsletter_subscribers-$previous_newsletter_subscribers)>0 ): ?>
						<span>(<i class="fa-solid fa-up"></i>)</span>
					<?php elseif ( ($newsletter_subscribers-$previous_newsletter_subscribers)===0 ): ?>
						<span>( - )</span>
					<?php else: ?>	
						<span>(<i class="fa-solid fa-down"></i>)</span>
					<?php endif; ?>
				<?php endif; ?>
			</li>
			<li>	
				<strong>YouTube Subscribers</strong><br/>
				<em>As of the report date</em><br/>
				<span>
				<?php if ( $youtube_subscribers>0 ) {
					echo number_format($youtube_subscribers);
				} else {
					echo 'Data for this month is not available.';
				} ?></span>
				<?php if( $previous_youtube_subscribers): ?>
					<?php if ( ($youtube_subscribers-$previous_youtube_subscribers)>0 ): ?>
						<span>(<i class="fa-solid fa-up"></i>)</span>
					<?php elseif ( ($youtube_subscribers-$previous_youtube_subscribers)===0 ): ?>
						<span>( - )</span>
					<?php else: ?>	
						<span>(<i class="fa-solid fa-down"></i>)</span>
					<?php endif; ?>
				<?php endif; ?>
			</li>
		</ul>

		<?php if( have_rows('project_updates') ): ?>
		<h2 class="wt_reports__main--subheader">Project Updates</h2>
		<ul class="wt_reports__main--updates">
			<?php while( have_rows('project_updates') ): the_row(); ?>
			<li>
				<strong><?php the_sub_field('project_name'); ?></strong><br/>
				<?php if( have_rows('project_update') ): ?>
				<ul>
					<?php while( have_rows('project_update') ): the_row(); ?>
					<li>
						<span>
							<?php the_sub_field('project_update_text'); ?>
						</span>
					</li>
					<?php endwhile; ?>
				</ul>
				<?php endif; ?>
			</li>
			<?php endwhile; ?>
		</ul>
		<?php endif; ?>
	</article>
</main>
