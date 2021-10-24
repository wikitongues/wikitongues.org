<?php
	$profile_picture = get_field('profile_picture');
	$leadership_title = get_field('leadership_title');
	preg_match('#\((.*?)\)#', $profile_picture, $profile_image); 
?>

<div class="wt_team__member">
	<?php if ( $profile_picture ): ?>
	<div class="wt_team__member--image"
		 style="background-image:url(<?php echo $profile_image[1]; ?>);"
		 role="img"
		 aria-label="Headshot of <?php the_title(); ?>"></div>
	<?php else: ?>
	<div class="wt_team__member--noimage"></div>
	<?php endif; ?>
	<div class="wt_team__member--name">
		<strong>
			<?php the_title(); ?>
		</strong>
		<p>
			<?php echo $leadership_title; ?>
		</p>
	</div>	
</div>