<li class="gallery-item">
	<?php
	$title = get_the_title();
	$location = get_field('location');

	echo '<a href="">';
	echo '<h3>' . $title . '</h3>';
	echo '<p>&nbsp;— '.$location.'</p>';
	echo '</a>';
	?>
</li>
