<?php
	$standard_name = get_field('standard_name');
	$alternate_names = get_field('alternate_names');
	$nations_of_origin = get_field('nations_of_origin');
	$writing_systems = get_field('writing_systems');
	$linguistic_genealogy = get_field('linguistic_genealogy');
	$iso_code = get_field('iso_code');
	$glottocode = get_field('glottocode');
	$olac_url = get_field('olac_url');
	$wikipedia_url = get_field('wikipedia_url');
	?>
<div class="wt_language__banner">
	<h1>
		<?php echo $standard_name; ?>
	</h1>
	<h2>
		<?php echo $alternate_names; ?>
	</h2>
	<ul class="wt_language__banner--metadata">
		<li>
			<strong>Countries of Origin</strong><br>
			<span><?php echo $nations_of_origin; ?></span>
		</li>
		<li>
			<strong>Writing Systems</strong><br>
			<span><?php echo $writing_systems; ?></span>
		</li>
		<li>
			<strong>Linguistic Genealogy</strong><br>
			<span><?php echo $linguistic_genealogy; ?></span>
		</li>
		<li>
			<strong>Language Codes</strong><br>
			<?php if ( $iso_code ): ?>
				<span>ISO 639-3: <?php echo $iso_code; ?></span><br>
			<?php endif; ?>
			
			<?php if ( $glottocode ): ?>
				<span>Glottocode: <?php echo $glottocode; ?></span>
			<?php endif; ?>
		</li>
		<li>
			<strong>Reference Links</strong><br>
			<span>
				<a href="<?php echo $olac_url; ?>" target="_blank">Open Language Archives</a>
			</span><br>
			<span>
				<a href="<?php echo $wikipedia_url; ?>" target="_blank">Wikipedia</a>
			</span>
		</li>
	</ul>
</div>