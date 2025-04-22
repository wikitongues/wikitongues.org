<?php

	$autonym = get_field('autonym');
	$wikipedia = get_field('wikipedia_url');
	// $wikipedia_description = get_field('wikipedia_description');
	$wikipedia_description = '';
	$language_wikipedia = [
		'url' => get_field('language_wikipedia_url'),
		'name' => get_field('language_wikipedia_name')
	];
	$olac = get_field('olac_url');
	$glottocode = get_field('glottocode');
	$glottolog = !empty($glottocode) ? 'https://glottolog.org/resource/languoid/id/' . $glottocode : '';
	$ethnologue = 'https://www.ethnologue.com/language/'.get_the_title();
	$links = [
		$language_wikipedia['name'] => $language_wikipedia['url'],
		'English Wikipedia Article' => $wikipedia,
		'ethnologue' => $ethnologue,
		'glottolog' => $glottolog,
		'Open Language Archives Community' => $olac,
	];

	$alternate_names = get_field('alternate_names');
	$iso_code = get_field('iso_code');
	$glottocode = get_field('glottocode');
	$nations_of_origin = get_field('nations_of_origin');
	$writing_systems = get_field('writing_systems');
	$linguistic_genealogy = get_field('linguistic_genealogy');
	$egids = get_field('egids_status');
?>
<div class="wt_meta--languages-single">
	<h1>
		<?php echo $standard_name; ?>
	</h1>
	<?php if ( $wikipedia_description ) :?>
		<p>
			<?php echo $wikipedia_description ?> &nbsp;
			<a href="<?php echo $wikipedia ?>">Read more on Wikipedia</a>
		</p>
	<?php endif; ?>
	<?php if ( $autonym ): ?>
		<div class="metadata" id="autonym">
			<strong class="mobile-accordion-header">Autonyms</strong>
			<p class="mobile-accordion-content"><?php echo $autonym; ?></p>
		</div>
	<?php endif; ?>
	<?php if ( $alternate_names ): ?>
		<div class="metadata" id="alternate-names">
			<strong class="mobile-accordion-header">Alternate Names</strong>
			<p class="mobile-accordion-content"><?php echo $alternate_names; ?></p>
		</div>
	<?php endif; ?>
	<?php if ( $iso_code || $glottocode): ?>
		<div class="metadata" id="identifiers">
			<strong class="wt_sectionHeader mobile-accordion-header">Identifiers</strong>
			<?php if ( $iso_code || $glottocode ): ?>
				<div class="mobile-accordion-content">
				<?php if ( $iso_code ): ?>
					<span>
						<strong>ISO code</strong>
						<p><?php echo $iso_code; ?></p>
					</span>
				<?php endif; ?>
				<?php if ( $glottocode ): ?>
					<span>
						<strong>Glottocode</strong>
						<p><?php echo $glottocode; ?></p>
					</span>
				<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>
	<?php endif; ?>
	<?php if ( $nations_of_origin || $writing_systems || $linguistic_genealogy || $egids) : ?>
		<div class="metadata" id="metadata">
			<strong class="wt_sectionHeader mobile-accordion-header">Metadata</strong>
			<div class="mobile-accordion-content">
				<?php if ( $nations_of_origin ): ?>
					<strong>Countries of origin</strong>
					<p class="wt_text--label"><?php echo $nations_of_origin; ?></p>
				<?php endif; ?>

				<?php if ( $writing_systems ): ?>
					<strong>Writing systems</strong>
					<p class="wt_text--label"><?php echo $writing_systems; ?></p>
				<?php endif ;?>

				<?php if ( $linguistic_genealogy ): ?>
					<strong>Linguistic genealogy</strong>
					<p class="wt_text--label"><?php echo $linguistic_genealogy; ?></p>
				<?php endif; ?>

				<?php if ( $egids ): ?>
					<strong>EGIDS Status</strong>
					<p class="wt_text--label"><?php echo $egids; ?></p>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
	<div class="metadata" id="external-links">
		<strong class="mobile-accordion-header">Learn more about <?php  echo $standard_name; ?></strong>
		<ul class="mobile-accordion-content">
		<?php
			foreach ($links as $key => $value) {
				if (!empty($value)) {
					echo '<li><a class="official-link" href="' . esc_url($value) . '" target="_blank">' . ucfirst($key) . '</a></li>';
				}
			}
		?>
		</ul>
	</div>
	<button href="https://abdbdjge.donorsupport.co/-/XTRAFEBU" class="donate-cta">Support Language Revitalization</button>
</div>