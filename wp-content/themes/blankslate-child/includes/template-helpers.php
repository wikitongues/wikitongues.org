<?php
// Determine the environment
function get_environment() {
	if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
			return 'localhost';
	} elseif (strpos($_SERVER['HTTP_HOST'], 'staging') !== false) {
			return 'staging';
	} else {
			return '';
	}
}

function get_current_datetime() {
	return date('Y-m-d H:i:s');
}

function log_data($data, $method = 'console') { // methods: console, dom
	if ($method === 'console') {
		echo '<script>';
		echo 'console.log(' . json_encode($data) . ')';
		echo '</script>';
	} elseif ($method === 'dom') {
		echo '<pre>';
		print_r($data);
		echo '</pre>';
	}
}

add_action('wp_head', 'modify_page_title');
function modify_page_title() {
	$environment = get_environment();
	if ($environment) {
			echo "<script>document.title = '" . ucfirst($environment) . " | ' + document.title;</script>";
	}
}

// remove header bump from core css output
add_action('get_header', 'my_filter_head');
function my_filter_head() {
   remove_action('wp_head', '_admin_bar_bump_cb');
}

add_action( 'template_redirect', 'redirect_attachment_pages_to_404' );
function redirect_attachment_pages_to_404() {
	if ( is_attachment() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			include( get_query_template( '404' ) );
			exit;
	}
}

// // initiate options page - consider deprecating this
// if( function_exists('acf_add_options_page') ) {
//     acf_add_options_page();
// }

// Pagination for paged posts, Page 1, Page 2, Page 3, with Next and Previous Links, No plugin
function html5wp_pagination()
{
    global $wp_query;
    $big = 999999999;
    echo paginate_links(array(
        'base' => str_replace($big, '%#%', get_pagenum_link($big)),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $wp_query->max_num_pages
    ));
}