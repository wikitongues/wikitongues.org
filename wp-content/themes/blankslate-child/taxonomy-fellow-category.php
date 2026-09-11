<?php
	$category = get_queried_object();

	get_header();

	/*
	 * The category banner comes from the term's own `revitalization_fellows_banner`
	 * group rather than from an editorial `banner_layout` row. That frees the
	 * editorial slot for a block at the foot of the page, giving this template the
	 * same banner -> gallery -> block flow as template-revitalization-fellows.php.
	 *
	 * Pass the term explicitly: on a taxonomy archive ACF's implicit ID resolution
	 * lands on the first fellow in the loop, not on the queried term.
	 */
	$page_banner = get_field( 'revitalization_fellows_banner', $category );
if ( ! is_array( $page_banner ) ) {
	$page_banner = array();
}
if ( ! is_array( $page_banner['banner_image'] ?? null ) ) {
	$page_banner['banner_image'] = array();
}

	/*
	 * Term name and description take precedence over the stored banner copy,
	 * preserving what banner-layout.php did through $page_banner_override. Terms
	 * leave both banner fields blank, so without this every category banner would
	 * render without a heading.
	 */
	$page_banner['banner_header'] = $category->name ? $category->name : ( $page_banner['banner_header'] ?? '' );
	$page_banner['banner_copy']   = $category->description ? $category->description : ( $page_banner['banner_copy'] ?? '' );

	require 'modules/banners/banner--main.php';

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
	$params = wt_gallery_params(
		array(
			'post_type'      => 'fellows',
			'custom_class'   => 'full',
			'show_total'     => 'false',
			'columns'        => 4,
			'posts_per_page' => 50,
			'orderby'        => 'year',
			'order'          => 'desc',
			'display_blank'  => 'true',
			'taxonomy'       => 'fellow-category',
			'term'           => $category->slug,
		)
	);
		echo create_gallery_instance( $params );
	?>
</div>

<?php
// Editorial content sits below the gallery so a term can carry a closing block
// (toolkit, blog) — the banner above no longer occupies this slot.
require 'modules/editorial-content.php';
require 'modules/newsletter.php';

get_footer(); ?>
