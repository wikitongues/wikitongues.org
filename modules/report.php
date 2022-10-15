<main class="wt_reports__main">
	<h1 class="wt_reports__main--title">
		<?php the_title(); ?>
	</h1>
	<article class="wt_reports__main--content">
		<h2 class="wt_reports__main--subheader">Financials</h2>
		<ul>
			<li>
				<strong>Bank balance</strong><br/>
				<em>As of the report date</em><br/>
				<span><?php the_field('bank_balance'); ?></span>
			</li>
			<li>
				<strong>Hard Runway</strong><br/>
				<em>Bank balance minus expenses</em><br/>
				<span><?php the_field('runway'); ?></span>
			</li>
			<li>
				<strong>Projected Runway</strong><br/>
				<em>Runway with expected income</em><br/>
				<span><?php the_field('projected_runway'); ?></span>
			</li>
			<li>
				<strong>Fundraising Target</strong><br/>
				<em>Amount needed to support next year's programs</em><br/>
				<span><?php the_field('fundraising_progress'); ?></span>
			</li>
			<li>
				<strong>Downloads</strong><br />
				<em>Starting next month, access financial statements here.</em><br/>
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
