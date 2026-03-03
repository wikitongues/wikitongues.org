<?php
// Stats section for the unfiltered languages archive.
// Cached via transient; invalidated on relevant post saves.

$stats = get_transient( 'wt_archive_stats' );

if ( false === $stats ) {
	global $wpdb;

	// 1. Languages with at least one video, lexicon (source or target), or external resource.
	// Queries raw ACF field meta values directly — avoids reliance on stale count metas.
	// The REGEXP matches any non-empty serialized PHP array (a:1:{...} or more).
	$language_ids = $wpdb->get_col(
		"SELECT DISTINCT p.ID
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
		WHERE p.post_type = 'languages'
		AND p.post_status = 'publish'
		AND pm.meta_key IN ('speakers_recorded', 'lexicon_source', 'lexicon_target', 'external_resources')
		AND pm.meta_value REGEXP '^a:[1-9][0-9]*:'"
	);

	$language_ids   = $language_ids ?: array();
	$language_count = count( $language_ids );

	// 2. Total materials: all published videos + lexicons + resources.
	$video_count     = (int) wp_count_posts( 'videos' )->publish;
	$lexicon_count   = (int) wp_count_posts( 'lexicons' )->publish;
	$resource_count  = (int) wp_count_posts( 'resources' )->publish;
	$total_materials = $video_count + $lexicon_count + $resource_count;

	$total_languages = (int) wp_count_posts( 'languages' )->publish;

	// 3. Nations: distinct territory IDs from qualifying languages,
	// validated against actual published territory posts to exclude stale IDs.
	$all_territory_ids   = get_posts(
		array(
			'post_type'      => 'territories',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	$total_territories   = count( $all_territory_ids );
	$valid_territory_set = array_flip( $all_territory_ids );

	$matched_territories = array();
	foreach ( $language_ids as $lang_id ) {
		$territories = get_field( 'territories', $lang_id, false );
		if ( ! is_array( $territories ) ) {
			$territories = ! empty( $territories ) ? array( $territories ) : array();
		}
		foreach ( $territories as $tid ) {
			$tid = (int) $tid;
			if ( isset( $valid_territory_set[ $tid ] ) ) {
				$matched_territories[ $tid ] = true;
			}
		}
	}
	$nations_count = count( $matched_territories );

	$stats = array(
		'language_count'    => $language_count,
		'total_materials'   => $total_materials,
		'nations_count'     => $nations_count,
		'total_languages'   => $total_languages,
		'total_territories' => $total_territories,
	);

	set_transient( 'wt_archive_stats', $stats, 6 * HOUR_IN_SECONDS );
}

$language_count    = $stats['language_count'];
$total_materials   = $stats['total_materials'];
$nations_count     = $stats['nations_count'];
$total_languages   = $stats['total_languages'];
$total_territories = $stats['total_territories'];

$languages_pct = ( $total_languages > 0 ) ? round( $language_count / $total_languages * 100 ) : 0;
$nations_pct   = ( $total_territories > 0 ) ? round( $nations_count / $total_territories * 100 ) : 0;

?>
<section class="wt_archive-languages__stats">
	<div class="wt_archive-languages__stats-item">
		<strong><?php echo number_format( $language_count ); ?> languages</strong>
		<span>over <?php echo esc_html( (string) $languages_pct ); ?>% of every language in the world</span>
	</div>
	<div class="wt_archive-languages__stats-item">
		<strong><?php echo number_format( $total_materials ); ?> resources</strong>
		<span>Lexicons, oral histories, and other resources</span>
	</div>
	<div class="wt_archive-languages__stats-item">
		<strong><?php echo number_format( $nations_count ); ?> nations</strong>
		<span>our work extends over <?php echo esc_html( (string) $nations_pct ); ?>% of the world</span>
	</div>
</section>
