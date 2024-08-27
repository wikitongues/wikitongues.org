<?php
	$standard_name = get_field('standard_name');
	$nations_of_origin = get_field('nations_of_origin');
	$writing_systems = get_field('writing_systems');
	$linguistic_genealogy = get_field('linguistic_genealogy');
?>
<div class="wt_meta--languages-single">
	<h2 class="wt_sectionHeader"><?php  echo $standard_name; ?> resources</h2>
	<ul>
		<li>
			<a href="#wt_single-languages__videos">
				<h2>Videos <?php echo ($videos_count > 0 ? '(' . $videos_count . ')' : ''); ?></h2>
			</a>
			<a href="<?php bloginfo('url'); ?>/submit-a-video">Submit a video</a>
		</li>
		<li>
			<a href="#wt_single-languages__lexicons">
				<h2>Dictionaries, phrase books, and lexicons <?php echo ($lexicons_count > 0 ? '(' . $lexicons_count . ')' : ''); ?></h2>
			</a>
			<a href="<?php bloginfo('url'); ?>/submit-a-lexicon">Submit a lexicon</a>
		</li>
		<li>
			<a href="#wt_single-languages__resources">
				<h2>External Resources <?php echo ($external_resources_count > 0 ? '(' . $external_resources_count . ')' : ''); ?></h2>
			</a>
			<a href="<?php bloginfo('url'); ?>/submit-a-lexicon">Recommend a resource</a>
		</li>
	</ul>
</div>