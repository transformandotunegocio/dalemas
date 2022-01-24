<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
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
define( 'DB_NAME', 'dalemasd_newWp2021' );

/** MySQL database username */
define( 'DB_USER', 'dalemasd_bot120' );

/** MySQL database password */
define( 'DB_PASSWORD', '8)yl=I_]K64X)w71' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '/e0gjn&q0m#NlHM2K(5TNxVEi4W6B87 ;9/m@zlJEAW!:dHIhM7NjC@oamZ%nT>$' );
define( 'SECURE_AUTH_KEY',  'CDn8xn^`|(644u~z6/pKv];pJQ)]9gLbCES<=yl?yxgpFj!d]4EFUYG<,Vzx/U F' );
define( 'LOGGED_IN_KEY',    '2-,Hf,!E2g70VGby)A+st,hHsih<-*.xl(aF8c=4CjdWUIe`OmCi9YJSQF@;M kf' );
define( 'NONCE_KEY',        '[X$kXwcc<L@k_qT;8aj8,o;Jj5qJBjQpi+_0<EwQHb~ U3!fGf#Phwyle#bWW1PP' );
define( 'AUTH_SALT',        '5VZY6M,$7{I-_Hw0&!^^S]Bi^6Tl@l+R*tg %5W,X,X?0ky~slT[PO-|wgTRdHw{' );
define( 'SECURE_AUTH_SALT', '- Lt42SCl(;Calxw [vw?dtzh%3d$=@+oDedU=B<t+,ndch9FHZDC41s_@Z707G>' );
define( 'LOGGED_IN_SALT',   '1]D$1_RVS,$=F]>fXZh&>ubZV*-K2Y+*/#kZA8,vt=TVI&bt;r<I3z|GQ#~[,99I' );
define( 'NONCE_SALT',       'h~8` 1-_:t:<wh<BJ&$h/#x8rNhDz<];H6=31rT[<9WNmqgE9l, F>RI$P*cw(X]' );

/**#@-*/

/**
 * WordPress Database Table prefix.
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
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', false );
define( 'DOMAIN_CURRENT_SITE', 'dalemas.store' );
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
