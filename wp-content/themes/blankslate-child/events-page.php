<?php
/* Template Name: Events */
get_header();
$page_banner = get_field( 'events_banner' );
require 'modules/banners/banner--main.php';
?>

<div class="events">
	<div class="filter-buttons">
	<button id="upcoming-button" class="filter-button active" onclick="filterEvents('upcoming')">Upcoming</button>
	<button id="past-button" class="filter-button" onclick="filterEvents('past')">Past</button>
	</div>

	<ul class="event-list">
	<?php
	$date_now    = get_current_datetime();
	$compare     = isset( $_GET['events'] ) && $_GET['events'] === 'past' ? '<' : '>=';
	$query_args  = array(
		'posts_per_page' => -1,
		'post_type'      => 'events',
		'meta_query'     => array(
			array(
				'key'     => 'event_datetime',
				'compare' => $compare,
				'value'   => $date_now,
				'type'    => 'DATETIME',
			),
		),
		'order'          => 'ASC',
		'orderby'        => 'meta_value',
		'meta_key'       => 'event_datetime',
		'meta_type'      => 'DATETIME',
	);
	$event_query = new WP_Query( $query_args );

	if ( $event_query->have_posts() ) {
		while ( $event_query->have_posts() ) {
			$event_query->the_post();
			echo render_event( get_the_ID() );
		}
	} else {
		echo '<li class="empty-events"><p>No events found.</p></li>';
	}
	wp_reset_postdata();
	?>
	</ul>
</div>

<?php
require 'modules/newsletter.php';
get_footer();
?>

<script>
function filterEvents(temporal) {
	const data = {
	action: 'filter_events',
	temporal: temporal
	};
	jQuery.ajax({
	url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
	type: 'POST',
	data: data,
	success: function(response) {
		const eventList = document.querySelector('.event-list');
		if (response.success && response.data) {
			eventList.innerHTML = response.data;
		} else {
			eventList.innerHTML = '<li class="empty-events">No events found.</li>';
		}

		document.getElementById('upcoming-button').classList.toggle('active', temporal === 'upcoming');
		document.getElementById('past-button').classList.toggle('active', temporal === 'past');
	},
	error: function(xhr, status, error) {
		console.log('Error:', error);
		console.log('Status:', status);
		console.log('Response:', xhr.responseText);
	}
	});
}
</script>
