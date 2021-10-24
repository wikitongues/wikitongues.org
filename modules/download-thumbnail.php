<?php
	$version_image = $post['version_image'];
	$version_name = $post['version_name'];
	$version_description = $post['version_description'];
	$version_file = $post['version_file'];
?>

<div class="wt_thumbnails__download">
	<a href="<?php echo $version_file; ?>">
		<?php if ( $version_image ): ?>
		<img class="wt_thumbnails__download--image"
			 src="<?php echo $version_image['url']; ?>" 
			 alt="<?php echo $version_image['alt']; ?>">
		<?php endif; ?>
		
		<ul class="wt_thumbnails__download--metadata">
			<li>
				<strong><?php echo $version_name; ?></strong>
			</li>
			<li>
				<?php if ( $version_description ): ?>
				<span>
					<?php echo $version_description; ?>
				</span>
				<?php endif; ?>
			</li>
		</ul>
	</a>
</div>