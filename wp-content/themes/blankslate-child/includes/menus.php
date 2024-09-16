<?php
// Register navigation menus
add_action('init', 'wt_archive_menu');
add_action('init', 'wt_revitalization_menu');
add_action('init', 'wt_about_menu');
add_action('init', 'wt_footer_menu');
add_action('init', 'wt_mobile_menu');

function wt_archive_menu() {
    register_nav_menu('archive-menu', __('Archive Menu'));
}

function wt_revitalization_menu() {
    register_nav_menu('revitalization-menu', __('Revitalization Menu'));
}

function wt_about_menu() {
    register_nav_menu('about-menu', __('About Menu'));
}

function wt_footer_menu() {
    register_nav_menu('footer-menu', __('Footer Menu'));
}

function wt_mobile_menu() {
    register_nav_menu('mobile-menu', __('Mobile Menu'));
}