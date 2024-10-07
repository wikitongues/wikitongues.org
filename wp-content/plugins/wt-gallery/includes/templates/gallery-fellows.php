<li class="gallery-item">
    <?php
    $thumbnail_url = get_custom_image('fellows');
    $location = get_field('fellow_location');
    $fellow_language_preferred_name = get_field('fellow_language_preferred_name');
		$thumbnail = '';

    if ($thumbnail_url) {
        $thumbnail = '<div class="thumbnail" style="background-image:url('.esc_url($thumbnail_url).');" alt="' . get_the_title() . '"></div><span>&nbsp;</span>';
    } else {
        $thumbnail = '<div class="no-thumbnail"><p>Thumbnail unavailable</p></div><span>&nbsp;</span>';
    }

    $metadata = '<div class="fellow-metadata"><p>'.$location.'</p><h3>'.$fellow_language_preferred_name.'</h3></div>';

		echo '<a href="'.esc_url(get_permalink()).'">';
		echo $thumbnail;
		echo '<div><h3>' . $title . '</h3></div>';
		echo $metadata;
		echo '</a>';
		?>
</li>
