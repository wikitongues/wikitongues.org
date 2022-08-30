<?php
	$video_thumbnail = get_field('video_thumbnail');
	$video_thumbnail_media = get_field('video_thumbnail_url');
	$video_title = get_field('video_title');
	$featured_languages = get_field('featured_languages');
	$youtube_link = get_field('youtube_link');
	$wikimedia_commons_link = get_field('wikimedia_commons_link');
	$public_status = get_field('public_status');
	$video_license = get_field('video_license');
	$dropbox_link = get_field('dropbox_link');
	preg_match('#https?:\/\/\S+\.[^()]+(?:\([^)]*\))*#', $video_thumbnail, $parsed_video_thumbnail_url);
?>
<div class="wt_thumbnails__video wt_masonry">
	<?php if ( $video_thumbnail_media ): ?>
	<img class="wt_thumbnails__video--image"
		 src="<?php echo $video_thumbnail_media; ?>" 
		 alt="video still image">
	<?php elseif( $parsed_video_thumbnail_url && !$video_thumbnail_media): ?>
	<img class="wt_thumbnails__video--image"
		src="<?php echo $parsed_video_thumbnail_url[0]; ?>" 
		 alt="video still image">
		<?php else: ?>
	<img class="wt_thumbnails__video--image"
		 src="<?php echo bloginfo('url'); ?>/wp-content/themes/blankslate-child/img/video__no-thumbnail.jpg" 
		 alt="video still image">
	<?php endif; ?>


	
	<ul class="wt_thumbnails__video--metadata">
		<li>
			<strong><?php echo $video_title; ?></strong>
		</li>
		<li>
			<strong>Languages spoken</strong>
			<span>
			<?php 
				foreach ( $featured_languages as $post ) {
					setup_postdata( $post );

					echo '<span>' .
						 get_field('standard_name') .
						 '</span>';

				} wp_reset_postdata(); 
			?>
			</span>
		</li>
		<li>
			<strong>Access</strong>
			<?php
				if ( $public_status == 'Public' ) {
					if ( $youtube_link && $youtube_link != "No ID") {
						echo '<span>'.
							 '<a href="' . $youtube_link . '">' .
							 'YouTube' .
							 '</a>'.
							 '</span>';
					} 
					if ( $dropbox_link ) {
						echo '<span>' .
						'<a href="' . $dropbox_link . '">' .
						'Dropbox' .
						'</a>' .
						'</span>';
					}

					if ( $wikimedia_commons_link ) {
						echo '<span>'.
							 '<a href="' . $wikimedia_commons_link . '">' .
							 'Wikimedia Commons' .
							 '</a>'.
							 '</span>';
					}
				} elseif ( $public_status == 'Private' ) {
					echo '<span>' .
						 'This video has been made private at the creator\'s request' .
						 '</span>';
				} else {
					echo '<span>' .
						 'These materials are still being processed.' .
						 '</span>';
				}
			?>
		</li>
		<li>
			<strong>License</strong>
			<span><?php echo $video_license; ?></span>
		</li>
	</ul>
</div>