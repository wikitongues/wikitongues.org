<?php /* Template name: Team */

// header
get_header();

// banner
$page_header = get_field('page_header', 'options');
$page_subhead = get_field('page_subhead', 'options');

include( locate_template('modules/banner-short.php') );

// governing board and staff
$team = get_field('board_and_staff', 'options');

if ( $team ) {
	// governing board title
	$team_title = 'Board and Staff';
	$team_wrapper = 'board-and-staff'; // unique ID for wrapper

	include( locate_template('modules/team.php') );

}

// advisory board
$team = get_field('advisory_board', 'options');

if ( $team ) {
	// advisory board title
	$team_title = 'Advisors';
	$team_wrapper = 'advisors';

	include( locate_template('modules/team.php') );
}

// volunteers and interns
$team = get_field('interns_and_volunteers', 'options');

if ( $team ) {
	// interns and volunteers title
	$team_title = 'Interns and Volunteers';
	$team_wrapper = 'interns-and-volunteers';

	include( locate_template('modules/team.php') );
}

// footer
get_footer();