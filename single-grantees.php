<?php

// header
get_header();

// splash banner
$banner_image = get_field('banner_image');
$banner_header = get_field('banner_header');
$banner_copy = get_field('banner_copy');

include( locate_template('modules/banner.php') );

// main content variables
$grantee_first_name = get_field('first_name');
$grantee_last_name = get_field('last_name');
$grantee_language = get_field('grantee_language');
$grantee_location = get_field('grantee_location');
$grantee_pitch = get_field('grantee_pitch');
$grantee_project = get_field('grantee_project');
$grantee_bio = get_field('grantee_bio');
$grantee_collaborators = get_field('grantee_collaborators');
?>

<main class="wt_grantee__main">
	<h1 class="wt_grantee__main--title">
		<?php the_title(); ?>
	</h1>
	<h2 class="wt_grantee__main--subheader">
		<?php the_field('grantee_pitch'); ?>
	</h2>
	<article class="wt_grantee__main--content">
	<?php 
		echo $grantee_project .
			 '<p><strong>' .
			 $grantee_first_name . 
			 '\'s Background</strong></p>' .
			 $grantee_bio;

		if ( $grantee_collaborators ) {
			echo '<p><strong>' .
				 $grantee_first_name .
				 '\'s Collaborators</strong></p>';

			foreach ( $grantee_collaborators as $post ) {
				setup_postdata( $post );

				include( locate_template('modules/collaborator-thumbnail.php') );
			} wp_reset_postdata();
		} 

		if ( have_rows('grantee_links') ) {
			echo '<p><strong>Learn More</strong></p>'.
				 '<ul class="wt_grantee__main--links">';

			while ( have_rows('grantee_links') ){
				the_row();

				$link_type = get_sub_field('link_type');
				$link_name = get_sub_field('link_name');
				$link_url = get_sub_field('link_url');

				include( locate_template('modules/grantee_link.php') );
			}

			echo '</main>';
		}
		?>
	</article>
</main> 

<section class="wt_grantee__navigation">
	<h1 class="wt_grantee__navigation--title">Other Grantees</h1>
	<h2 class="wt_grantee__navigation--subheader"> Lorem, ipsum dolor sit amet consectetur, adipisicing elit. Quis eius pariatur, praesentium, dicta veritatis illum.</h2>
	<?php // testimonials
		$current_grantee = get_the_ID();
		$grantees = new WP_Query( 
			array(
				'post_type'=>'grantees',
				'post__not_in' => array( $current_grantee ),
				'meta_key' => 'grantee_role',
				'meta_value' => 'Project leader',
				'posts_per_page' => 15
			) 
		);

		if( $grantees->have_posts() ){		
			while( $grantees->have_posts() ){
				$grantees->the_post();

				include( locate_template('modules/grantee-thumbnail.php') );
			}
		}
		wp_reset_postdata();
	?>
</section>

<?php get_footer(); ?>