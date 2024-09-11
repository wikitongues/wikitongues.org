<?php
	$wikipedia = get_field('wikipedia_url');
	$olac = get_field('olac_url');
	$glottocode = get_field('glottocode');
	$glottolog = !empty($glottocode) ? 'https://glottolog.org/resource/languoid/id/' . $glottocode : '';
	$ethnologue = 'https://www.ethnologue.com/language/'.get_the_title();
	$links = [
		'ethnologue' => $ethnologue,
		'glottolog' => $glottolog,
		'OLAC' => $olac,
		'wikipedia' => $wikipedia,
	];
?>
<div class="wt_meta--languages-single">
	<h2 class="wt_sectionHeader"><?php  echo $standard_name; ?> resources</h2>
	<ul>
		<li>
			<a href="#wt_single-languages__videos">
				<h2>Videos <?php echo ($videos_count > 0 ? '(' . $videos_count . ')' : ''); ?></h2>
			</a>
			<a href="<?php echo home_url(); ?>/submit-a-video">Submit a video</a>
		</li>
		<li>
			<a href="#wt_single-languages__lexicons">
				<h2>Dictionaries, phrase books, and lexicons <?php echo ($lexicons_count > 0 ? '(' . $lexicons_count . ')' : ''); ?></h2>
			</a>
			<a href="<?php echo home_url(); ?>/submit-a-lexicon">Submit a lexicon</a>
		</li>
		<li>
			<a href="#wt_single-languages__resources">
				<h2>External Resources <?php echo ($external_resources_count > 0 ? '(' . $external_resources_count . ')' : ''); ?></h2>
			</a>
			<a href="<?php echo home_url(); ?>/submit-a-resource">Recommend a resource</a>
		</li>
	</ul>
	<?php
		foreach ($links as $key => $value) {
			if (!empty($value)) {
				echo '<a class="official-link" href="' . esc_url($value) . '" target="_blank">' . ucfirst($key) . '</a>';
			}
		}
	?>
	<a href="https://abdbdjge.donorsupport.co/-/XTRAFEBU" class="donate-cta">Support Language Revitalization</a>
</div>