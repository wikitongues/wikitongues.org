<?php
	$territory_query = new WP_Query([
		'post_type'      => 'territories',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'slug',
		'order'          => 'ASC',
		'tax_query'      => [
			[
				'taxonomy' => 'region',
				'field'    => 'term_id',
				'terms'    => $current_region->term_id,
			]
		]
	]);
	$current_region_name = in_array($current_region->name, ['Americas', 'Caribbean', 'Sahel', 'Gambia']) ? 'The ' . $current_region->name : $current_region->name;
?>
	<h1><?php echo $territory ?></h1>
	<?php	if ( $territory_query->have_posts() ) : ?>
	<section class="related-territories metadata">
		<strong>Other territories in <a href="<?php echo esc_url(get_term_link($current_region)) ;?>"><?php echo esc_html( $current_region_name ) ?></a></strong>
		<ul>
			<?php while ( $territory_query->have_posts() ) : $territory_query->the_post();
				$territory_name = $territory_query->post->post_title;
				$territory_name_clean = in_array($territory_name, ['Americas', 'Caribbean', 'Sahel', 'Gambia']) ? 'The ' . $territory_name : $territory_name;
				if ( $territory_query->post->ID == $territory_id ) continue;
					echo '<li><a href="'.esc_url(get_permalink()).'">'.esc_html( $territory_name_clean ).'</a></li>';
			endwhile; ?>
		</ul>
	</section>
	<?php
	wp_reset_postdata();
	endif;