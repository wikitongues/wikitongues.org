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

	// Count query: fetch only 1 post with IDs-only fields so WordPress runs
	// SQL_CALC_FOUND_ROWS cheaply, without loading full post objects.
	$count_query        = new WP_Query(
		array(
			'post_type'      => 'languages',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => $meta_query,
		)
	);
	$language_count_num = $count_query->found_posts;

	// Preview query: fetch up to 4 languages for thumbnail display only.
	$preview_query = new WP_Query(
		array(
			'post_type'      => 'languages',
			'posts_per_page' => 4,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'meta_query'     => $meta_query,
		)
	);

	$url              = get_permalink();
	$title            = get_the_title();
	$language_count   = '<aside>' . esc_html( $language_count_num ) . '</aside>';
	$thumbnail_object = '';

	foreach ( $preview_query->posts as $language_post ) {
		$language_name = get_field( 'standard_name', $language_post->ID );
		$video_query   = get_videos_by_featured_language( $language_post->post_title );
		if ( $video_query->have_posts() ) {
			while ( $video_query->have_posts() ) {
				$video_query->the_post();
				$thumbnail         = get_custom_image( 'videos' );
				$thumbnail_object .= '<div class="thumbnail" style="background-image:url(' . esc_url( $thumbnail ) . ');" alt="' . get_the_title() . '" title=""> <p>' . esc_html( $language_name ) . '</p></div>';
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
