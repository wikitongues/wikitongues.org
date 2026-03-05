<li class="gallery-item">
	<?php
	$territory_id = $query->post->ID;
	$meta_query   = array(
		array(
			'key'     => 'territories',
			'value'   => $territory_id,
			'compare' => 'LIKE',
		),
	);

	// Single query: fetch up to 4 languages for thumbnail display and read
	// found_posts for the total count (SQL_CALC_FOUND_ROWS runs automatically).
	$preview_query = new WP_Query(
		array(
			'post_type'      => 'languages',
			'posts_per_page' => 4,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => $meta_query,
		)
	);

	$url              = get_permalink();
	$title            = get_the_title();
	$language_count   = '<aside>' . esc_html( $preview_query->found_posts ) . '</aside>';
	$thumbnail_object = '';

	foreach ( $preview_query->posts as $language_post ) {
		$language_name = get_field( 'standard_name', $language_post->ID ) ?: get_the_title( $language_post->ID );
		$video_query   = get_videos_by_featured_language( get_the_title( $language_post->ID ) );
		if ( $video_query->have_posts() ) {
			while ( $video_query->have_posts() ) {
				$video_query->the_post();
				$thumbnail         = get_custom_image( 'videos' );
				$thumbnail_object .= '<div class="thumbnail" style="background-image:url(' . esc_url( $thumbnail ) . ');" alt="' . esc_attr( get_the_title() ) . '" title=""> <p>' . esc_html( $language_name ) . '</p></div>';
			}
		} else {
			$thumbnail_object .= '<div class="no-thumbnail"><p>' . esc_html( $language_name ) . '</p></div>';
		}
	}
	wp_reset_postdata();

	echo '<a href="' . esc_url( $url ) . '">';
	echo '<div class="metadata"><h6>' . esc_html( $title ) . '</h6>' . $language_count . '</div>';
	echo '<div class="languages">';
	echo $thumbnail_object;
	echo '</div>';
	echo '</a>';
	?>
</li>
