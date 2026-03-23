<?php
$includes_dirs = array( 'taxonomies', 'template', 'api', 'admin', 'integrations', 'cli' );
foreach ( $includes_dirs as $dir ) {
	foreach ( glob( __DIR__ . "/includes/{$dir}/*.php" ) as $file ) {
		require_once $file;
	}
}

// Module-level hook registrations (e.g. intake field sets).
foreach ( glob( __DIR__ . '/modules/*/intake.php' ) as $file ) {
	require_once $file;
}
