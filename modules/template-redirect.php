<?php /* Template name: Redirect */

// redirect link variables
$redirect_to_page = get_field('redirect_to_page');
$redirect_to_post = get_field('redirect_to_post');
$custom_redirect = get_field('custom_redirect');

// conditional redirect instructions; page redirect takes priority
if ( $redirect_to_page && !$redirect_to_post && !$custom_redirect ) {
	header('Location: ' . $redirect_to_page );

} elseif ( $redirect_to_post && !$redirect_to_page && !$custom_redirect ) {
	header( 'Location:' . $redirect_to_post->guid );

} elseif ( $custom_redirect && !$redirect_to_page && !$redirect_to_post ) {
	header( 'Location:' . $custom_redirect );

} else {
	header( 'Location:' . $redirect_to_page );

}