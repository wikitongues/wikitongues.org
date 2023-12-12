<div class="banner_alert_container">
    <div id="inner" style="display:flex; ">
        <div class="wt_banner_alert"><?php the_field('banner_alert_text', 'options'); ?></div>
        <button id="banner_alert_button" >
            <a id="banner_alert_button_link" href="<?php the_field('banner_alert_button_link', 'options'); ?>" target="_blank" ><?php the_field('banner_alert_button_text', 'options'); ?></a>
            <i class="fa-regular fa-arrow-right-long"></i>
        </button>
    </div>
    <div id="banner_alert_close_button">
        <i class="fa-thin fa-xmark"></i>
    </div>
</div>