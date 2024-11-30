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
?>
<div class="wt_meta--languages-single">
	<h2 class="wt_sectionHeader"><?php  echo $standard_name; ?> resources</h2>
	<ul class="resources">
		<li>
			<a href="#wt_single-languages__videos">
				<h3>Videos <?php echo ($videos_count > 0 ? '(' . $videos_count . ')' : ''); ?></h3>
			</a>
			<a href="<?php echo home_url('/submit-a-video', 'relative'); ?>">Submit a video</a>
		</li>
		<li>
			<a href="#wt_single-languages__lexicons">
				<h3>Dictionaries, phrase books, and lexicons <?php echo ($lexicons_count > 0 ? '(' . $lexicons_count . ')' : ''); ?></h3>
			</a>
			<a href="<?php echo home_url('/submit-a-lexicon', 'relative'); ?>">Submit a lexicon</a>
		</li>
		<li>
			<a href="#wt_single-languages__resources">
				<h3>External Resources <?php echo ($external_resources_count > 0 ? '(' . $external_resources_count . ')' : ''); ?></h3>
			</a>
			<a href="<?php echo home_url('/submit-a-resource', 'relative'); ?>">Recommend a resource</a>
		</li>
	</ul>
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
	<a href="https://abdbdjge.donorsupport.co/-/XTRAFEBU" class="donate-cta">Support Language Revitalization</a>
</div>