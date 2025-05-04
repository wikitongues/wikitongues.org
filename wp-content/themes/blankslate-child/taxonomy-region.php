<?php
get_header();

// Grab the current region term
$current_region = get_queried_object();
$current_parent_id = $current_region->parent ?: $current_region->term_id;
$territory = $current_region->name;

// include( 'modules/territories/territories-active-region.php' );
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
    echo '<div class="wt_meta--territories-single">';
    echo '<h1>' . $territory . '</h1>';
    $current_region->parent != 0 ? include( 'modules/territories/territories-child-regions.php' ) : '' ;
    include( 'modules/territories/territories-sibling-regions.php' );
    include( 'modules/territories/territories-parent-regions.php' );

    echo '</div>';

    if ( $territory_query->have_posts() ) :
        echo '<div class="container">';
        $params = [
            'title' => 'Territories in ' . $territory,
            'subtitle' => '',
            'show_total' => 'true',
            'post_type' => 'territories',
            'custom_class' => '',
            'columns' => 3,
            'posts_per_page' => 9,
            'orderby' => 'rand',
            'order' => '',
            'pagination' => 'true',
            'meta_key' => '',
            'meta_value' => '',
            'selected_posts' => '',
            'display_blank' => 'true',
            'exclude_self' => 'false',
            'taxonomy' => 'region',
            'term' => $current_region->slug
        ];
        echo create_gallery_instance($params);

	wp_reset_postdata();
    echo '</div>';
	endif;
get_footer();
?>
