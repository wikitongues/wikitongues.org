<main class="wt_wrapper">
	<section class="wt_donate__intro">
		<h1 class="wt_donate__header">
			<?php echo $donate_page_header; ?>	
		</h1>
<!-- 		<h2 class="wt_donate__subheader">
			<?php// echo $donate_subheader; ?>
		</h2> -->
	</section>

	<section class="wt_donate__progress">
		<a href="#XCUDAJSK" style="display: none"></a>
	</section>

	<section class="wt_donate__donate">
		<div class="wt_donate__form">
			<?php echo $donate_form_embed; ?>
		</div>

		<div class="wt_donate__address">
			<strong>To donate offline, please mail us<br/>a check payable to 'Wikitongues, Inc.':</strong><br/><br/>
			<span><?php echo $donate_address; ?></span>
		</div>
	</section>
</main>

<section class="wt_donate__casestudies">
	<main class="wt_wrapper">
		<div class="wt_donate__casestudies">
		<h1>Case Studies</h1>
		<h2><?php echo $donate_content; ?></h2>
		<ul>
	</main>

	<?php
		if( $case_studies ){

		echo '<ul>';

		foreach( $case_studies as $post ){
			setup_postdata( $post );

			include( locate_template('modules/donate-grantee-thumbnail.php') );
		}

		echo '</ul>';

		wp_reset_postdata();
	} ?>
</section>