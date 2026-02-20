<?php

	$sibling_regions = get_terms(
		array(
			'taxonomy'   => 'region',
			'parent'     => $current_parent_id,
			'hide_empty' => false,
			'orderby'    => 'slug',
		)
	);
	$parent_name     = wt_prefix_the( get_term( $current_parent_id )->name );
	$parent_link     = get_term_link( $current_parent_id );
	if ( ! is_wp_error( $sibling_regions ) && ! empty( $sibling_regions ) ) : ?>
		<section class="sibling-regions metadata">
		<strong>Other regions in <a href="<?php echo esc_url( $parent_link ); ?>"><?php echo esc_html( $parent_name ); ?></a></strong>
		<ul>
		<?php
		foreach ( $sibling_regions as $sibling ) {
			if ( $sibling->term_id === $current_region->term_id ) {
				continue;
			}
				$sibling_name = wt_prefix_the( $sibling->name );
				echo '<li><a href="' . esc_url( get_term_link( $sibling ) ) . '">' . esc_html( $sibling_name ) . '</a></li>';
		}
		?>
		</ul>
		</section>
		<?php
	endif;
