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

// Field selector. template-donate.php passes the field key, which also loads a
// page's launch blocks before it has saved any (see donate-content.php).
$editorial_field = isset( $editorial_field ) ? $editorial_field : 'main_content';

// Only the first text row opens with a drop cap (text-layout.php reads this).
$text_row_index = 0;

$templates = array(
	'text_layout'          => 'modules/flexible-content/text-layout.php',
	'banner_layout'        => 'modules/flexible-content/banner-layout.php',
	'video_layout'         => 'modules/flexible-content/video-layout.php',
	'testimonials_layout'  => 'modules/carousel--testimonial.php',
	'block_layout'         => 'modules/flexible-content/block-layout.php',
	'link_group_layout'    => 'modules/flexible-content/link-group-layout.php',
	'gallery_layout'       => 'modules/flexible-content/gallery-layout.php',
	// Donation blocks; see includes/template/donate-content.php.
	'donate_hero_layout'   => 'modules/flexible-content/donate-hero-layout.php',
	'stats_layout'         => 'modules/flexible-content/stats-layout.php',
	'numbered_list_layout' => 'modules/flexible-content/numbered-list-layout.php',
	'stories_layout'       => 'modules/flexible-content/stories-layout.php',
	'give_now_layout'      => 'modules/flexible-content/give-now-layout.php',
	'faq_accordion_layout' => 'modules/flexible-content/faq-accordion-layout.php',
	'closing_cta_layout'   => 'modules/flexible-content/closing-cta-layout.php',
);

if ( have_rows( $editorial_field, $editorial_source ) ) :
	while ( have_rows( $editorial_field, $editorial_source ) ) :
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

		if ( 'text_layout' === $layout ) {
			++$text_row_index;
		}
	endwhile;
endif;

unset( $editorial_source, $editorial_field, $text_row_index );
