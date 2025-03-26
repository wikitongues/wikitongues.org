<?php
$language = get_the_ID();
$fellows = array();
$args = array(
    'post_type' => 'fellows',
    'meta_query' => array(
        array(
            'key' => 'fellow_language',
            'value' => $language,
            'compare' => 'LIKE'
        )
    )
);
$fellow_query = new WP_Query($args);

if ($fellow_query->have_posts()) :
    while ($fellow_query->have_posts()) : $fellow_query->the_post();
			$fellows[] = get_the_ID();
    endwhile;
endif;

wp_reset_postdata();

if ( $fellows ):
	?>

	<div id="wt_single-languages__fellows" class="wt_single-languages__contents">
		<?php
			// Gallery
			$title = 'Revitalization Projects';
			$title_name = $standard_name;
			if (substr($standard_name, -7) !== 'anguage') {
				$title_name .= ' language';
			}
			$title = $title_name . ' Revitalization Projects';
			$params = [
				'title' => $title,
				'subtitle' => 'The Wikitongues Fellowship is an accelerator program where activists can learn from a network of revitalization projects.',
				'post_type' => 'fellows',
				'custom_class' => 'display',
				'columns' => 1,
				'posts_per_page' => 1,
				'orderby' => 'date',
				'order' => 'asc',
				'pagination' => 'true',
				'meta_key' => 'fellow_language',
				'meta_value' => $language,
				'selected_posts' => '',
				'display_blank' => 'false',
				'exclude_self' => 'true',
				'taxonomy' => '',
				'term' => '',
			];
			echo create_gallery_instance($params);
		?>

	</div>
<?php endif; ?>