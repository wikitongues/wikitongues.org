<?php
// Register Custom Post Type for FAQs
add_action( 'init', 'create_faq_cpt' );
function create_faq_cpt() {
	$labels = array(
		'name'           => __( 'FAQs', 'faq' ),
		'singular_name'  => __( 'FAQ', 'faq' ),
		'menu_name'      => __( 'FAQs', 'faq' ),
		'name_admin_bar' => __( 'FAQ', 'faq' ),
		'add_new'        => __( 'Add New', 'faq' ),
		'add_new_item'   => __( 'Add New FAQ', 'faq' ),
		'new_item'       => __( 'New FAQ', 'faq' ),
		'edit_item'      => __( 'Edit FAQ', 'faq' ),
		'view_item'      => __( 'View FAQ', 'faq' ),
		'all_items'      => __( 'All FAQs', 'faq' ),
		'search_items'   => __( 'Search FAQs', 'faq' ),
	);

	$args = array(
		'labels'       => $labels,
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'faqs' ),
		'supports'     => array( 'title', 'editor', 'revisions' ),
		'menu_icon'    => 'dashicons-editor-help',
		'show_in_rest' => true,
	);

	register_post_type( 'faq', $args );
}

// Register Custom Taxonomy for FAQ Categories
add_action( 'init', 'create_faq_category_taxonomy' );
function create_faq_category_taxonomy() {
	$labels = array(
		'name'              => __( 'FAQ Categories', 'faq' ),
		'singular_name'     => __( 'FAQ Category', 'faq' ),
		'search_items'      => __( 'Search FAQ Categories', 'faq' ),
		'all_items'         => __( 'All FAQ Categories', 'faq' ),
		'parent_item'       => __( 'Parent FAQ Category', 'faq' ),
		'parent_item_colon' => __( 'Parent FAQ Category:', 'faq' ),
		'edit_item'         => __( 'Edit FAQ Category', 'faq' ),
		'update_item'       => __( 'Update FAQ Category', 'faq' ),
		'add_new_item'      => __( 'Add New FAQ Category', 'faq' ),
		'new_item_name'     => __( 'New FAQ Category Name', 'faq' ),
		'menu_name'         => __( 'FAQ Categories', 'faq' ),
	);

	$args = array(
		'labels'       => $labels,
		'hierarchical' => true,
		'public'       => true,
		'rewrite'      => array( 'slug' => 'faq-category' ),
		'show_in_rest' => true,
	);

	register_taxonomy( 'faq_category', array( 'faq' ), $args );
}

// Customize FAQ Columns
add_filter( 'manage_faq_posts_columns', 'set_custom_faq_columns' );
function set_custom_faq_columns( $columns ) {
	unset( $columns['date'] );
	$columns['faq_category'] = __( 'Category', 'textdomain' );
	$columns['date']         = __( 'Date', 'textdomain' );
	return $columns;
}

add_action( 'manage_faq_posts_custom_column', 'custom_faq_column', 10, 2 );
function custom_faq_column( $column, $post_id ) {
	switch ( $column ) {
		case 'faq_category':
				$terms = get_the_term_list( $post_id, 'faq_category', '', ', ', '' );
			if ( is_string( $terms ) ) {
					echo wp_kses_post( $terms );
			} else {
					echo '—';
			}
			break;
	}
}

// Remove Unnecessary Metaboxes
add_action( 'add_meta_boxes', 'remove_faq_metaboxes', 99 );
function remove_faq_metaboxes() {
	remove_meta_box( 'slugdiv', 'faq', 'normal' );
	remove_meta_box( 'postcustom', 'faq', 'normal' );
}

// Shortcode to Display FAQs
// All FAQs: [faqs]
// Specific Category: [faqs category="category-slug"]
// Specific FAQs: [faqs ids="123,456"]

add_shortcode( 'faqs', 'faq_shortcode' );
function faq_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'category' => '',
			'ids'      => '',
		),
		$atts,
		'faqs'
	);

	$args = array(
		'post_type'      => 'faq',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	);

	if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'faq_category',
					'field'    => 'slug',
					'terms'    => explode( ',', $atts['category'] ),
				),
			);
	}

	if ( ! empty( $atts['ids'] ) ) {
			$args['post__in'] = explode( ',', $atts['ids'] );
	}

	$faqs = new WP_Query( $args );

	ob_start();

	if ( $faqs->have_posts() ) {
			echo '<div class="faqs">';
		while ( $faqs->have_posts() ) {
				$faqs->the_post();
				echo '<div class="faq">';
				echo '<h3 class="faq-question">' . esc_html( get_the_title() ) . '</h3>';
				echo '<div class="faq-answer">' . apply_filters( 'the_content', get_the_content() ) . '</div>';
				echo '</div>';
		}
			echo '</div>';
	}

	wp_reset_postdata();

	return ob_get_clean();
}
