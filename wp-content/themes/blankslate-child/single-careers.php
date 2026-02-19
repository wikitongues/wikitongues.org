<?php

add_action( 'wp_enqueue_scripts', 'enqueue_mobile_accordion_script' );
function enqueue_mobile_accordion_script() {
	if ( is_singular( 'careers' ) ) {
		wp_enqueue_script( 'mobile-accordion-helper', get_stylesheet_directory_uri() . '/js/mobile-accordion-helper.js', array( 'jquery' ), null, true );
	}
}

get_header();

// Fetch the global fields from the Careers list page
$list_page_id = get_page_by_path( 'careers' )->ID;
$headers      = get_field( 'headers', $list_page_id );

// Helper function to render a section if a value exists
function render_section( $field_name, $headers, $page_id = null ) {
	$field_value = get_field( $field_name, $page_id );
	$title_value = $headers[ $field_name ];
	if ( $field_value ) {
		echo "<section class='" . esc_attr( $field_name ) . "'>";
		echo "<h4 class='mobile-accordion-header'>" . esc_attr( $title_value ) . '</h4>';
		echo "<div class='mobile-accordion-content'>" . wpautop( wp_kses_post( $field_value ) ) . '</div>';
		echo '</section>';
	}
}

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		?>
		<div class="career-post">
			<a href="<?php echo home_url( '/careers', 'relative' ); ?>">Back to Careers</a>
			<h1 class="career-title"><?php the_title(); ?></h1>
			<section>
				<?php echo '<h4>' . $headers['posted_date'] . '</h4>'; ?>
				<time datetime="<?php echo get_the_date( 'c' ); ?>" itemprop="datePublished"><?php echo get_the_date(); ?></time>
			</section>
			<?php
			render_section( 'location', $headers );
			render_section( 'deadline', $headers );
			render_section( 'why_wikitongues', $headers, $list_page_id );
			render_section( 'team_description', $headers );
			render_section( 'role_description', $headers );
			render_section( 'requirements', $headers );
			render_section( 'compensation', $headers );
			render_section( 'about_wikitongues', $headers, $list_page_id );
			render_section( 'dei', $headers, $list_page_id );
			?>

			<section class="career-application">
				<?php echo '<h4>' . $headers['application'] . '</h4>'; ?>
				<a href="<?php echo esc_url( get_field( 'application' ) ); ?>" target="_blank">
					Apply Here
				</a>
			</section>
		</div>
		<?php
	endwhile;
endif;

require 'modules/newsletter.php';
get_footer();
