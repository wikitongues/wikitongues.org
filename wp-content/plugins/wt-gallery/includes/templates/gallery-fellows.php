<li class="gallery-item">
    <?php
		$thumbnail_url = get_custom_image('fellows');
    $fellow_year = get_field('fellow_year');
    $location = get_field('fellow_location');
    $fellow_language_preferred_name = get_field('fellow_language_preferred_name');
		$thumbnail = '';

    if ($thumbnail_url) {
        $thumbnail = '<div class="thumbnail" style="background-image:url('.esc_url($thumbnail_url).');" alt="' . get_the_title() . '"></div><span class="thumbnail-spacer">&nbsp;</span>';
    } else {
        $thumbnail = '<div class="no-thumbnail"><p>Thumbnail unavailable</p></div><span class="thumbnail-spacer">&nbsp;</span>';
    }

    $metadata = '<div class="fellow-metadata"><h3>'.$fellow_language_preferred_name.'</h3>';
		$metadata .= '<span><p>'.$location.'</p><p>'.$fellow_year.'</p></span></div>';

		echo '<a href="'.esc_url(get_permalink()).'">';
		echo $thumbnail;
		echo '<div><h3>' . $title . '</h3></div>';
		echo $metadata;
		echo '</a>';
		?>
</li>
