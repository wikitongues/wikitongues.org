<?php

// variables
$banner_alert_status = get_field( 'banner_alert_status', 'options' );
$banner_alert_text = get_field( 'banner_alert_text', 'options' );
$banner_alert_button_link = get_field( 'banner_alert_button_link', 'options' );
$banner_alert_button_text = get_field( 'banner_alert_button_text', 'options' ); 
?>

<?php if ( $banner_alert_status === 'active' ): ?>
<div class="banner_alert_container">
    <div id="inner" style="display:flex; ">
        <div class="wt_banner_alert">
            <?php echo $banner_alert_text; ?>
        </div>
        <button id="banner_alert_button" >
            <a id="banner_alert_button_link" 
               href="<?php echo $banner_alert_button_link; ?>" 
               target="_blank" ><?php echo $banner_alert_button_text; ?></a>
            <i class="fa-regular fa-arrow-right-long"></i>
        </button>
    </div>
    <div id="banner_alert_close_button">
        <i class="fa-thin fa-xmark"></i>
    </div>
</div>
<?php endif; ?>