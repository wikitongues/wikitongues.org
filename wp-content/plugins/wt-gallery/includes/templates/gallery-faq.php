<li class="gallery-item">
  <?php
    $url = get_permalink();
    $title = get_the_title();
		$text = get_the_content();
	?>
		<strong><?php echo esc_html($title); ?></strong>
		<?php echo wpautop(wp_kses_post($text)); ?>
</li>
