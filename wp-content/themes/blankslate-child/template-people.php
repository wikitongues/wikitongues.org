<?php /* Template name: People */

get_header();

// Text-only banner, matching what the Board, Advisors and Staff pages showed
// before this template replaced their three separate ones.
$text_banner = get_field( 'people_banner' );

require 'modules/banners/banner--text.php';

/*
 * The page composes itself from Main Content. Each people section is a
 * gallery_layout row — filter by people-type or pick people explicitly,
 * rendered wide or as a grid — so Staff can carry Staff, then careers, then
 * Interns and Volunteers, then careers again, in whatever order an editor
 * wants. A CTA (the board's "care about the cause?" invitation) is a
 * block_layout row in the same flow; it needs no template support.
 */
require 'modules/editorial-content.php';

require 'modules/newsletter.php';

get_footer();
