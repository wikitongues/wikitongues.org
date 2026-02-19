<?php
$categories                     = get_the_terms( get_the_ID(), 'fellow-category' );
$category_names                 = implode( ', ', array_map( 'esc_html', wp_list_pluck( $categories, 'name' ) ) );
$class                          = $atts['custom_class'];
$fellow_language                = get_field( 'fellow_language' );
$fellow_language_preferred_name = get_field( 'fellow_language_preferred_name' );
$fellow_banner                  = get_field( 'fellow_banner' );
$fellow_year                    = get_field( 'fellow_year' );
$location                       = get_field( 'fellow_location' );
$marketing_text                 = get_field( 'marketing_text' );
$standard_name                  = '';
$thumbnail                      = '';
$thumbnail_url                  = get_custom_image( 'fellows' );
$url                            = get_permalink();

if ( $fellow_language instanceof WP_Post ) {// Handle single language, use the global preferred name if passed
	$output_name = get_post_meta( $fellow_language->ID, 'standard_name', true );
} elseif ( is_array( $fellow_language ) ) {
	if ( count( $fellow_language ) > 1 ) {
		// If array, do not use preferred name since we cannot set distinct names per entry.
		foreach ( $fellow_language as $language ) {
			if ( $language instanceof WP_Post ) {
				$standard_name = get_post_meta( $language->ID, 'standard_name', true );
				$output_name   = $fellow_language_preferred_name ? $fellow_language_preferred_name : $standard_name;
			}
		}
	} else {
		// If single language, use preferred name if passed.
		foreach ( $fellow_language as $language ) {
			if ( $language instanceof WP_Post ) {
				$standard_name = get_post_meta( $language->ID, 'standard_name', true );
				$output_name   = $fellow_language_preferred_name ? $fellow_language_preferred_name : $standard_name;
			}
		}
	}
} else {
	$output .= '<span class="identifier">' . esc_html( $fellow_language ) . '</span>';
}

if ( $thumbnail_url ) {
	$thumbnail = '<div class="thumbnail" style="background-image:url(' . esc_url( $thumbnail_url ) . ');" alt="' . get_the_title() . '"></div><span class="thumbnail-spacer">&nbsp;</span>';
} else {
	$thumbnail = '<div class="no-thumbnail"><p>Thumbnail unavailable</p></div><span class="thumbnail-spacer">&nbsp;</span>';
}

if ( empty( $class ) || strpos( $class, 'full' ) !== false ) {
	echo '<li class="gallery-item">';
	echo '<a href="' . esc_url( $url ) . '">';
	echo $thumbnail;
	echo '<div class="details">';
	echo '<h5 class="name">' . $title . '</h5>';
	echo '<div class="fellow-metadata">';
	if ( substr( $output_name, -7 ) !== 'anguage' ) {
		$output_name .= ' Language';
	}
	echo '<h5 class="language">' . $output_name . '</h5>';
	echo '<p>' . $category_names . '</p>';
	echo '<span><p>' . $location . '</p><p>' . $fellow_year . '</p></span>';
	echo '</div>';
	echo '</div></a>';
	echo '</li>';
} elseif ( $class === 'display' ) { // Display fellow on single language page
	echo '<li class="gallery-item">';
	echo '<a href="' . esc_url( $url ) . '">';
	echo $thumbnail;
	echo '<div class="details">';
	echo '<h3 class="name">' . $title . '</h3>';
	echo '<div class="fellow-metadata">';
	echo '<h6 class="description">' . esc_html( $fellow_banner['banner_copy'] ) . '</h6>';
	echo '<p>' . $category_names . '</p>';
	echo '<span><p>' . $location . '</p><p>' . $fellow_year . '</p></span>';
	echo '</div>';
	echo '</div></a>';
	echo '</li>';
} elseif ( $class === 'custom fundraiser' ) {
	echo '<li>';
	echo '<div class="thumbnail" style="background-image:url(' . esc_url( $thumbnail_url ) . ');" alt="' . get_the_title() . '"></div>';
	echo '<section>';
	echo '<strong>' . $title . '<br>' . $location . '</strong>';
	echo '<p>' . $marketing_text . '</p>';
	echo '</section>';
	echo '</li>';
}
