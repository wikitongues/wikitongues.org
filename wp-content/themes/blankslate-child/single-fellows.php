<?php
$first_name                     = get_field( 'first_name' );
$last_name                      = get_field( 'last_name' );
$fellow_name                    = $first_name . ' ' . $last_name;
$page_banner                    = get_field( 'fellow_banner' );
$fellow_year                    = get_field( 'fellow_year' );
$fellow_language                = get_field( 'fellow_language' );
$fellow_location                = get_field( 'fellow_location' );
$fellow_territory               = get_field( 'fellow_territory' );
$fellow_language_preferred_name = get_field( 'fellow_language_preferred_name' );
$categories                     = get_the_terms( get_the_ID(), 'fellow-category' );
if ( $categories && ! is_wp_error( $categories ) ) {
	$category_links = array();
	foreach ( $categories as $cat ) {
		$cat_link = get_term_link( $cat, 'fellow-category' );
		if ( ! is_wp_error( $cat_link ) ) {
			$category_links[] = '<a href="' . esc_url( $cat_link ) . '">' . esc_html( $cat->name ) . '</a>';
		}
	}
	$category_names = implode( '', $category_links );
} else {
	$category_names = '';
}
log_data( $categories );
$social_links               = wt_social_links();
$revitalization_fellows_url = home_url( '/revitalization/fellows/?fellow_year=', 'relative' );
$revitalization_fellows_url = add_query_arg( 'fellow_year', $fellow_year, $revitalization_fellows_url );
$current_slug               = add_query_arg( array(), $wp->request );

// ====================
// Manage Fellows Page Titles
// ====================
if ( is_singular( 'fellows' ) ) {
	echo '<script>document.title = "Fellows | ' . $fellow_name . '";</script>';
}

get_header();

require 'modules/fellows/meta--fellows-single.php';

require 'modules/editorial-content.php';
$display_about           = get_field( 'display_about', 'option' );
$fellowship_about        = get_field( 'fellowship_about', 'option' );
$fellowship_about_header = get_field( 'fellowship_about_header', 'option' );
if ( $display_about && $fellowship_about ) {
	echo '<div class="main-content">';
	echo '<div class="fellowship-about">';
	echo '<strong class="wt_sectionHeader">' . esc_html( $fellowship_about_header ) . '</strong>';
	echo wp_kses_post( $fellowship_about );
	echo '</div>';
	echo '</div>';
}

?>
<div class="custom-gallery full">
	<strong class="wt_sectionHeader"><?php echo 'Other fellows from <a href="' . esc_url( $revitalization_fellows_url ) . '">' . $fellow_year; ?></a></strong>
	<p>The Wikitongues Fellowship is an accelerator program where activists can learn from a network of revitalization projects. <a href="https://wikitongues.donorsupport.co/-/XTRAFEBU" data-cta-location="fellow-gallery">Support a revitalization project.</a></p>
</div>
<?php
// Gallery
$params = wt_gallery_params(
	array(
		'post_type'      => 'fellows',
		'custom_class'   => 'full',
		'show_total'     => 'false',
		'columns'        => 4,
		'posts_per_page' => 4,
		'orderby'        => 'rand',
		'order'          => '',
		'pagination'     => 'false',
		'meta_key'       => 'fellow_year',
		'meta_value'     => $fellow_year,
		'exclude_self'   => 'true',
	)
);
echo create_gallery_instance( $params );

require 'modules/newsletter.php';

get_footer();