<li class="gallery-item gallery-item--card">
	<?php
	$url      = get_permalink();
	$title    = get_the_title();
	$location = get_field( 'location' );

	echo '<a href=' . esc_url( $url ) . '>' . $title . '&nbsp;— ' . $location . '</a>';
	?>
</li>
