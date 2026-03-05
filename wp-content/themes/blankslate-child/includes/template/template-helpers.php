<?php
// Determine the environment
function get_environment() {
	if ( strpos( $_SERVER['HTTP_HOST'], 'localhost' ) !== false ) {
			return 'localhost';
	} elseif ( strpos( $_SERVER['HTTP_HOST'], 'staging' ) !== false ) {
			return 'staging';
	} else {
			return '';
	}
}

function get_current_datetime() {
	return wp_date( 'Y-m-d H:i:s' );
}

function log_data( $data, $method = 'console' ) {
	// methods: console, dom
	if ( $method === 'console' ) {
		echo '<script>';
		echo 'console.log(' . json_encode( $data ) . ')';
		echo '</script>';
	} elseif ( $method === 'dom' ) {
		echo '<pre>';
		print_r( $data );
		echo '</pre>';
	}
}

function get_url() {
	$url = home_url();
}

add_action( 'wp_head', 'modify_page_title' );
function modify_page_title() {
	$environment = get_environment();
	if ( $environment ) {
			echo "<script>document.title = '" . ucfirst( $environment ) . " | ' + document.title;</script>";
	}
}

// remove header bump from core css output
add_action( 'get_header', 'my_filter_head' );
function my_filter_head() {
	remove_action( 'wp_head', '_admin_bar_bump_cb' );
}

add_action( 'template_redirect', 'redirect_attachment_pages_to_404' );
function redirect_attachment_pages_to_404() {
	if ( is_attachment() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			include get_query_template( '404' );
			exit;
	}
}

// // initiate options page - consider deprecating this
// if( function_exists('acf_add_options_page') ) {
//     acf_add_options_page();
// }

// Pagination for paged posts, Page 1, Page 2, Page 3, with Next and Previous Links, No plugin
function html5wp_pagination() {
	global $wp_query;
	$big = 999999999;
	echo paginate_links(
		array(
			'base'    => str_replace( $big, '%#%', get_pagenum_link( $big ) ),
			'format'  => '?paged=%#%',
			'current' => max( 1, get_query_var( 'paged' ) ),
			'total'   => $wp_query->max_num_pages,
		)
	);
}

/**
 * Prefix a territory or region name with "The" where grammatically required.
 *
 * @param string $name Territory or region name.
 * @return string Name with "The " prepended if applicable.
 */
function wt_prefix_the( string $name ): string {
	$prefixed_names = array( 'Americas', 'Caribbean', 'Sahel', 'Gambia', 'Bahamas' );
	return in_array( $name, $prefixed_names, true ) ? 'the ' . $name : $name;
}

/**
 * Build the standard social-links array for the current post in context.
 *
 * Each entry maps a platform key to ['url' => string|false, 'icon' => string].
 * Pass the result to team-member--wide.php, team-member--grid.php, or
 * meta--fellows-single.php as $social_links.
 *
 * @return array<string, array{url: string|false, icon: string}>
 */
function wt_social_links(): array {
	return array(
		'email'     => array(
			'url'  => get_field( 'email' ),
			'icon' => 'square-email',
		),
		'facebook'  => array(
			'url'  => get_field( 'facebook' ),
			'icon' => 'square-facebook',
		),
		'instagram' => array(
			'url'  => get_field( 'instagram' ),
			'icon' => 'instagram',
		),
		'linkedin'  => array(
			'url'  => get_field( 'linkedin' ),
			'icon' => 'linkedin',
		),
		'tiktok'    => array(
			'url'  => get_field( 'tiktok' ),
			'icon' => 'tiktok',
		),
		'twitter'   => array(
			'url'  => get_field( 'twitter' ),
			'icon' => 'x-twitter',
		),
		'website'   => array(
			'url'  => get_field( 'website' ),
			'icon' => 'link',
		),
		'youtube'   => array(
			'url'  => get_field( 'youtube' ),
			'icon' => 'youtube',
		),
	);
}

/**
 * Return an inline SVG icon by name.
 *
 * All icons are self-contained, aria-hidden, and sized at 1em so they inherit
 * the surrounding font size — matching the previous Font Awesome behaviour.
 * fill="currentColor" on each path means icons inherit the CSS text colour.
 *
 * @param string $name Icon name (e.g. 'bars', 'envelope', 'instagram').
 * @return string Inline SVG markup, or empty string if the name is unknown.
 */
function wt_icon( string $name ): string {
	// phpcs:disable Generic.Files.LineLength
	$icons = array(
		'arrow-right-long' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path fill="currentColor" d="M502.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-128-128c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l370.7 0-73.4 73.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l128-128z"/></svg>',
		'bars'             => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path fill="currentColor" d="M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"/></svg>',
		'envelope'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path fill="currentColor" d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48H48zM0 176V384c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V176L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/></svg>',
		'instagram'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path fill="currentColor" d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>',
		'link'             => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path fill="currentColor" d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.8l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"/></svg>',
		'linkedin'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path fill="currentColor" d="M416 32H31.9C14.3 32 0 46.5 0 64.3v383.4C0 465.5 14.3 480 31.9 480H416c17.6 0 32-14.5 32-32.3V64.3c0-17.8-14.4-32.3-32-32.3zM135.4 416H69V202.2h66.5V416zm-33.2-243c-21.3 0-38.5-17.3-38.5-38.5S80.9 96 102.2 96c21.2 0 38.5 17.3 38.5 38.5 0 21.3-17.2 38.5-38.5 38.5zm282.1 243h-66.4V312c0-24.8-.5-56.7-34.5-56.7-34.6 0-39.9 27-39.9 54.9V416h-66.4V202.2h63.7v29.2h.9c8.9-16.8 30.6-34.5 62.9-34.5 67.2 0 79.7 44.3 79.7 101.9V416z"/></svg>',
		'square-email'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path fill="currentColor" d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zM218 271.7L64.2 172.4C66 156.4 79.5 144 96 144H352c16.5 0 30 12.4 31.8 28.4L230 271.7c-1.8 1.1-3.9 1.7-6 1.7s-4.2-.6-6-1.7zm13.5 32.1l130-89.2V352c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V214.6l130 89.2c7 4.8 15.1 7.2 23.2 7.2s16.2-2.4 23.2-7.2z"/></svg>',
		'square-facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path fill="currentColor" d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64h98.2V334.2H109.4V256h52.8V222.3c0-87.1 39.4-127.5 125-127.5c16.2 0 44.2 3.2 55.7 6.4V172c-6-.6-16.5-1-29.6-1c-42 0-58.2 15.9-58.2 57.2V256h83.6l-14.4 78.2H255V480H384c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64z"/></svg>',
		'tiktok'           => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path fill="currentColor" d="M448 209.9a210.1 210.1 0 0 1 -122.8-39.3V349.4A162.6 162.6 0 1 1 185 188.3V278.2a74.6 74.6 0 1 0 52.2 71.2V0l88 0a121.2 121.2 0 0 0 1.9 22.2h0A122.2 122.2 0 0 0 448 109.8V209.9z"/></svg>',
		'x-twitter'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path fill="currentColor" d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>',
		'xmark'            => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path fill="currentColor" d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>',
		'youtube'          => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path fill="currentColor" d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z"/></svg>',
	);
	// phpcs:enable Generic.Files.LineLength
	return $icons[ $name ] ?? '';
}
