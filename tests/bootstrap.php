<?php
require_once __DIR__ . '/../vendor/autoload.php';

WP_Mock::bootstrap();

require_once __DIR__ . '/unit/FakeQuery.php';

require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/import-captions.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/acf-helpers.php';
require_once __DIR__ . '/../wp-content/themes/blankslate-child/includes/search-filter.php';
require_once __DIR__ . '/../wp-content/plugins/wt-gallery/includes/render_gallery_items.php';
