<?php
/**
 * Analytics for the report's pages. As everywhere on the site, the code only
 * pushes to the data layer; Google Tag Manager forwards to GA4.
 *
 * - Page details, before GTM loads: rights_page (hub, region, report),
 *   rights_region, rights_country, report_version, release_status, and
 *   viewer_role (press, admin or empty), so GA4 can leave out our own and
 *   journalists' visits.
 * - rights_updates_signup, when "Get release updates" is clicked.
 * - Donate clicks are the site's existing donate_cta_click, through each
 *   link's data-cta-location; PDF downloads are the download gateway's
 *   funnel events, with post_type rights_reports.
 *
 * @package WT\Rights
 */

/**
 * The current page's report details, or null when it isn't a report page.
 *
 * @return array<string, string>|null
 */
function wt_rights_page_context(): ?array {
	$context = null;
	if ( get_query_var( 'wt_rights_hub' ) ) {
		$context = array( 'rights_page' => 'hub' );
	} elseif ( is_tax( 'rights_region' ) ) {
		$term    = get_queried_object();
		$context = array(
			'rights_page'    => 'region',
			'rights_region'  => $term instanceof WP_Term ? $term->slug : '',
			'release_status' => $term instanceof WP_Term ? wt_rights_region_status( $term->term_id ) : '',
		);
	} elseif ( is_singular( 'rights_reports' ) ) {
		$post    = get_queried_object();
		$region  = $post instanceof WP_Post ? wt_rights_report_region( $post->ID ) : null;
		$context = array(
			'rights_page'    => 'report',
			'rights_region'  => $region ? $region->slug : '',
			'rights_country' => $post instanceof WP_Post ? $post->post_name : '',
			'report_version' => $post instanceof WP_Post ? (string) get_post_meta( $post->ID, '_rights_version', true ) : '',
			'release_status' => $region ? wt_rights_region_status( $region->term_id ) : '',
		);
	}
	if ( null === $context ) {
		return null;
	}
	$context['viewer_role'] = wt_rights_viewer_role();
	return $context;
}

/**
 * The viewer's role for analytics: press, admin, or '' for the public.
 */
function wt_rights_viewer_role(): string {
	if ( current_user_can( 'edit_rights_reports' ) ) {
		return 'admin';
	}
	if ( function_exists( 'wt_rights_is_press_only' ) && wt_rights_is_press_only() ) {
		return 'press';
	}
	return '';
}

/**
 * Prints the page details into the data layer, before GTM loads.
 */
function wt_rights_push_page_context(): void {
	$context = wt_rights_page_context();
	if ( null === $context ) {
		return;
	}
	echo '<script>window.dataLayer=window.dataLayer||[];window.dataLayer.push(' . wp_json_encode( $context ) . ');</script>' . "\n";
}
add_action( 'wt_head_before_analytics', 'wt_rights_push_page_context' );

/**
 * Loads the click tracking on report pages only.
 */
function wt_rights_enqueue_analytics(): void {
	if ( null === wt_rights_page_context() ) {
		return;
	}
	wp_enqueue_script( 'wt-rights', plugins_url( 'assets/rights.js', __DIR__ ), array(), WT_RIGHTS_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'wt_rights_enqueue_analytics' );
