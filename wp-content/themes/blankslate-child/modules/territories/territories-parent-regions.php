<?php
	$parent_regions = get_terms(
		array(
			'taxonomy'   => 'region',
			'parent'     => 0,
			'hide_empty' => false,
			'orderby'    => 'slug',
		)
	);

	if ( ! is_wp_error( $parent_regions ) && ! empty( $parent_regions ) ) : ?>
		<section class="top-level-regions metadata">
		<strong>Explore other global regions</strong>
		<ul>

		<?php
		foreach ( $parent_regions as $parent ) {
			if ( $parent->term_id === $current_parent_id ) {
				continue;
			}
				$parent_name = wt_prefix_the( $parent->name );
			echo '<li><a href="' . esc_url( get_term_link( $parent ) ) . '">' . esc_html( $parent_name ) . '</a></li>';
		}
		?>
		</ul>
		</section>

		<?php
	endif;