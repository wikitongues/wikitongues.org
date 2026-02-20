<?php
	$territory_query = new WP_Query(
		array(
			'post_type'      => 'territories',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'slug',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'region',
					'field'    => 'term_id',
					'terms'    => $current_region->term_id,
				),
			),
		)
	);
	?>
	<?php	if ( $territory_query->have_posts() ) : ?>
	<section class="related-territories metadata">
		<strong>Territories in <a href="<?php echo esc_url( get_term_link( $current_region ) ); ?>"><?php echo esc_html( wt_prefix_the( $current_region->name ) ); ?></a></strong>
		<ul>
			<?php
			while ( $territory_query->have_posts() ) :
				$territory_query->the_post();
				$territory_name = wt_prefix_the( get_the_title() );
				// if ( $territory_query->post->ID == $territory_id ) continue;
				echo '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( $territory_name ) . '</a></li>';
			endwhile;
			?>
		</ul>
	</section>
		<?php
		wp_reset_postdata();
	endif;