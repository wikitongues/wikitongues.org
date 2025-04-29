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
?>

	<h1><?php echo $territory ?></h1>
	<?php	if ( $territory_query->have_posts() ) : ?>
	<section class="related-territories metadata">
		<ul class="territories-list">
			<?php while ( $territory_query->have_posts() ) : $territory_query->the_post();
                $url = esc_url(get_permalink());
                $title = esc_html(get_the_title());
                $territory_id = get_the_ID();
                $languages = get_field('languages', $territory_id);
                $language_ids = [];
                if ($languages) {
                    $language_ids = implode(',', wp_list_pluck($languages, 'ID'));
                }
                $params = [
                    'title' => '',
                    'subtitle' => '',
                    'post_type' => 'languages',
                    'custom_class' => '',
                    'columns' => 1,
                    'posts_per_page' => 1,
                    'orderby' => '',
                    'order' => '',
                    'pagination' => 'false',
                    'meta_key' => '',
                    'meta_value' => '',
                    'selected_posts' => $language_ids,
                    'display_blank' => 'true',
                    'exclude_self' => 'false',
                    'taxonomy' => '',
                    'term' => ''
                ];
                echo '<li class="territory"><a class="territory-name" href="'.$url.'">'.$title.'</a>';
                echo create_gallery_instance($params);
                echo '</li>';
			endwhile; ?>
		</ul>
	</section>
	<?php
	wp_reset_postdata();
	endif;
echo '<div class="wt_meta--languages-single">';
include( 'modules/territories/territories-sibling-regions.php' );
include( 'modules/territories/territories-parent-regions.php' );
echo '</div>';
get_footer();
?>
