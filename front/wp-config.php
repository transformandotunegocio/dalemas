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
define( 'DB_NAME', 'front' );

/** MySQL database username */
define( 'DB_USER', 'front' );

/** MySQL database password */
define( 'DB_PASSWORD', 'front@021' );

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
define( 'AUTH_KEY',         'UXc[U&Gd LZ;w{BpHIXgu77EVh}@}fk..w?au;M_Q:(-2 uACmp%F:kKf!}JpYt>' );
define( 'SECURE_AUTH_KEY',  'J.<A9f<U*`:;9|QQkXc0!{tIpEu5HZj.,g5o_fq6mCwgT(nA/p^ybK?)Kj]B%F|-' );
define( 'LOGGED_IN_KEY',    'DwJ8owoSWi#6{j|T</jB&=N1?fj7s.~VilUziwboQe}F(vT3:8V!uDE+FIMr<?yI' );
define( 'NONCE_KEY',        '|^hRpXpHgcGUT|k!z h22ZZ== vmhldY.$<K2YsBNyjsh6T5/gi [,5Lu}eA!<DZ' );
define( 'AUTH_SALT',        '3+j=/apG.&SWOu#+vM;l4u@+jc%$@_JGobjn-=3{GH/9?/6#0f#WMV<_C}ls<?i)' );
define( 'SECURE_AUTH_SALT', 'Rq}DWgUU0-%vT&wQXBTV#G =JXM:X64ZJm8%`.xq~Fe4wokLmk,<eW$*SRvq)?eb' );
define( 'LOGGED_IN_SALT',   'o*!K$j%w&M.cQM%&x<4@/3j9Y~c/jA?++d&?Djl:,W>8!Lpc4s@M=Q]RsI&S!}AG' );
define( 'NONCE_SALT',       '0fTODzFS*qo(R&H*1MfB0([}7Yp:9#n!UG6_|YW$$L H@bEY L`f=c(iG<P;|n@8' );

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


define('WP_ALLOW_MULTISITE', true);

define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', false );
define( 'DOMAIN_CURRENT_SITE', 'front.dalemas.store' );
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
