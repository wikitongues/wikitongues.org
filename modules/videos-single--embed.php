<main class="wt_single-videos__content">
<!-- define height in js for responsive iframe -->

<?php if ( $public_status === 'Public' ): ?>

	<!-- video wrap, class conditioned on content -->
	<?php if ( $youtube_id ): ?>

		<div class="wt_single-videos__video has-iframe">

	<?php elseif ( $youtube_link && $youtube_link !== 'No ID' ): ?>

		<div class="wt_single-videos__video has-iframe">

	<?php else: ?>

		<div class="wt_single-videos__video">

	<?php endif; ?>

	<?php if ( $youtube_id ): ?>

		<iframe width="100%" src="https://www.youtube.com/embed/<?php echo $youtube_id; ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>

	<?php elseif ( $youtube_link && $youtube_link !== 'No ID' ): ?>

		<?php $youtube_id = substr(strrchr($youtube_link, "/"), 1); ?>

		<iframe width="100%" src="https://www.youtube.com/embed/<?php echo $youtube_id; ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>

		
	<?php elseif ( $dropbox_link || $video_thumbnail ): ?>

		<!-- Dropbox links need more work to be embedded -->
		<?php echo wp_get_attachment_image($video_thumbnail, $size='small'); ?>

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