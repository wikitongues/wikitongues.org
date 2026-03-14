<?php
/**
 * Uninstall — runs when the plugin is deleted via the WP admin.
 *
 * Sub-phase 0: removes the version option seeded on activation.
 * Sub-phase 1 will extend this to drop DB tables (wp_dg_people,
 * wp_dg_download_events, wp_dg_webhook_delivery, wp_dg_tokens)
 * after confirming the operator has exported any data they need.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'dg_version' );
