<?php

// wp config for unit tests.  copy this into wp-tests-config.php and edit to your needs

define('ABSPATH', __DIR__ . '/src/');

define('WP_DEBUG', true);
define('WP_SITEURL', 'http://example.org');
define('WP_DEFAULT_THEME', 'default');
define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Blog');
define('WP_PHP_BINARY', 'php');
define('WPLANG', '');

// define('WP_TESTS_MULTISITE', true);
// define('WP_TESTS_FORCE_KNOWN_BUGS', true);

$table_prefix = 'wptests_';
define('DB_NAME', 'wordpress_develop_tests');
define('DB_USER', 'root');
define('DB_PASSWORD', 'password');
define('DB_HOST', 'mysql');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

define('FS_METHOD', 'direct');

define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
define('AUTH_SALT',        'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT',   'put your unique phrase here');
define('NONCE_SALT',       'put your unique phrase here');


