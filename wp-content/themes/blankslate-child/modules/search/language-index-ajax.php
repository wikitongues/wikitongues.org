<?php
	require_once '../../../../../wp-load.php'; // Load WordPress environment

	global $wpdb;

	$start_letter = isset( $_GET['start_letter'] ) ? sanitize_text_field( $_GET['start_letter'] ) : '';
if ( ! $start_letter ) {
	echo json_encode( array( 'error' => 'Missing letter' ) );
	exit;
}

	// **Check if cached data exists**
	$cache_key = "languages_list_$start_letter";
	$languages = get_transient( $cache_key );

if ( $languages === false ) { // If cache does not exist, fetch from database
	// Debugging output
	// file_put_contents(__DIR__ . '/debug.log', "Requested letter: $start_letter\n", FILE_APPEND);

	// Query database for languages starting with the specified letter
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"
				SELECT p.ID,
					TRIM(pm.meta_value) AS standard_name,
					(SELECT pm2.meta_value FROM {$wpdb->postmeta} pm2
					WHERE pm2.post_id = p.ID
					AND pm2.meta_key = 'speakers_recorded'
					LIMIT 1) AS speakers_recorded
				FROM {$wpdb->posts} p
				JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'languages'
				AND p.post_status = 'publish'
				AND pm.meta_key = 'standard_name'
				AND TRIM(LOWER(pm.meta_value)) LIKE LOWER(%s)
				ORDER BY CONVERT(pm.meta_value USING utf8mb4) COLLATE utf8mb4_unicode_520_ci ASC
			",
			$start_letter . '%'
		)
	);

	$languages = array();

	foreach ( $results as $row ) {
		// Determine if speakers_recorded is present
		$has_speakers = false;
		if ( ! empty( $row->speakers_recorded ) ) {
			$decoded = @unserialize( $row->speakers_recorded );
			if ( $decoded !== false && is_array( $decoded ) && count( $decoded ) > 0 ) {
				$has_speakers = true;
			}
		}

		// Add each language record
		$languages[] = array(
			'id'            => $row->ID,
			'standard_name' => $row->standard_name,
			'permalink'     => get_permalink( $row->ID ),
			'has_speakers'  => $has_speakers,
			'iso'           => get_the_title( $row->ID ), // Use post title as ISO
		);
	}

	// **Store query result in cache (12 hours)**
	set_transient( $cache_key, $languages, 12 * HOUR_IN_SECONDS );

	// Debug: Log cache store
	// file_put_contents(__DIR__ . '/debug.log', "Cache stored for: $start_letter\n", FILE_APPEND);
}

	// **Return cached or fetched data**
	header( 'Content-Type: application/json' );
	echo json_encode( $languages );
	exit;
