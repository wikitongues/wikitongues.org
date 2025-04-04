<head>
	<!-- Google Analytics Script -->
	<?php echo get_field('analytics_header_script', 'options'); ?>
	<!-- Language and browser view meta tags -->
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!-- favicon -->
	<?php $favicon = get_field('favicon', 'options'); ?>
	<?php if ( $favicon ): ?>
		<link href="<?php echo $favicon['url']; ?>" rel="shortcut icon">
	<?php else: ?>
		<link href="<?php echo home_url('/wp-content/themes/blankslate-child/img/icons/favicon.ico', 'relative'); ?>" rel="shortcut icon">
	<?php endif; ?>

	<!-- Custom metadata variables -->
	<?php
		$global              = $wp;
		$seo_title           = get_field('seo_title');
		$seo_description     = get_field('seo_description');
		$seo_keywords        = get_field('seo_keywords');
		$sharing_title       = get_field('sharing_title');
		$sharing_description = get_field('sharing_description');
		$sharing_image       = get_field('sharing_image');
		$default_description = 'Wikitongues safeguards endangered languages, expands access to linguistic resources, and directly supports language revitalization projects on every continent.';
		?>

	<!-- SEO title -->
	<title><?php

		if ( $seo_title ) {
			// use title from SEO CRM, if available
			echo $seo_title;

		} else if ( is_archive() ) {
			// grab archived post type
			$archive_post_type = get_queried_object();
			if(!isset($archive_post_type->taxonomy)){
				// title page based on archived post type
				echo 'Wikitongues | ' . $archive_post_type->labels->name;
			} else {
				echo 'Wikitongues | ' . $archive_post_type->name;
			};

		} else {
			// use the default page title
			echo 'Wikitongues' . ' | ' . get_the_title();

		} ?>
	</title>

	<!-- SEO description -->
	<meta name="description"
		 content="<?php
		 	if ( $seo_description ) {
		 		echo $seo_description;
		 	} else {
		 		echo $default_description; } ?>">

	<!-- SEO keywords -->
	<meta name="keywords"
		  content="<?php
		  	if ( $seo_keywords ) {
		  		echo $seo_keywords;
		  	} else {
		  		echo 'language, linguistics, language revitalization, endangered languages, culture, diversity, travel'; } ?>">

	<!-- SEO robots instructions -->
	<meta name="robots" content="index,follow">

	<!-- Open graph title for social media sharing -->
	<meta property="og:title"
		  content="<?php
		  	if ( $sharing_title ) {
		  		echo $sharing_title;
		  	} else {
		  		echo 'Wikitongues' . ' | ' . get_the_title();
		  	} ?>">

	<!-- Open graph description -->
	<meta property="og:description"
		  content="<?php
		  	if ( $sharing_description ) {
		  		echo $sharing_description;
		  	} else {
		  		echo $default_description; } ?>">

	<!-- Open graph image -->
	<meta property="og:image"
		  content="<?php
		  	if ( $sharing_image ) {
		  		echo $sharing_image['url'];
		  	} // what should the default be here? ?>">

	<!-- Open graph url -->
	<meta property="og:url" content="<?php echo home_url( $wp->request, 'relative'); ?>">

	<!-- Twitter card format -->
	<meta name="twitter:card" content="summary_large_image">

	<!-- For the CMS: Reconciling the guidelines for the image is simple: follow Facebook’s recommendation of a minimum dimension of 1200×630 pixels and an aspect ratio of 1.91:1, but adhere to Twitter’s file size requirement of less than 1MB. Validate: https://developers.facebook.com/tools/debug/sharing/ and https://cards-dev.twitter.com/validator -->

	<!-- Font Awesome connection for UI/UX icons and small graphic elements -->
	<script src="https://kit.fontawesome.com/5c48172421.js" crossorigin="anonymous"></script>

	<!-- Fundraise Up connection -->
	<script>(function(w,d,s,n,a){if(!w[n]){var l='call,catch,on,once,set,then,track'
	.split(','),i,o=function(n){return'function'==typeof n?o.l.push([arguments])&&o
	:function(){return o.l.push([n,arguments])&&o}},t=d.getElementsByTagName(s)[0],
	j=d.createElement(s);j.async=!0;j.src='https://cdn.fundraiseup.com/widget/'+a;
	t.parentNode.insertBefore(j,t);o.s=Date.now();o.v=4;o.h=w.location.href;o.l=[];
	for(i=0;i<7;i++)o[l[i]]=o(l[i]);w[n]=o}
	})(window,document,'script','FundraiseUp','ABDBDJGE');</script>
	<!-- WP head tag -->
	<?php wp_head(); ?>
</head>