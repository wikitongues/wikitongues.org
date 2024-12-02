<?php

$date_now = get_current_datetime();

function format_event_date_with_proximity($date_string) {
	$event_date = new DateTime($date_string);
	$today = new DateTime();
	$days_difference = (int) $today->diff($event_date)->format('%R%a');

	$proximity = '';
	if ($days_difference >= -7 && $days_difference <= 7) {
			$day_of_week = $event_date->format('l');
			if ($days_difference > 0) $proximity = " Next $day_of_week, ";
			elseif ($days_difference < 0) $proximity = " Last $day_of_week, ";
			else $proximity = " Today, ";
	}
	return $proximity . $event_date->format('j F Y, gA');
}

// Define function for rendering each event
if (!function_exists('render_event')) {
		function render_event($post_id) {

		$event_datetime = esc_html(format_event_date_with_proximity(get_post_meta($post_id, "event_datetime", true)));
		$event_timezone = esc_html(get_post_meta($post_id, "event_timezone", true));
		$event_location = esc_html(get_post_meta($post_id, "event_location", true));
		$event_description = esc_html(get_post_meta($post_id, "event_description", true));
		$event_registration_link = esc_url(get_post_meta($post_id, "event_registration_link", true));

		ob_start(); ?>
		<li class="event-entry">
				<h3 class="event-question"><?php echo esc_html(get_the_title($post_id)); ?></h3>
				<div class="event-details">
						<p><?php echo $event_datetime . ' ' . $event_timezone; ?></p>
						<p><?php echo $event_location; ?></p>
				</div>
				<p class="event-description"><?php echo $event_description; ?></p>
				<a href="<?php echo $event_registration_link; ?>">Register</a>
		</li>
		<?php
		return ob_get_clean();
}}

function ajax_filter_events() {
	ob_clean();
	$temporal = isset($_POST['temporal']) ? sanitize_text_field($_POST['temporal']) : 'upcoming';
	$date_now = date('Y-m-d H:i:s');
	$compare = $temporal === 'upcoming' ? '>=' : '<';

	$posts = get_posts(array(
			'posts_per_page' => -1,
			'post_type'      => 'events',
			'meta_query'     => array(
					array(
							'key'     => 'event_datetime',
							'compare' => $compare,
							'value'   => $date_now,
							'type'    => 'DATETIME'
					)
			),
			'order'     => 'ASC',
			'orderby'   => 'meta_value',
			'meta_key'  => 'event_datetime',
			'meta_type' => 'DATETIME'
	));

	if ($posts) {
			ob_start();
			foreach ($posts as $post) {
					setup_postdata($post);
					echo render_event($post->ID);
			}
			wp_reset_postdata();
			$response = ob_get_clean();
			wp_send_json_success($response);
	} else {
		wp_send_json_error('<li class="empty-events">No events found.</li>');
	}

	echo $response;
	wp_die();
}

add_action('wp_ajax_filter_events', 'ajax_filter_events');
add_action('wp_ajax_nopriv_filter_events', 'ajax_filter_events');
