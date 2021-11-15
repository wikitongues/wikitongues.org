<?php
	$language_thumbnail = get_the_post_thumbnail_url();
	$standard_name = get_field('standard_name');
	$alternate_names = get_field('alternate_names');
	$nations_of_origin = get_field('nations_of_origin'); // not sure if this is the metadata we want to tease
	
	// $writing_systems = get_field('writing_systems');
	// $linguistic_genealogy = get_field('linguistic_genealogy');
	// $glottocode = get_field('glottocode');
	// $olac_url = get_field('olac_url');
	// $wikipedia_url = get_field('wikipedia_url');
?>
<div class="wt_thumbnails__language">
	<a href="<?php the_permalink(); ?>">
		<?php if ( $language_thumbnail ): ?>
		<img class="wt_thumbnails__video--image"
			 src="<?php echo $language_thumbnail; ?>" 
			 alt="language image thumbnail">
		<?php endif; ?>
		
		<ul class="wt_thumbnails__language--metadata">
			<li>
				<?php if ( $standard_name ) : ?>
				<strong><?php echo $standard_name; ?></strong>
				<?php endif; ?>
				
				<?php if ( $alternate_names ) : ?>
				<span><?php echo $alternate_names; ?></span>
				<?php endif; ?>
			</li>
		</ul>
	</a>
</div>