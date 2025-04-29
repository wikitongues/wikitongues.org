<?php
	$territory_query = new WP_Query([
		'post_type'      => 'territories',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
		'tax_query'      => [
			[
				'taxonomy' => 'region',
				'field'    => 'term_id',
				'terms'    => $current_region->term_id,
			]
		]
	]);
?>
	<h1><?php echo $territory ?></h1>
	<?php	if ( $territory_query->have_posts() ) : ?>
	<section class="related-territories metadata">
		<strong>Other territories in <?php echo esc_html( $current_region->name ) ?></strong>
		<ul>
			<?php while ( $territory_query->have_posts() ) : $territory_query->the_post();
				printf(
						'<li><a href="%s">%s</a></li>',
						esc_url( get_permalink() ),
						esc_html( get_the_title() )
				);
			endwhile; ?>
		</ul>
	</section>
	<?php
	wp_reset_postdata();
	endif;