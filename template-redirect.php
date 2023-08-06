<?php 

/* Template name: Redirect */

$redirect_to_page = get_field('redirect_to_page');
$redirect_to_post = get_field('redirect_to_post');
$custom_redirect = get_field('custom_redirect');

if ( $redirect_to_page ) {
	header( 'Location: ' . $redirect_to_page );
}

if ( $redirect_to_post ) {
	header( 'Location: ' . $redirect_to_post );
}

if ( $custom_redirect ) {
	header('Location: ' . $custom_redirect);
}