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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          ']f#;|3hITY?O*sh(stV%2:-JmU88th1mJbn<C`.G/5c.b$w&&l|S|/Rll6c=Zq}b' );
define( 'SECURE_AUTH_KEY',   'SfZ`Xi}FDt3T3Ueev!>;L4xayqvU[BfUuHNk1j}E9L9U0AAg2&g1vm3z54gOIkzk' );
define( 'LOGGED_IN_KEY',     'W1kEdJvjPa9Pt3/U^m]MSmjlB?1<36V~z!p}eDD=a$|5_Y&g(3{1%E(I+pf]^;S`' );
define( 'NONCE_KEY',         'wyhqEF[9`Es0e|#Kz15_0)Kl}|Soi(sF5On)N/15o,;E2oHs;y2Cg0mkBKdkDWp:' );
define( 'AUTH_SALT',         'a7H/)Bt-q*oSTQxbn)3rynQoh<b$[ah)#2rQ$qn2y*;}#;^B%k8YiNv6%Xu+k2_&' );
define( 'SECURE_AUTH_SALT',  'mh3xY^[Jux]d!Iq4>G+q6YC71wA>B`6NkL=vsRlObk?)V9CX5gVT|ZAanHcM&Yx2' );
define( 'LOGGED_IN_SALT',    'gCWqI>x9ex&p%@|VE;4Js8aK4$jnHO.7g8)GNEzNIS}z>:+HOsd5kw75R$?Mom,D' );
define( 'NONCE_SALT',        'cnFfRT0wLM,R)7Q$Vo#8[$-VP1ksmrsn&DfZ#lGcZ#MQhZu@,=`z=oK6@cXDW%9n' );
define( 'WP_CACHE_KEY_SALT', '_Dz1m)#eP*cS4(4BgKHJ}]0r`|F`itPqhA#6]F$n.I+xgwzeTY1{Yh,xztlQ?!Pc' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
