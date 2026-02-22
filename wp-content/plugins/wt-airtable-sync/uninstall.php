<?php
/**
 * Runs when the plugin is deleted via the WP admin.
 * Removes any options written by this plugin.
 * Does NOT delete synced post content — that data belongs to the site.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wt_sync_key_missing' );
