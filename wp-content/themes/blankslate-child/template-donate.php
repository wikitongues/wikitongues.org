<?php
/* Template name: Donate */

// Built from editorial `main_content` blocks, like the homepage. The field key
// lets an unsaved page load its launch blocks (includes/template/donate-content.php).
get_header();

$editorial_field = WT_MAIN_CONTENT_KEY;
require __DIR__ . '/modules/editorial-content.php';

require __DIR__ . '/modules/newsletter.php';

get_footer();
