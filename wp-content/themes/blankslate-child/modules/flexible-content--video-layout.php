<?php
	$video = get_sub_field('video');
	$video_title = get_sub_field('video_title');
	$dropbox_link_raw = str_replace("dl=0", "raw=1", $video);
	if ( $dropbox_link_raw ) {
		?>
		<div class="main-content wt_single-videos__embed">
			<video width="320" height="240" controls>
				<source src="<?php echo $dropbox_link_raw ?>" type="video/mp4">Your browser does not support the video tag.
			</video>
			<?php
		if ( $video_title ) {
			echo '<h6>' . $video_title . '</h6>';
		};
		echo '</div>';
	};