<?php

	$sibling_regions = get_terms([
		'taxonomy'   => 'region',
		'parent'     => $current_parent_id,
		'hide_empty' => false,
		'orderby'    => 'name',
	]);


	if ( ! is_wp_error( $sibling_regions ) && ! empty( $sibling_regions ) ) : ?>
		<section class="sibling-regions metadata">
		<strong>Neighboring Regions</strong>
		<ul>
		<?php foreach ( $sibling_regions as $sibling ) {
				if ( $sibling->term_id === $current_region->term_id ) {
						// echo '<li class="current-region"><strong>' . esc_html( $sibling->name ) . '</strong></li>';
				} else {
						printf(
								'<li><a href="%s">%s</a></li>',
								esc_url( get_term_link( $sibling ) ),
								esc_html( $sibling->name )
						);
				}
		} ?>
		</ul>
		</section>
		<?php
	endif;
