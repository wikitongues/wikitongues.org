<main class="wt_single-videos__content">
<!-- define height in js for responsive iframe -->

<?php if ( $public_status === 'Public' ): ?>

	<div class="wt_single-videos__video <?php if ( $youtube_id ): ?>has-iframe<?php endif; ?>">

	<?php if ( $youtube_id ): ?>

		<iframe width="100%" src="https://www.youtube.com/embed/<?php echo $youtube_id; ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
		
	<?php elseif ( $dropbox_link || $video_thumbnail ): ?>

		<!-- Dropbox links need more work to be embedded -->
		<img src="<?php echo $video_thumbnail['url']; ?>" alt="<?php echo $video_thumbnail['alt']; ?>">

	<?php else: ?>

		<p>Sorry, there was an error loading the video file. We're probably still processing it, so please check back soon.</p>

	<?php endif; ?>

	</div><!-- __video wrap -->

<?php elseif ( $public_status === 'Processing' ): ?>

	<div class="wt_single-videos__no-video">		
		<p class="wt_text--bold">We're still processing this video. Please check back soon for the file.</p><!-- future protocol: ensure live by a certain date -->
	</div>

<?php elseif ( $public_status === 'Private' ): ?>

	<div class="wt_single-videos__no-video">		
		<p class="wt_text--bold">The creator of this video has chosen to make this video private.</p>
	</div>

<?php endif; ?>