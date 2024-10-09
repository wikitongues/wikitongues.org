<li class="gallery-item">
	<?php
		$external_resources = get_field('external_resources');
		$resource_link = get_field('resource_url');
		$resource_title = get_field('resource_title') ? get_field('resource_title') : get_the_title();
		$resource_description = get_field('resource_description');
		$domain = getDomainFromUrl($resource_link);

		echo '<a href="' . esc_url($resource_link) . '">';
		echo '<h1>' . esc_html($resource_title) . '</h1>';
		if ($resource_description) {
				echo '<p class="description">' . esc_html($resource_description) . '</p>';
		}
		echo '<p class="domain">' . esc_html($domain) . '</p>';
		echo '</a>';
	?>
</li>
