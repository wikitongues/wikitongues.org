<div id="<?php echo $team_wrapper; ?>" class="wt_team">	
	<div class="wt_team__title">
		<h1><?php echo $team_title; ?></h1>
	</div>

	<?php 
	foreach ( $team as $post ) {
		setup_postdata( $post );

		include( locate_template('modules/team-member.php') );
	} 

	wp_reset_postdata();
	?>
</div>