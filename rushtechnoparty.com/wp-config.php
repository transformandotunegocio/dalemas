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
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dalemasd_rush' );

/** MySQL database username */
define( 'DB_USER', 'dalemasd_rush_user' );

/** MySQL database password */
define( 'DB_PASSWORD', 'GJ3j-KLXQxm9' );

/** MySQL hostname */
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
define( 'AUTH_KEY',         'j51dm4@d{kV@6MS  ~r6> BkFRG)d_-yw:XQ-1Kn-zm!-#hcrCpV ^5mgQk%3K#6' );
define( 'SECURE_AUTH_KEY',  '3]X(]+/,sjJrB=_SP7B4sqdA*qkD2rxP_-~G3y()1#<jcEEU_^9+|1yX>HDPSmGY' );
define( 'LOGGED_IN_KEY',    'o3Du,TM5vJI2QK,glWC#&[Hb4^.p~230Y@AL,]-Pl`nHFzLT%H7RaN9G!,(PvoL}' );
define( 'NONCE_KEY',        'K4Bj2|r^IQ0)}O!xb}=2jY-Tooowu@ya=(.@X?6gMrkgn4}!lgR%k;!*LR={8FR]' );
define( 'AUTH_SALT',        'Ul?LY`9]h52!cHcMFkR-{?KO>BA@zQU<{Ve|/ChWP)U eH2I04F6/x)8G~Hhm38f' );
define( 'SECURE_AUTH_SALT', '/o0c#g|6{@<)-PQY.:XmbX3t7?::qB~(w7w!GLh7br`Q8+vV!N%H h:-VC+,QW i' );
define( 'LOGGED_IN_SALT',   '*,t-bIAzDuIoXfM@!*Az]qov+~KSI^JTi!rt[{d^Q$r)nsY8OWXE[TaA]vYKuA03' );
define( 'NONCE_SALT',       'An396@Ku#r@8JV<;Y0V*hN>tqM21g^N]fixy=-J>pECx;hFsCoK5yMFM yq@k7?X' );

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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
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
