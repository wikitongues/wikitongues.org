<?php
	$image = get_sub_field('image');
	$caption = get_sub_field('image_caption');
	$class = $image ? ' has-image' : '';
	$text_content = wpautop(wp_kses_post(get_sub_field('text_area')));

	echo '<section class="main-content' . $class . '">';
	if ($image) {
		echo '<div class="image-container"><img src="'.get_sub_field('image').'" alt="Your Alt Text" class="your-css-class">';
		if ($caption) {
			echo '<p class="caption">'.$caption.'</p>';
		}
		echo '</div>';
	}
	echo $text_content;
	echo '</section>';