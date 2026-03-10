<?php
// Stats section for the unfiltered languages archive.
// Cached via transient; invalidated on relevant post saves.

$stats = wt_get_archive_stats();

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
