<?php

// header
get_header();

// banner
include( locate_template('modules/language-splash.php') );

// video post objects and cta content
$thumbnail = get_field('speakers_recorded');
$thumbnail_cta_text = 'Add a video'; // could be made an option field
$thumbnail_cta_link = 'https://airtable.com/shrYPADYkHF9umAhm';

// video thumbnails header
echo '<div class="wt_thumbnails__title">' .
	 '<h1>' . $standard_name . ' Videos</h1>' .
	 '<h2>These videos were recoded by volunteers from around the world.</h2>' .
	 '</div>'; // need to make this modular

// if videos exist
if ( $thumbnail ) {
	// show the video thumbnail template for each video
	include( locate_template('modules/thumbnails.php') );

} else {
	// show the "no videos" message
	include( locate_template('modules/no-videos.php') );

}

// lexicon post objects and cta content
$lexicon_source = get_field('lexicon_source');
$lexicon_target = get_field('lexicon_target');
$thumbnail_cta_text = 'Add a lexicon'; // could be made an option field
$thumbnail_cta_link = 'https://airtable.com/shrGm31ZXQxoZIA9D';

// lexicon thumbnails header
echo '<div class="wt_thumbnails__title">' .
	 '<h1>' . $standard_name . ' Lexicons</h1>' .
	 '<h2>Dictionaries and phrase books archived by Wikitongues or made available on the Living Dictionaries app.</h2>' .
	 '</div>'; // need to make this modular

// if lexicon source+target exist
if ( $lexicon_source && $lexicon_target ) {
	// merge arrays
	$thumbnail = array_merge($lexicon_source,$lexicon_target);

// if only lexicon source exists
} elseif ( $lexicon_source && !$lexicon_target ) {
	// define $thumbnail accordingly
	$thumbnail = $lexicon_source;

// if only lexicon target exists
} elseif ( $lexicon_target && !$lexicon_source ) {
	// define $thumbnail accordingly
	$thumbnail = $lexicon_target;

// if lexicon source+target are empty
} else {
	// define $thumbnail as source
	$thumbnail = $lexicon_source;

}

// if lexicons exist
if ( $thumbnail ) {
	// show the lexicon thumbnail template for each lexicon
	include( locate_template('modules/thumbnails.php') );

// if no lexicons exist
} else {
	// show the "no lexicons" message
	include( locate_template('modules/no-lexicons.php') );

}

// external resources
$thumbnail = get_field('external_resources');
$thumbnail_cta_text = 'Suggest a resource';
$thumbnail_cta_link = 'https://airtable.com/shrLJ3Yk5YeoCizJx';

// video thumbnails header
echo '<div class="wt_thumbnails__title">' .
	 '<h1>Other Resources</h1>' .
	 '<h2>These external resources are not maintained or archived by Wikitongues, but they may be useful to ' . $standard_name . ' research or revitalization projects.</h2>' .
	 '</div>'; // need to make this modular

if ( $thumbnail ) {
	include( locate_template('modules/thumbnails.php') );

} else {
	// show the "no resources" message
	include( locate_template('modules/no-resources.php') );
}

// footer
get_footer();