<?php
$term = get_queried_object();
global $page_banner_override;

$templates = array(
	'text_layout'         => 'modules/flexible-content/text-layout.php',
	'banner_layout'       => 'modules/flexible-content/banner-layout.php',
	'video_layout'        => 'modules/flexible-content/video-layout.php',
	'testimonials_layout' => 'modules/carousel--testimonial.php',
	'block_layout'        => 'modules/flexible-content/block-layout.php',
	'link_group_layout'   => 'modules/flexible-content/link-group-layout.php',
	'gallery_layout'      => 'modules/flexible-content/gallery-layout.php',
);

if ( have_rows( 'main_content', $term ) ) :
	while ( have_rows( 'main_content', $term ) ) :
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
