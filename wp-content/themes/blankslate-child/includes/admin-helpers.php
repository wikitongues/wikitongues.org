<?php

function custom_reorder_admin_menu( $menu_order ) {
	global $menu;

	// Ensure $menu is properly initialized
	if ( ! is_array( $menu ) ) {
		return $menu_order; // Return the original order if $menu is not properly initialized
	}

	// Define the new order you want for the menu items
	$new_order = array(
		'index.php',                    // Dashboard
		'separator1',                   // Separator
		'edit.php?post_type=page',      // Pages
		'separator2',                   // Separator
		'edit.php?post_type=languages', // Languages
		'edit.php?post_type=videos',    // Videos
		'edit.php?post_type=lexicons',  // Lexicons
		'edit.php?post_type=resources', // Resources
		'edit.php?post_type=fellows',   // Fellows
		'edit.php?post_type=team',      // Team
		'edit.php?post_type=partners',  // Partners
		'edit.php?post_type=reports',   // Reports
		'separator3',                   // Separator
		'upload.php',                   // Media
		'themes.php',                   // Appearance
		'plugins.php',                  // Plugins
		'batch-update.php',             // Batch Update
		'users.php',                    // Users
		'tools.php',                    // Tools
		'options-general.php',          // Settings
	);

	// Create a new array to hold the reordered menu
	$reordered_menu = array();

	// Loop through the desired order and add the menu items to the reordered array
	foreach ( $new_order as $item ) {
		foreach ( $menu as $key => $value ) {
			if ( $value[2] === $item ) {
				$reordered_menu[ $key ] = $value;
				unset( $menu[ $key ] ); // Remove the item from the original menu
				break;
			}
		}
	}

	// Add any remaining menu items that were not in the desired order to the end
	$menu = array_merge( $reordered_menu, $menu ); // Update the global $menu with the new order

	return array_keys( $menu ); // Return the reordered menu keys
}
add_filter( 'custom_menu_order', '__return_true' );
add_filter( 'menu_order', 'custom_reorder_admin_menu', 999 ); // Set a high priority to execute last

// Remove specific menu items
function custom_remove_menu_pages() {
	remove_menu_page( 'edit.php' ); // Posts
	// remove_menu_page('upload.php'); // Media
	remove_menu_page( 'edit-comments.php' ); // Comments
}
add_action( 'admin_menu', 'custom_remove_menu_pages', 999 ); // Ensure this runs after custom_reorder_admin_menu
