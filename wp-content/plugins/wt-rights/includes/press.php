<?php
/**
 * The press role's experience: journalists land on the report, never in the
 * dashboard. Press accounts are made by an admin (Users → Add New, role Press).
 *
 * @package WT\Rights
 */

/**
 * Whether a user is press only (not also an editor or admin).
 *
 * @param WP_User|null $user A user; default the current one.
 */
function wt_rights_is_press_only( ?WP_User $user = null ): bool {
	$user = $user ?? wp_get_current_user();
	return $user instanceof WP_User && $user->exists() && in_array( 'press', (array) $user->roles, true ) && ! user_can( $user, 'edit_posts' );
}

/**
 * Sends press accounts to the report hub after logging in.
 *
 * @param string           $redirect_to The default destination.
 * @param string           $requested   The requested destination.
 * @param WP_User|WP_Error $user        The user, or an error.
 */
function wt_rights_press_login_redirect( string $redirect_to, string $requested, $user ): string {
	if ( $user instanceof WP_User && wt_rights_is_press_only( $user ) ) {
		return home_url( '/' . WT_RIGHTS_BASE . '/' );
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'wt_rights_press_login_redirect', 10, 3 );

/**
 * Keeps press accounts out of wp-admin (AJAX still works for front-end features).
 */
function wt_rights_press_admin_lockout(): void {
	if ( wt_rights_is_press_only() && ! wp_doing_ajax() ) {
		wp_safe_redirect( home_url( '/' . WT_RIGHTS_BASE . '/' ) );
		exit;
	}
}
add_action( 'admin_init', 'wt_rights_press_admin_lockout' );

/**
 * Hides the admin bar from press accounts.
 *
 * @param bool $show Whether to show it.
 */
function wt_rights_press_admin_bar( bool $show ): bool {
	return wt_rights_is_press_only() ? false : $show;
}
add_filter( 'show_admin_bar', 'wt_rights_press_admin_bar' );
