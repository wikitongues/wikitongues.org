<?php
	$category             = get_queried_object();
	$page_banner_override = array(
		'banner_header' => $category->name,
		'banner_copy'   => $category->description,
	);
	global $page_banner_override;

	get_header();
	require 'modules/editorial-content.php';

	$terms = get_terms(
		array(
			'taxonomy'   => 'fellow-category',
			'hide_empty' => false, // Change to true to only show categories with posts
		)
	);
	// Check if terms exist and are valid
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		?>
			<div class="fellow-gallery-nav">
				<div class="mobile-nav">
					<strong>Fellowship Categories</strong>
					<strong class="mobile"><a href="<?php echo home_url( '/revitalization/fellows', 'relative' ); ?>">Browse by cohort</a></strong>
				</div>
				<ul>
					<?php
					foreach ( $terms as $term ) {
						$class = ( $term->slug === $category->slug ) ? 'active' : '';
						echo '<li class="' . $class . '"><a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a></li>';
					}
					?>
				</ul>
				<strong><a href="<?php echo home_url( '/revitalization/fellows', 'relative' ); ?>">Browse by cohort</a></strong>
			</div>
		<?php
	}
	?>

<div class="archive-content">

	<?php
	$params = array(
		'title'          => '',
		'subtitle'       => '',
		'show_total'     => 'false',
		'post_type'      => 'fellows',
		'custom_class'   => 'full',
		'columns'        => 4,
		'posts_per_page' => '50',
		'orderby'        => 'year',
		'order'          => 'desc',
		'pagination'     => 'true',
		'meta_key'       => '',
		'meta_value'     => '',
		'selected_posts' => '',
		'display_blank'  => 'true',
		'exclude_self'   => 'false',
		'taxonomy'       => 'fellow-category',
		'term'           => $category->slug,
		'link_out'       => '',
	);
		echo create_gallery_instance( $params );
	?>
</div>

<?php
// Retrieve all terms from the custom taxonomy 'fellow-category'

require 'modules/newsletter.php';

get_footer(); ?>
