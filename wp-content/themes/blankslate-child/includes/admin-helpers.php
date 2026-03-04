<?php

/**
 * Register non-navigable section header items that act as visual group labels
 * in the admin sidebar. Styled and stripped of navigation below.
 *
 * Each must have a unique position to avoid colliding in the $menu array.
 */
function wt_register_admin_section_headers(): void {
	add_menu_page( 'Archive', 'Archive', 'edit_posts', 'wt-section-archive', '__return_null', '', 91 );
	add_menu_page( 'People', 'People', 'edit_posts', 'wt-section-people', '__return_null', '', 92 );
	add_menu_page( 'Publishing', 'Publishing', 'edit_posts', 'wt-section-publishing', '__return_null', '', 93 );
	add_menu_page( 'Documents', 'Documents', 'edit_posts', 'wt-section-documents', '__return_null', '', 94 );
	add_menu_page( 'Admin', 'Admin', 'manage_options', 'wt-section-admin', '__return_null', '', 95 );
}
add_action( 'admin_menu', 'wt_register_admin_section_headers' );

function custom_reorder_admin_menu( array $menu_order ): array {
	global $menu;

	if ( ! is_array( $menu ) ) {
		return $menu_order;
	}

	// Add wt-menu-section CSS class to section header items.
	foreach ( $menu as $key => $item ) {
		if ( isset( $item[2] ) && str_starts_with( $item[2], 'wt-section-' ) ) {
			$menu[ $key ][4] .= ' wt-menu-section';
		}
	}

	$new_order = array(
		'index.php',                          // Dashboard
		'wt-section-archive',                 // ── Archive ──────────────────────
		'edit.php?post_type=languages',       // Languages
		'edit.php?post_type=videos',          // Videos
		'edit.php?post_type=captions',        // Captions
		'edit.php?post_type=lexicons',        // Lexicons
		'edit.php?post_type=resources',       // Resources
		'edit.php?post_type=territories',     // Territories
		'wt-section-people',                  // ── People ───────────────────────
		'edit.php?post_type=fellows',         // Fellows
		'edit.php?post_type=team',            // Team
		'edit.php?post_type=partners',        // Partners
		'wt-section-publishing',              // ── Publishing ───────────────────
		'edit.php?post_type=page',            // Pages
		'edit.php?post_type=blog',            // Blog
		'edit.php?post_type=careers',         // Careers
		'edit.php?post_type=events',          // Events
		'edit.php?post_type=faq',             // FAQ
		'edit.php?post_type=reports',         // Reports
		'upload.php',                         // Media
		'wt-section-documents',               // ── Documents ────────────────────
		'edit.php?post_type=documents',       // Documents
		'edit.php?post_type=document_files',  // Document Files
		'wt-section-admin',                   // ── Admin ────────────────────────
		'edit.php?post_type=acf-field-group', // ACF
		// 'batch-update',                    // Batch Update
		'airtable-links',                     // Options (first child determines parent slug)
		'custom-search',                      // Options (fallback if custom-search is first child)
		'plugins.php',                        // Plugins
		'integromat',                         // Make
		'users.php',                          // Users
		'themes.php',                         // Appearance
		'tools.php',                          // Tools
		'options-general.php',                // Settings
	);

	$reordered_menu = array();

	foreach ( $new_order as $slug ) {
		foreach ( $menu as $key => $value ) {
			if ( $value[2] === $slug ) {
				$reordered_menu[ $key ] = $value;
				unset( $menu[ $key ] );
				break;
			}
		}
	}

	// Append any items not explicitly listed (prevents items vanishing).
	$menu = array_merge( $reordered_menu, $menu );

	// Return slugs in the correct format for the menu_order filter.
	return array_column( array_values( $menu ), 2 );
}
add_filter( 'custom_menu_order', '__return_true' );
add_filter( 'menu_order', 'custom_reorder_admin_menu', 999 );

function custom_remove_menu_pages(): void {
	remove_menu_page( 'edit.php' );          // Posts
	remove_menu_page( 'edit-comments.php' ); // Comments

	// Remove all submenus for CPTs — the parent link goes directly to the list
	// view, so "All [CPT]" and "Add New" dropdowns are redundant.
	$cpts = array(
		'languages',
		'videos',
		'captions',
		'lexicons',
		'resources',
		'territories',
		'fellows',
		'team',
		'partners',
		'careers',
		'page',
		'blog',
		'events',
		'faq',
		'reports',
		'documents',
		'document_files',
	);
	foreach ( $cpts as $cpt ) {
		$parent = 'edit.php?post_type=' . $cpt;
		// remove_submenu_page( $parent, $parent );
		remove_submenu_page( $parent, 'post-new.php?post_type=' . $cpt );
	}
}
add_action( 'admin_menu', 'custom_remove_menu_pages', 999 );

/**
 * Style section header items and strip their links so they don't navigate.
 */
add_action(
	'admin_head',
	function (): void {
		?>
		<style>
			#adminmenu .wp-menu-image {
				display: none !important;
			}
			#adminmenu a.menu-top {
				padding: 0 !important;
			}
			#adminmenu .wp-menu-name {
				padding: 4px 8px !important;
			}
			#adminmenu li.menu-top:not(.wt-menu-section) a.menu-top .wp-menu-name {
				padding-left: 16px !important;
			}
			#adminmenu li.menu-top {
				min-height: 0 !important;
			}
			#adminmenu a {
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
			#adminmenu .wt-menu-section > a,
			#adminmenu .wt-menu-section > a:hover {
				pointer-events: none;
				cursor: default;
				color: #a7aaad !important;
				background: transparent !important;
				font-size: 10px;
				font-weight: 700;
				text-transform: uppercase;
				letter-spacing: 0.08em;
				padding-top: 16px;
				padding-bottom: 4px;
				opacity: 1 !important;
			}
			#adminmenu .wt-menu-section .wp-menu-image {
				display: none;
			}
			#adminmenu .wt-menu-section .wp-menu-name {
				padding-left: 0;
			}

			#adminmenu .wp-submenu li a {
				padding-left: 24px !important;
			}


		</style>
		<script>
			document.addEventListener( 'DOMContentLoaded', function () {
				document.querySelectorAll( '#adminmenu .wt-menu-section > a' ).forEach( function ( el ) {
					el.removeAttribute( 'href' );
				} );
			} );
		</script>
		<?php
	}
);
