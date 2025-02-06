<?php /* Template Name: FAQ */

add_action( 'wp_enqueue_scripts', 'enqueue_faq_navigation_script' );
function enqueue_faq_navigation_script() {
	if ( is_page_template( 'faq-page.php' ) ) {
		wp_enqueue_script( 'faq-navigation', get_stylesheet_directory_uri() . '/js/faq-navigation.js', array( 'jquery' ), null, true );
	}
}

get_header();

// banner
$page_banner = get_field('faq_banner');

include( 'modules/banner--main.php' );

echo '<div class="faq-container">';

if( have_rows('faq_section') ):

	$nav_items = array();
	$section_count = 0;

	while( have_rows('faq_section') ): the_row();
			if( get_row_layout() == 'faq_layout' ):
					$section_header = get_sub_field('section_header');

					// Generate the same unique ID as before
					$section_id = 'faq-section-' . $section_count;

					// Add the section to the navigation items array
					$nav_items[] = array(
							'id' => $section_id,
							'title' => $section_header,
					);

					$section_count++;

			endif;
	endwhile;

	// Output the navigation menu
	if( !empty( $nav_items ) ) {
			echo '<nav class="faq-navigation">';
			echo '<ul>';
			foreach( $nav_items as $item ) {
					echo '<li><a href="#' . esc_attr( $item['id'] ) . '">' . esc_html( $item['title'] ) . '</a></li>';
			}
			$email = 'hello@wikitongues.org';
			echo '</ul>';
			echo '<p>Have another question? <a href="mailto:' . $email . '">Write us at ' . $email . '!</a></p>';
			echo '</nav>';
	}

	// Reset the rows to loop again
	reset_rows();

	$section_count = 0;
	echo '<div class="faq-content">';
	while( have_rows('faq_section') ): the_row();

		if( get_row_layout() == 'faq_layout' ):
			$section_header = get_sub_field('section_header');
			$faq_entries = get_sub_field('faq_entries');
			$section_id = 'faq-section-' . $section_count;

			if( $section_header ) {
				echo '<h4 id="' . esc_attr( $section_id ) . '">' . esc_html( $section_header ) . '</h4>';
			}

			if( $faq_entries ) {
				echo '<ul class="faqs">';
				foreach( $faq_entries as $post ) {
					setup_postdata( $post );
					// $faq_text = get_sub_field('short_answer');
					?>
					<li class="faq-entry">
						<h3 class="faq-question"><?php the_title(); ?></h3>
						<?php the_content(); ?>
					</li>
					<?php
				}

				wp_reset_postdata();

				echo '</ul>'; // Close 'faqs' ul

			} else {
					echo '<p>No FAQs selected in this section.</p>';
			}
			$section_count++;
		endif;

	endwhile;
	echo '</div>'; // Close 'faq-content' div

else:

	echo '<p>No FAQ sections found.</p>';

endif;

echo '</div>';

include( 'modules/newsletter.php' );

get_footer();
?>
