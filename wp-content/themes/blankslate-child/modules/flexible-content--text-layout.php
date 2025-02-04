<?php
	echo '<section class="main-content">';
	echo wpautop(wp_kses_post(get_sub_field('text_area')));
	echo '</section>';