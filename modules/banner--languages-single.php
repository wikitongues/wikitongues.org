<div class="wt_banner--languages">
	<section class="wt_banner--languages__meta">
		<h1>
			<?php the_field('standard_name'); ?>
		</h1>
		<p>
			<strong>
				<?php the_field('alternate_names'); ?>
			</strong>
		</p>
		<ul>
			<li>
				<strong>
					ISO code: <?php the_field('iso_code'); ?>
				</strong>
			</li>
			<li>
				<strong>
					Glottocode: <?php the_field('glottocode'); ?>
				</strong>
			</li>
		</ul>
		<ul>
			<li>
				<p>Countries of origin</p>
				<p class="wt_text--label">
					<?php the_field('nations_of_origin'); ?>
				</p>
			</li>
			<li>
				<p>Writing system</p>
				<p class="wt_text--label">
					<?php the_field('writing_system'); ?>
				</p>
			</li>
			<li>
				<p>Linguistic genealogy</p>
				<p class="wt_text--label">
					<?php the_field('linguistic_genealogy'); ?>
				</p>
			</li>
		</ul>
	</section>
</div>