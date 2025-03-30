<?php
	$category = get_queried_object();
 	get_header();
	include( 'modules/editorial-content.php' );

	$terms = get_terms( array(
    'taxonomy'   => 'fellow-category',
    'hide_empty' => false, // Change to true to only show categories with posts
	) );
	// Check if terms exist and are valid
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		?>
			<div class="test">
				<div class="nav">
					<strong>Fellowship Categories</strong>
					<strong><a href="<?php echo home_url('/revitalization/fellows', 'relative')?>">Browse by cohort</a></strong>
				</div>
				<div class="nav">
					<select onchange="if (this.value) window.location.href=this.value;">
						<?php
						foreach ( $terms as $term ) {
							$selected = $term->slug === $category->slug ? 'selected' : '';
							echo '<option value="' . esc_url( get_term_link( $term ) ) . '" ' . $selected . '>' . esc_html( $term->name ) . '</option>';
						}?>
					</select>
					<ul class="fellow-categories-nav">
					<?php
					foreach ( $terms as $term ) {
						if( $term->slug !== $category->slug ) {
							echo '<li><a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a></li>';
						}
					}
					?>
					</ul>
				</div>
			</div>
		<?php
	}
?>

<div class="archive-content">

  <?php
    $params = [
			// 'title' => $category->name . ' Fellows',
			// 'subtitle' => $category->description,
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

include( 'modules/newsletter.php' );

get_footer(); ?>
