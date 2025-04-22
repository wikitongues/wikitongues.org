<?php
$term = get_queried_object();

global $page_banner_override;

if( have_rows('main_content', $term) ):
	while( have_rows('main_content', $term) ) : the_row();
		// Determine the current layout.
		$layout = get_row_layout();
		// Load a partial based on the layout.
		if( $layout == 'text_layout' ):
			$image = get_sub_field('image', $term);
			include( 'flexible-content/text-layout.php' );
		elseif( $layout == 'banner_layout' ):
			$page_banner 		= get_sub_field('banner', $term);

			global $page_banner_override;

			$page_banner['banner_header'] = !empty($page_banner_override['banner_header'])
				? $page_banner_override['banner_header']
				: $page_banner['banner_header'];

			$page_banner['banner_copy'] = !empty($page_banner_override['banner_copy'])
				? $page_banner_override['banner_copy']
				: $page_banner['banner_copy'];

			include( 'banners/banner--main.php' );
		elseif( $layout == 'video_layout' ):
			include( 'flexible-content/video-layout.php' );
		elseif( $layout == 'testimonials_layout' ):
			include( 'carousel--testimonial.php' );
		elseif( $layout == 'block_layout' ):
			include( 'flexible-content/block-layout.php' );
		elseif( $layout == 'link_group_layout' ):
			include( 'flexible-content/link-group-layout.php' );
		elseif( $layout == 'gallery_layout' ):
			include( 'flexible-content/gallery-layout.php' );
		endif;

	endwhile;
endif;