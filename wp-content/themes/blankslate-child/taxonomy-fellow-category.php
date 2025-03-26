<?php
	$category = get_queried_object();
 	get_header();
	include( 'modules/editorial-content.php' );
?>

<div class="archive-content">
  <?php
    $params = [
			'title' => $category->name . ' Fellows',
			'subtitle' => '',
			'post_type' => 'fellows',
			'custom_class' => 'full',
			'columns' => 4,
			'posts_per_page' => '50',
			'orderby' => 'year',
			'order' => 'desc',
			'pagination' => 'true',
			'meta_key' => '',
			'meta_value' => '',
			'selected_posts' => '',
			'display_blank' => 'true',
			'exclude_self' => 'false',
			'taxonomy' => 'fellow-category',
			'term' => $category->slug,
		];
		echo create_gallery_instance($params);
  ?>
</div>

<?php
// Retrieve all terms from the custom taxonomy 'fellow-category'
$terms = get_terms( array(
    'taxonomy'   => 'fellow-category',
    'hide_empty' => false, // Change to true to only show categories with posts
) );

// Check if terms exist and are valid
if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
    echo '<ul class="fellow-categories-nav">';
    foreach ( $terms as $term ) {
        // Output each term as a list item with a link to its archive page
        echo '<li><a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a></li>';
    }
    echo '</ul>';
}

include( 'modules/newsletter.php' );

get_footer(); ?>
