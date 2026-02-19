<?php
// variables
$banner_alert_text        = get_field( 'banner_alert_text', 'options' );
$banner_alert_button_link = get_field( 'banner_alert_button_link', 'options' );
$banner_alert_button_text = get_field( 'banner_alert_button_text', 'options' );
?>

<div class="wt_banner-alert__container">
	<!-- this element needs to be worked into the stylesheet. no in-line code -->
	<div id="inner" style="display:flex; ">
		<div class="wt_banner-alert__text">
			<?php echo $banner_alert_text; ?>
		</div>
		<!-- not sure this needs to be a button -->
		<button class="wt_banner-alert__button" >
			<a class="wt_banner-alert__button--link" 
				href="<?php echo $banner_alert_button_link; ?>" 
				target="_blank" >
				<?php echo $banner_alert_button_text; ?> 
				<i class="fa-regular fa-arrow-right-long"></i>
			</a>
		</button>
	</div>
	<!-- this should probably be a button -->
<!--     <button class="wt_banner-alert__button--close">
		<i class="fa-thin fa-xmark"></i>
	</button> -->
</div>