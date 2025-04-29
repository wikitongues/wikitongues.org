<?php
	$parent_regions = get_terms([
		'taxonomy'   => 'region',
		'parent'     => 0,
		'hide_empty' => false,
		'orderby'    => 'name',
	]);

	if ( ! is_wp_error( $parent_regions ) && ! empty( $parent_regions ) ) : ?>
		<section class="top-level-regions metadata">
		<strong>Explore other global regions</strong>
		<ul>

		<?php foreach ( $parent_regions as $parent ) {
				// Skip the parent group if this region belongs to it
				if ( $parent->term_id == $current_parent_id ) continue;

				printf(
						'<li><a href="%s">%s</a></li>',
						esc_url( get_term_link( $parent ) ),
						esc_html( $parent->name )
				);
		}
		?>
		</ul>
		</section>

	<?php	endif;