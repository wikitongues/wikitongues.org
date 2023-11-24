<main class="main-content">
<?php 
	// if progress bar exists
	if ( $progress_bar ) { 

		// display progress bar
		echo $progress_bar;
	}
	
	// if donation link exists
	if ( $donation_link ) {

		// define wide button variables
		$wide_button_link = $donation_link;
		$wide_button_text = 'Donate';

		// display donation button
		include( 'button--wide.php' );
	}

	// content loop
	if ( have_posts() ) {

		while ( have_posts() ){

			the_post();

			the_content();
		}
	} 
?>
</main>