<?php // vars
$action_header = get_field('action_header');
$action_subheader = get_field('action_subheader');
$primary_action_link = get_field('primary_action_link');
$primary_action_text = get_field('primary_action_text');
$secondary_action_link = get_field('secondary_action_link');
$secondary_action_text = get_field('secondary_action_text');
?>

<div class="wt_action">
	<?php if ( $action_header ): ?>
	<div class="wt_action__header">
		<h1><?php echo $action_header; ?></h1>
		<?php if ( $action_subheader ): ?>
		<h2>
			<?php echo $action_subheader; ?>	
		</h2>
		<?php endif; ?>
	</div>
	<?php endif; ?>
	<?php if ( $primary_action_link ): ?>
	<a href="<?php echo $primary_action_link; ?>"
	   class="wt_action__primary">
	   	<?php echo $primary_action_text; ?>
	</a>
	<?php endif; ?>
	
	<?php if ( $secondary_action_link ): ?>
	<a href="<?php echo $secondary_action_link; ?>"
	   class="wt_action__secondary">
		<span><i class="fad fa-arrow-circle-right"></i></span>
		<span>Or <?php echo $secondary_action_text; ?></span>
	</a>
	<?php endif; ?> 
</div>