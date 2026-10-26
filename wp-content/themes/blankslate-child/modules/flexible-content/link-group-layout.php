<?php
if ( have_rows( 'link_group' ) ) :
	// A text row, so it shares the reading column and heading style.
	echo '<section class="wt_text-row wt_prose">';
	echo '<h3>Links</h3>';
	echo '<ul class="link-group">';
	while ( have_rows( 'link_group' ) ) :
		the_row();
		$link_url  = get_sub_field( 'link_url' );
		$link_text = get_sub_field( 'link_text' );
			echo '<li>';
			echo '<a href="' . esc_url( $link_url ) . '">';
			echo esc_html( $link_text );
			echo '</a>';
			echo '</li>';
		endwhile;
	echo '</ul>';
	echo '</section>';
endif;
