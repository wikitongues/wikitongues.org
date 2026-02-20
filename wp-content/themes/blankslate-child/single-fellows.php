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
	$category_names = implode( ', ', $category_links );
} else {
	$category_names = '';
}
log_data( $categories );
// $category_names = implode(', ', array_map('esc_html', wp_list_pluck($categories, 'name')));
$social_links               = array(
	'email'     => array(
		'url'  => get_field( 'email' ),
		'icon' => 'fa-brands fa-square-email',
	),
	'facebook'  => array(
		'url'  => get_field( 'facebook' ),
		'icon' => 'fa-brands fa-square-facebook',
	),
	'instagram' => array(
		'url'  => get_field( 'instagram' ),
		'icon' => 'fa-brands fa-instagram',
	),
	'linkedin'  => array(
		'url'  => get_field( 'linkedin' ),
		'icon' => 'fa-brands fa-linkedin',
	),
	'tiktok'    => array(
		'url'  => get_field( 'tiktok' ),
		'icon' => 'fa-brands fa-tiktok',
	),
	'twitter'   => array(
		'url'  => get_field( 'twitter' ),
		'icon' => 'fa-brands fa-x-twitter',
	),
	'website'   => array(
		'url'  => get_field( 'website' ),
		'icon' => 'fa-solid fa-link',
	),
	'youtube'   => array(
		'url'  => get_field( 'youtube' ),
		'icon' => 'fa-brands fa-youtube',
	),
);
$revitalization_fellows_url = home_url( '/revitalization/fellows/?fellow_year=', 'relative' );
$revitalization_fellows_url = add_query_arg( 'fellow_year', $fellow_year, $revitalization_fellows_url );

$current_slug     = add_query_arg( array(), $wp->request );
$fundraising_link = home_url( "{$current_slug}/?element=XESPGTCJ&form=FUNQMUDJDGQ", 'relative' );

// ====================
// Manage Fellows Page Titles
// ====================
if ( is_singular( 'fellows' ) ) {
	echo '<script>document.title = "Fellows | ' . $fellow_name . '";</script>';
}

get_header();

require 'modules/fellows/meta--fellows-single.php';

$fellow_bio = get_field( 'fellow_bio' );
echo '<div class="main-content">';
echo wpautop( wp_kses_post( $fellow_bio ) );
echo wpautop( wp_kses_post( get_the_content() ) );
echo '</div>';
require 'modules/editorial-content.php';

?>
<div class="custom-gallery full">
	<strong class="wt_sectionHeader"><?php echo 'Other fellows from <a href="' . esc_url( $revitalization_fellows_url ) . '">' . $fellow_year; ?></a></strong>
	<p>The Wikitongues Fellowship is an accelerator program where activists can learn from a network of revitalization projects. <a href="<?php echo $fundraising_link; ?>">Support a revitalization project.</a></p>
</div>
<?php
// Gallery
$params = array(
	'title'          => '',
	'subtitle'       => '',
	'show_total'     => 'false',
	'post_type'      => 'fellows',
	'custom_class'   => 'full',
	'columns'        => 4,
	'posts_per_page' => 4,
	'orderby'        => 'rand',
	'order'          => '',
	'pagination'     => 'false',
	'meta_key'       => 'fellow_year',
	'meta_value'     => $fellow_year,
	'selected_posts' => '',
	'display_blank'  => 'false',
	'exclude_self'   => 'true',
	'taxonomy'       => '',
	'term'           => '',
);
echo create_gallery_instance( $params );

require 'modules/newsletter.php';

get_footer();