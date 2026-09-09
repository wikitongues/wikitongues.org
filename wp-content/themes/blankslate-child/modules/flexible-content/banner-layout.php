<?php
	// Editorial banner row. The term name/description override that used to live
	// here moved into taxonomy-fellow-category.php, which now renders the category
	// banner from the term's own field group rather than from this layout.
	$page_banner = get_sub_field( 'banner' );

	require __DIR__ . '/../banners/banner--main.php';
