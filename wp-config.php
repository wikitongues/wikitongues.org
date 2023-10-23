<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wikitongues' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '~5yJ&STh)-mdV5E:$Q8,q+_H;yg]&:tu5Bz[J)19Z (dV}i>.3%1g3)DhX(|K `?' );
define( 'SECURE_AUTH_KEY',  '.m<$PM:)ZbTZS:-).M#,?%cd>++wTv+ye|VF;cg|[)O_Y t=eI;|1Men<3Skg=d-' );
define( 'LOGGED_IN_KEY',    '7y)olF#o2Xr&f#[rXMhmsB@N6,Y0^X%B0s!/mBH+9)gw{#6Uh^zzLTnt>yv^NxT@' );
define( 'NONCE_KEY',        'Ig}~lxIS<zSSZPCW=oKg dx+uC[NRJy3T3HCzq$N<J5ggWc~ws&W-7na{`B1bR<7' );
define( 'AUTH_SALT',        '}02%VornA`Bqa|S=t0 5_UiqUXaJ(FfE 0/p:CCabHrSSqe1st^GF,XPc>R4+TvZ' );
define( 'SECURE_AUTH_SALT', '6-IA;!8uq1~W+N|N7@Yk4:(h`QXTGVos/gM:be<jeUd%mRlgA@sC[ Znt{fiI=S7' );
define( 'LOGGED_IN_SALT',   '}K!=)Hbg_[/;WIaRqY#6Hl c:MD1Hwc>+*~>;I$`bpU)r<8ASO>LlUgq8[x0aRm%' );
define( 'NONCE_SALT',       'jht%YE|/Wdz/DouAU;v;q)3|G;`]i_A!%;Gjmn(|:u%>VT/X3(&5&M1+s/:(D:wl' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
