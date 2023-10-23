<?php /* Template name: Toolkit */

// header
get_header();

// homepage banner
$banner_image = get_field('banner_image');
$banner_header = get_field('banner_header');
$banner_copy = get_field('banner_copy');

include( locate_template('modules/banner.php') );

// toolkit downloads
$thumbnail = get_field('toolkit_versions'); // new variable

// toolkits header
echo '<div class="wt_thumbnails__title">' .
	 '<h1>Download Options</h1>' .
	 '</div>'; // need to make this modular

// if videos exist
if ( $thumbnail ) {
	// show the video thumbnail template for each video
	include( locate_template('modules/thumbnails.php') );

} else {
	// show the "no videos" message
	include( locate_template('modules/error.php') );

}

// footer
get_footer();