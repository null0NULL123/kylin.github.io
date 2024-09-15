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
define( 'DB_NAME', 'blog_cpoe_top' );

/** MySQL database username */
define( 'DB_USER', 'blog_cpoe_top' );

/** MySQL database password */
define( 'DB_PASSWORD', 'sys7DjTYzj3S3W7A' );

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
define( 'AUTH_KEY',         '=0iWEb!P[o>BC0>a*J<jkloIq2MB<g$`=]u/L:pYj: 4/LT1|Az(QM5qhq,qBOid' );
define( 'SECURE_AUTH_KEY',  '}[=P2_y+u~;CFUR>$/SgcAO~CL5pnTkt <:(9o-cz1X8e{jn)3KpBH)>Y_a=.](p' );
define( 'LOGGED_IN_KEY',    '#5]tmc^/S2gyi[Sp{KP./PYk2.?=6(y6l{e){&L)I2[Is7g%j,C&WER^V%<cP,(~' );
define( 'NONCE_KEY',        'JYsQ.(r=dv{+%DfPfZ?e().iz9u,!RlVh|p L,3m7llt[I;T1JSCTP|2geX~9Wx$' );
define( 'AUTH_SALT',        'y6sx-:W`Zl9cec))fi{1`f:M|?Wm?V?6wl*VI;9=!&mf#he3]<.s^wY37<w>j,[v' );
define( 'SECURE_AUTH_SALT', 'Z0WzULD6ul;MR$41_gw+)jel_Oq@$4-HumoT(A9W-juQ6].Bq %FB$d+X92 XW(:' );
define( 'LOGGED_IN_SALT',   'a+,0#V&;7/_f/Jk|QTdc)-mG&XBn.*,G1/R3bpihF|b w:}0L0kAokB&/U$#Mz*L' );
define( 'NONCE_SALT',       ')1D=neXfE&]2T/uoFjcgFRE4wQnes-mNf%8Q#Qn}oU.yJ G%3,}aXVqZd[r(EdxG' );

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
