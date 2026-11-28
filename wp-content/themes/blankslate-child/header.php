<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
		<?php require 'modules/page--head.php'; ?>

		<body <?php body_class(); ?>><!-- is an additional content wrapper necessary for drop shadow gradient? -->
			<!-- Google Tag Manager (noscript) -->
			<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-M6VGJW4" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

			<?php wp_body_open(); ?>

			<div class="wt_masthead">
			<?php
			$site_banner = wt_site_banner();
			if ( $site_banner ) {
				include 'modules/banners/banner--site.php';
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
					<?php echo do_shortcode( '[react_typeahead id="typeahead_nav" custom_class="nav-style"]' ); ?>
					<?php
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
							<?php echo wt_icon( 'bars' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>
						<button id="mobile-nav-close">
							<?php echo wt_icon( 'xmark' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>
					</aside>
				</section>

				<?php $secondary_menu_location = wt_secondary_menu_location(); ?>
				<?php if ( $secondary_menu_location ) : ?>
				<section class="wt_header__secondary">
					<?php
					wp_nav_menu(
						array(
							'theme_location'  => $secondary_menu_location,
							'container'       => 'nav',
							'container_class' => 'wt_header__nav--secondary',
						)
					);
					?>
				</section>
				<?php endif; ?>
			</header>
			</div>