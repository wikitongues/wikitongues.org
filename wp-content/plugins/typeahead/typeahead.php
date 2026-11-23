<?php
/*
Plugin Name: Wikitongues React Typeahead
Description: Integrate Wikitongus React Typeahead with WordPress
Version: 1.0
Author: Frederico Andrade
*/

require_once __DIR__ . '/includes/class-wt-typeahead-index.php';
WT_Typeahead_Index::init();

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/includes/class-wt-typeahead-cli.php';
	WP_CLI::add_command( 'typeahead', 'WT_Typeahead_CLI' );
}

function react_typeahead_enqueue_scripts() {
	$plugin_dir = plugin_dir_url( __FILE__ );

	// Get the hashed filenames for CSS and JS files
	$manifest = json_decode( file_get_contents( plugin_dir_path( __FILE__ ) . 'build/asset-manifest.json' ), true );
	$main_css = $manifest['files']['main.css'];
	$main_js  = $manifest['files']['main.js'];

	// Enqueue the CSS file
	wp_enqueue_style(
		'react-typeahead-style',
		$plugin_dir . 'build/' . $main_css
	);

	// Enqueue the JavaScript file
	wp_enqueue_script(
		'react-typeahead-script',
		$plugin_dir . 'build/' . $main_js,
		array(), // Dependencies, if any
		null, // Version
		true // Load in footer
	);
}

add_action( 'wp_enqueue_scripts', 'react_typeahead_enqueue_scripts' );

function react_typeahead_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id'           => 'react_typeahead_root_' . uniqid(),
			'custom_class' => '',
		),
		$atts,
		'react_typeahead'
	);

	$unique_id    = esc_attr( $atts['id'] );
	$custom_class = esc_attr( $atts['custom_class'] );

	// Search runs in the browser against a static index (plan 7.9). If the
	// index hasn't been built on this environment yet, schedule a build.
	$index_url = WT_Typeahead_Index::url();
	if ( '' === $index_url ) {
		WT_Typeahead_Index::schedule();
	}
	$samples = get_option( WT_Typeahead_Index::SAMPLES_OPTION, array() );

	$settings = array(
		'customClass'        => $custom_class,
		'uniqueId'           => $unique_id,
		'indexUrl'           => $index_url,
		'placeholderSamples' => is_array( $samples ) ? array_values( $samples ) : array(),
	);

	$settings_var_name = 'wpTypeaheadSettings_' . preg_replace( '/[^a-zA-Z0-9_]/', '_', $unique_id );

	wp_localize_script( 'react-typeahead-script', $settings_var_name, $settings );
	return '<div id="' . $unique_id . '" class="react-typeahead-container ' . $custom_class . '"></div>';
}

add_shortcode( 'react_typeahead', 'react_typeahead_shortcode' );

function hide_shortcode_container() {
	?>
	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			let instances = document.querySelectorAll('.react-typeahead-container');
			instances.forEach(function(instance) {
				instance.style.display = 'none';
			});
		});
	</script>
	<?php
}

add_action( 'wp_head', 'hide_shortcode_container' );
