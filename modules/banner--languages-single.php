<?php
$standard_name = get_field('standard_name');
$alternate_names = get_field('alternate_names');
$iso_code = get_field('iso_code');
$glottocode = get_field('glottocode');
$nations_of_origin = get_field('nations_of_origin');
$writing_systems = get_field('writing_systems');
$linguistic_genealogy = get_field('linguistic_genealogy');
?>
<div class="wt_banner--languages">
	<section class="wt_banner--languages__meta">
		<h1>
			<?php the_field('standard_name'); ?>
		</h1>
		<p>
		<?php if ( $alternate_names ): ?>
			<strong>
				<?php echo $alternate_names; ?>
			</strong>
		<?php endif; ?>
		</p>
		<ul>
		<?php if ( $iso_code ): ?>
			<li>
				<p>ISO code</p>
				<p><?php echo $iso_code; ?></p>
			</li>
		<?php endif; ?>

		<?php if ( $glottocode ): ?>
			<li>
				<p>Glottocode</p>
				<p><?php echo $glottocode; ?></p>
			</li>
		<?php endif; ?>

		<?php if ( $nations_of_origin ): ?>
			<li>
				<p>Countries of origin</p>
				<p class="wt_text--label">
					<?php echo $nations_of_origin; ?>
				</p>
			</li>
		<?php endif; ?>

		<?php if ( $writing_systems ): ?>
			<li>
				<p>Writing systems</p>
				<p class="wt_text--label">
					<?php echo $writing_systems; ?>
				</p>
			</li>
		<?php endif ;?>

		<?php if ( $linguistic_genealogy ): ?>
			<li>
				<p>Linguistic genealogy</p>
				<p class="wt_text--label">
					<?php echo $linguistic_genealogy; ?>
				</p>
			</li>
		<?php endif; ?>
		</ul>
	</section>
</div>