<?php
	$page_banner = get_sub_field('banner', $term);
	global $page_banner_override;

	$page_banner['banner_header'] = !empty($page_banner_override['banner_header'])
		? $page_banner_override['banner_header']
		: $page_banner['banner_header'];

	$page_banner['banner_copy'] = !empty($page_banner_override['banner_copy'])
		? $page_banner_override['banner_copy']
		: $page_banner['banner_copy'];

	include( __DIR__ . '/../banners/banner--main.php' );