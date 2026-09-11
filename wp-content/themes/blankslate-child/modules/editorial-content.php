<?php
/*
 * Renders the editorial `main_content` flexible content.
 *
 * Defaults to the queried object (page, post or term). A caller that needs a
 * different source — an options page, say — sets $editorial_source before
 * including this file. It is unset afterwards so the next include starts from
 * the default again.
 */
$editorial_source = isset( $editorial_source ) ? $editorial_source : get_queried_object();

$templates = array(
	'text_layout'         => 'modules/flexible-content/text-layout.php',
	'banner_layout'       => 'modules/flexible-content/banner-layout.php',
	'video_layout'        => 'modules/flexible-content/video-layout.php',
	'testimonials_layout' => 'modules/carousel--testimonial.php',
	'block_layout'        => 'modules/flexible-content/block-layout.php',
	'link_group_layout'   => 'modules/flexible-content/link-group-layout.php',
	'gallery_layout'      => 'modules/flexible-content/gallery-layout.php',
);

if ( have_rows( 'main_content', $editorial_source ) ) :
	while ( have_rows( 'main_content', $editorial_source ) ) :
		the_row();
		$layout = get_row_layout();
		if ( empty( $templates[ $layout ] ) ) {
			continue; // unknown layout; skip safely
		}

		// Per-layout prep (only when needed)
		switch ( $layout ) {
			case 'text_layout':
				$image = get_sub_field( 'image' );
				break;
			default:
				// no-op
				break;
		}

		// Locate the template safely (supports child themes)
		$template_path = locate_template( $templates[ $layout ] );

		if ( $template_path ) {
			include $template_path;
		}
	endwhile;
endif;

unset( $editorial_source );
