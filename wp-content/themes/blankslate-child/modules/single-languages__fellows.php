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

// print_r($fellow_query);
// print_r($language);
// print_r($fellows);
if ( $fellows ):
	?>

	<div id="wt_single-languages__fellows" class="wt_single-languages__contents">
		<?php
			$custom_title = 'Fellows';
			$custom_post_type = 'fellows';
			$custom_class = '';
			$custom_columns = 3;
			$custom_posts_per_page = 6;
			$custom_orderby = 'date';
			$custom_order = 'asc';
			$custom_pagination = 'true';
			$custom_meta_key = 'fellow_language';
			$custom_meta_value = $language;
			$custom_selected_posts = '';
			echo do_shortcode('[custom_gallery title="'.$custom_title.'" custom_class="'.$custom_class.'" post_type="'.$custom_post_type.'" columns="'.$custom_columns.'" posts_per_page="'.$custom_posts_per_page.'" orderby="'.$custom_orderby.'" order="'.$custom_order.'" pagination="'.$custom_pagination.'" meta_key="'.$custom_meta_key.'" meta_value="'.$custom_meta_value.'" selected_posts="'.$custom_selected_posts.'"]');
		?>
	</div>
<?php endif; ?>