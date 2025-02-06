<?php
	$videos = get_field('speakers_recorded');
	$videos = is_array($videos) ? $videos : [];
	$videos_count = count($videos);
	$lexicon_source = get_field('lexicon_source');
	$lexicon_target = get_field('lexicon_target');
	$lexicon_source = is_array($lexicon_source) ? $lexicon_source : [];
	$lexicon_target = is_array($lexicon_target) ? $lexicon_target : [];
	$lexicons = array_merge($lexicon_source, $lexicon_target);
	$lexicons_count = count($lexicons);

	$wikipedia = get_field('wikipedia_url');
	$olac = get_field('olac_url');
	$glottocode = get_field('glottocode');
	$glottolog = !empty($glottocode) ? 'https://glottolog.org/resource/languoid/id/' . $glottocode : '';
	$ethnologue = 'https://www.ethnologue.com/language/'.get_the_title();
	$links = [
		'ethnologue' => $ethnologue,
		'glottolog' => $glottolog,
		'Open Language Archives Community' => $olac,
		'English Wikipedia Article' => $wikipedia,
	];

	$alternate_names = get_field('alternate_names');
	$iso_code = get_field('iso_code');
	$glottocode = get_field('glottocode');
	$nations_of_origin = get_field('nations_of_origin');
	$writing_systems = get_field('writing_systems');
	$linguistic_genealogy = get_field('linguistic_genealogy');
?>
<div class="wt_meta--languages-single">
	<h1>
		<?php the_field('standard_name'); ?>
	</h1>
	<?php if ( $alternate_names ): ?>
		<div class="metadata" id="alternate-names">
			<strong>Alternate Names</strong>
			<p><?php echo $alternate_names; ?></p>
		</div>
	<?php endif; ?>
	<?php if ( $iso_code || $glottocode): ?>
		<div class="metadata" id="identifiers">
			<h2>Identifiers</h2>
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
	<?php if ( $nations_of_origin || $writing_systems || $linguistic_genealogy ) : ?>
		<div class="metadata" id="metadata">
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
		</div>
	<?php endif; ?>
	<div class="metadata" id="resources">
		<h2 class="wt_sectionHeader"><?php  echo $standard_name; ?> resources</h2>
		<ul class="resources">
			<li>
				<h3>Videos</h3>
				<a href="<?php echo home_url('/submit-a-video', 'relative'); ?>">Submit a video</a>
			</li>
			<li>
				<h3>Dictionaries, phrase books, and lexicons</h3>
				<a href="<?php echo home_url('/submit-a-lexicon', 'relative'); ?>">Submit a lexicon</a>
			</li>
			<li>
				<h3>External Resources</h3>
				<a href="<?php echo home_url('/submit-a-resource', 'relative'); ?>">Recommend a resource</a>
			</li>
		</ul>
	</div>
	<div class="metadata" id="external-links">
		<h2>Learn more about <?php  echo $standard_name; ?></h2>
		<ul>
		<?php
			foreach ($links as $key => $value) {
				if (!empty($value)) {
					echo '<li><a class="official-link" href="' . esc_url($value) . '" target="_blank">' . ucfirst($key) . '</a></li>';
				}
			}
		?>
		</ul>
	</div>
	<a href="https://abdbdjge.donorsupport.co/-/XTRAFEBU" class="donate-cta">Support Language Revitalization</a>
</div>