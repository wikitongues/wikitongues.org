<?php
	$regions        = wp_get_post_terms( $territory_id, 'region' );
	$current_region = $regions[0] ?? null; // Assuming only 1 assigned region

if ( ! $current_region || is_wp_error( $current_region ) ) {
		return; // No region assigned, nothing to build
}

	$current_parent_id = $current_region->parent ?: $current_region->term_id;

	require 'territories-active-region.php';
	require 'territories-sibling-regions.php';
	require 'territories-parent-regions.php';
