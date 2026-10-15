<?php
	$image   = get_sub_field( 'image' );
	$caption = get_sub_field( 'image_caption' );
	// The page's first text row is its lede and opens with a drop cap.
	$class        = ( isset( $text_row_index ) && 0 === $text_row_index ) ? ' wt_prose--lede' : '';
	$text_content = wpautop( wp_kses_post( get_sub_field( 'text_area' ) ) );
	// Editors leave blank lines (<p>&nbsp;</p>) to add space; spacing is the
	// stylesheet's job, so drop them.
	$text_content = preg_replace( '#<p>(?:\s|&nbsp;|\x{00A0})*</p>\s*#u', '', $text_content );

	echo '<section class="wt_text-row wt_prose' . $class . '">';
if ( $image ) {
	// Same markup as a captioned image in a blog post, so prose.styl styles both.
	$image_alt = get_post_meta( attachment_url_to_postid( $image ), '_wp_attachment_image_alt', true );
	echo '<figure class="wp-caption">';
	echo '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $image_alt ) . '">';
	if ( $caption ) {
		echo '<figcaption class="wp-caption-text">' . wp_kses_post( $caption ) . '</figcaption>';
	}
	echo '</figure>';
}
	echo $text_content;
	echo '</section>';
