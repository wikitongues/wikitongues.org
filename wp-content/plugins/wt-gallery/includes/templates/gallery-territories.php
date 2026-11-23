<li class="gallery-item gallery-item--card">
	<?php
	$territory_id = $query->post->ID;

	// Languages per territory come from one postmeta read shared by every card
	// (wt_gallery_posts_by_meta_reference), not a LIKE query per card. The
	// previews for the whole gallery are fetched together on the first card.
	$languages_by_territory = wt_gallery_posts_by_meta_reference( 'territories', 'languages' );
	$language_ids           = $languages_by_territory[ $territory_id ] ?? array();
	$gallery_preview_ids    = array();
	foreach ( $query->posts as $gallery_post ) {
		$gallery_preview_ids = array_merge( $gallery_preview_ids, array_slice( $languages_by_territory[ $gallery_post->ID ] ?? array(), 0, 4 ) );
	}
	$previews = wt_gallery_language_previews( $gallery_preview_ids );

	$url              = get_permalink();
	$title            = get_the_title();
	$language_count   = '<aside>' . esc_html( (string) count( $language_ids ) ) . '</aside>';
	$thumbnail_object = '';

	foreach ( array_slice( $language_ids, 0, 4 ) as $language_id ) {
		$preview = $previews[ $language_id ] ?? null;
		if ( ! $preview ) {
			continue;
		}
		if ( $preview['video_id'] ) {
			$thumbnail_object .= '<div class="thumbnail" style="background-image:url(' . esc_url( $preview['thumbnail'] ) . ');" alt="' . esc_attr( $preview['video_post_title'] ) . '" title=""> <p>' . esc_html( $preview['name'] ) . '</p></div>';
		} else {
			$thumbnail_object .= '<div class="no-thumbnail"><p>' . esc_html( $preview['name'] ) . '</p></div>';
		}
	}

	echo '<a href="' . esc_url( $url ) . '">';
	echo '<div class="metadata"><h6>' . esc_html( $title ) . '</h6>' . $language_count . '</div>';
	echo '<div class="languages">';
	echo $thumbnail_object;
	echo '</div>';
	echo '</a>';
	?>
</li>
