<?php
// // define session variable for displaying alert banner
// session_start();

// if (isset($_SESSION['last_visit_time'])) {
//     $currentTime = time();
//     $lastVisitTime = $_SESSION['last_visit_time'];
//     $timeDifference = $currentTime - $lastVisitTime;
//     // unable to verify that days counter is working
//     // $daysSinceLastVisit = floor( $timeDifference / (60 * 60 * 24) );
//     // set $daysSinceLastVisit to seconds:
//     $daysSinceLastVisit = $timeDifference;

//  $_SESSION['days_since_last_visit'] = $daysSinceLastVisit;

//     // for testing:
//     // echo "It's been $daysSinceLastVisit days since your last visit.";
// }

// $_SESSION['last_visit_time'] = time();
?>

<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
		<?php require 'modules/page--head.php'; ?>

		<?php $banner_alert_status = get_field( 'banner_alert_status', 'options' ); ?>

		<body <?php body_class(); ?>
			<?php
			if ( $banner_alert_status === 'active' ) :
				?>
				data-alert="true"<?php endif; ?>><!-- is an additional content wrapper necessary for drop shadow gradient? -->
			<!-- Google Tag Manager (noscript) -->
			<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-M6VGJW4" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

			<?php wp_body_open(); ?>

			<!-- alert/message banner -->
			<?php
			// load alert banner if user hasn't visited the site in 1 day
			// day counter var isn't working, counting in seconds ~~~~DU Feb '24
			//if ( $_SESSION['days_since_last_visit']>86400 ) {
				// include( 'modules/banners/banner--alert.php' );
			//}

			if ( $banner_alert_status === 'active' ) {
				include 'modules/banners/banner--alert.php';
			}

			?>

			<header class="wt_header 
			<?php
			if ( is_front_page() ) :
				?>
				transparent-background<?php endif; ?>" role="banner">
				<section class="wt_header__primary">
					<div class="wt_header__logo">
						<a href="<?php echo home_url(); ?>">
							<img class="wt_header__logo--light 
							<?php
							if ( is_front_page() ) :
								?>
								transparent-background<?php endif; ?>" src="<?php the_field( 'header_logo_light', 'options' ); ?>" alt="Wikitongues logo: light color scheme">
							<img class="wt_header__logo--dark 
							<?php
							if ( is_front_page() ) :
								?>
								transparent-background<?php endif; ?>" src="<?php the_field( 'header_logo_dark', 'options' ); ?>" alt="Wikitongues logo: dark color scheme">
						</a>
					</div>
					<?php echo do_shortcode( '[react_typeahead id="typeahead_nav" custom_class="nav-style" data_source="airtable"]' ); ?>
					<?php

					// global var? define somewher else?
					$template_slug = get_page_template_slug();

					if ( is_front_page() ) {
						wp_nav_menu(
							array(
								'theme_location'  => 'main-menu',
								'container'       => 'nav',
								'container_class' => 'wt_header__nav transparent-background',
							)
						);
					} else {
						wp_nav_menu(
							array(
								'theme_location'  => 'main-menu',
								'container'       => 'nav',
								'container_class' => 'wt_header__nav',
							)
						);
					}

					// mobile menu
					wp_nav_menu(
						array(
							'theme_location'  => 'mobile-menu',
							'container'       => 'nav',
							'container_class' => 'wt_header__nav--mobile',
						)
					);
					?>

					<aside class="wt_header__mobile-buttons">
						<button id="mobile-nav-open">
							<i class="fa-solid fa-bars"></i>
						</button>
						<button id="mobile-nav-close">
							<i class="fa-solid fa-xmark"></i>
						</button>
					</aside>
				</section>

				<?php if ( ! is_front_page() && ! is_page_template( 'template-giving-campaign.php' ) ) : ?>
				<section class="wt_header__secondary">
					<?php
					if (
							strpos( $template_slug, 'revitalization' ) !== false ||
							is_singular( array( 'documents', 'fellows' ) ) ||
							is_tax( 'fellow-category' )
						) {
						wp_nav_menu(
							array(
								'theme_location'  => 'revitalization-menu',
								'container'       => 'nav',
								'container_class' => 'wt_header__nav--secondary',
							)
						);
					} elseif (
							strpos( $template_slug, 'archive' ) !== false ||
							is_post_type_archive( array( 'languages', 'fellows', 'territories' ) ) ||
							is_singular( array( 'languages', 'videos', 'territories' ) ) ||
							is_tax( 'region' ) ||
							is_search()
						) {
						wp_nav_menu(
							array(
								'theme_location'  => 'archive-menu',
								'container'       => 'nav',
								'container_class' => 'wt_header__nav--secondary',
							)
						); // if single language or single video, display the language
					} elseif ( strpos( $template_slug, 'about' ) !== false ) {
						wp_nav_menu(
							array(
								'theme_location'  => 'about-menu',
								'container'       => 'nav',
								'container_class' => 'wt_header__nav--secondary',
							)
						);
					}
					?>
				</section>
				<?php endif; ?>
			</header>