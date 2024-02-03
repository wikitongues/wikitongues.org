<?php
// variables
$banner_alert_status = get_field( 'banner_alert_status', 'options' );
$banner_alert_text = get_field( 'banner_alert_text', 'options' );
$banner_alert_button_link = get_field( 'banner_alert_button_link', 'options' );
$banner_alert_button_text = get_field( 'banner_alert_button_text', 'options' ); 
?>

<?php if ( $banner_alert_status === 'active' ): ?>
<div class="banner_alert_container">
    <!-- this element needs to be worked into the stylesheet. no in-line code -->
    <div id="inner" style="display:flex; ">
        <div class="wt_banner-alert__text">
            <?php echo $banner_alert_text; ?>
        </div>
        <button class="wt_banner-alert__button" >
            <a class="wt_banner-alert__button--link" 
               href="<?php echo $banner_alert_button_link; ?>" 
               target="_blank" ><?php echo $banner_alert_button_text; ?></a>
            <i class="fa-regular fa-arrow-right-long"></i>
        </button>
    </div>
    <div class="wt_banner-alert__button--close">
        <i class="fa-thin fa-xmark"></i>
    </div>
</div>
<?php endif; ?>