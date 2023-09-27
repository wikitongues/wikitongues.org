<?php 
$thumbnail_title = get_field('thumbnail_title');
$thumbnail_image = get_field('thumbnail_image');
$post_type = get_post_type();

if ( $post_type === 'languages' ) {
	
	$thumbnail_cta_text = 'Read more';

} elseif ( $post_type === 'videos' ) {
	
	$thumbnail_cta_text = 'Watch';

} else { // add conditions for lexicons and misc resources
	
	$thumbnail_cta_test = 'Read more';

}

if ( $thumbnail_image ) {

	echo '<div role="img" class="wt_search-results__thumbnail--image" style="background-image:url('. 
		 $thumbnail_image['url'] .
		 wp_get_attachment_url($thumbnail_image) .
		 '") aria-label="' .
		 $thumbnail_image['alt'] .
		 get_post_meta($thumbnail_image, '_wp_attachment_image_alt', TRUE) .
		 '"></div>';

} elseif ( has_post_thumbnail() ) { 

	echo '<div role="img" class="wt_search-results__thumbnail--image" style="background-image:url('. 
		 get_the_post_thumbnail_url() .
		 '") aria-label="thumbnail"></div>'; // bring in actual alt

} else {

	echo '<div class="wt_search-results__thumbnail--no-image"></div>'; 

} ?>

<div class="wt_search-results__thumbnail--copy">
<?php

	if ( $post_type === 'languages' ) {

		if ( $thumbnail_title ) {

			echo '<p><strong>' . $thumbnail_title . '</strong>';

		} else {

			echo '<p><strong>' . get_field( 'standard_name' ) . ' language</strong>';
				 
		}

		echo '</br><span>Also known as ' . get_field( 'alternate_names' ) . '</span></p>';

	} elseif ( $post_type === 'videos' ) {

		if ( $thumbnail_title ) {

			echo '<p><strong>' . $thumbnail_title . '</strong>';

		} else {

			echo '<p><strong>' . get_field( 'video_title' ) . '</strong>';
			 
		}

		echo '</br><span>Language video</span></p>';

	} else { // add conditions for lexicons and misc resources
		
		echo '<p><strong>' . get_the_title() . '</strong></p>';
	}


	if ( $thumbnail_cta_text ) {

		echo '<a href="' . get_the_permalink() . '">' .
			 $thumbnail_cta_text . 
			 '<i class="fa-regular fa-arrow-right-long"></i></a>'; // add arrow

	} else {

		echo '<a href="' . get_the_permalink() . '">' .
			 'Read more<i class="fa-regular fa-arrow-right-long"></i></a>'; // add arrow

	}

?>
</div>