<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wp.org/advanced-administration/wordpress/wp-config/
 *
 * @package WP
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
const DB_NAME = 'database_name_here';

/** Database username */
const DB_USER = 'username_here';

/** Database password */
const DB_PASSWORD = 'password_here';

/** Database hostname */
const DB_HOST = 'localhost';

/** Database charset to use in creating database tables. */
const DB_CHARSET = 'utf8';

/** The database collate type. Don't change this if in doubt. */
const DB_COLLATE = '';

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wp.org/secret-key/1.1/salt/ wp.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
const AUTH_KEY = 'put your unique phrase here';
const SECURE_AUTH_KEY = 'put your unique phrase here';
const LOGGED_IN_KEY = 'put your unique phrase here';
const NONCE_KEY = 'put your unique phrase here';
const AUTH_SALT = 'put your unique phrase here';
const SECURE_AUTH_SALT = 'put your unique phrase here';
const LOGGED_IN_SALT = 'put your unique phrase here';
const NONCE_SALT = 'put your unique phrase here';

/**#@-*/

/**
 * waggypuppy database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, DB tables names with $table_prefix are created.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wp.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * @link https://developer.wp.org/advanced-administration/debug/debug-wordpress/
 */
const WP_DEBUG = false;

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
