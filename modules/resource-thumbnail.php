<?php
	$resource_title = get_field('resource_title');
	$resource_url = get_field('resource_url');
	$resource_description = get_field('resource_description');
?>
<div class="wt_thumbnails__resource wt_masonry">
	<a href="<?php echo $resource_url; ?>" target="_blank">
		<ul class="wt_thumbnails__lexicon--metadata">
			<li>
				<strong><?php echo $resource_title; ?></strong>
			</li>
			<li>
				<p><?php echo $resource_description; ?></p>
			</li>
		</ul>
	</a>
</div>