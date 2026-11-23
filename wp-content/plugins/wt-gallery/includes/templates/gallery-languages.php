<li class="gallery-item gallery-item--card">
	<?php
	$url              = get_permalink();
	$thumbnail_object = '';
	$iso_code         = get_the_title();
	$iso_code_element = '<aside>' . esc_html( $iso_code ) . '</aside>';

	// Previews for the whole gallery are fetched together on the first card.
	$previews = wt_gallery_language_previews( wp_list_pluck( $query->posts, 'ID' ) );
	$preview  = $previews[ get_the_ID() ] ?? null;

	if ( $preview && $preview['video_id'] ) {
		$thumbnail_object .= '<div class="thumbnail" style="background-image:url(' . esc_url( $preview['thumbnail'] ) . ');" alt="' . esc_attr( $preview['video_post_title'] ) . '" title=""> <p>' . esc_html( $preview['video_title'] ) . '</p></div>';
	} else {
		$thumbnail_object .= '<div class="no-thumbnail"><p>No resources found to display – Yet.</p></div>';
	}

	echo '<a href="' . esc_url( $url ) . '">';
	echo '<div class="metadata"><h6>' . $title . '</h6>' . $iso_code_element . '</div>';
	echo $thumbnail_object;
	echo '</a>';
	?>
</li>
