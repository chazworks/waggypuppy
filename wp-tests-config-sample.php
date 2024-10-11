<?php

// wp config for unit tests.  copy this into wp-tests-config.php and edit to your needs

const ABSPATH = __DIR__ . '/src/';

const WP_DEBUG = true;
const WP_SITEURL = 'http://example.org';
const WP_DEFAULT_THEME = 'default';
const WP_TESTS_DOMAIN = 'example.org';
const WP_TESTS_EMAIL = 'admin@example.org';
const WP_TESTS_TITLE = 'Test Blog';
const WP_PHP_BINARY = 'php';
const WPLANG = '';

// define('WP_TESTS_MULTISITE', true);
// define('WP_TESTS_FORCE_KNOWN_BUGS', true);

$table_prefix = 'wptests_';
const DB_NAME = 'wordpress_develop_tests';
const DB_USER = 'root';
const DB_PASSWORD = 'password';
const DB_HOST = 'mysql';
const DB_CHARSET = 'utf8';
const DB_COLLATE = '';

const FS_METHOD = 'direct';

const AUTH_KEY = 'put your unique phrase here';
const SECURE_AUTH_KEY = 'put your unique phrase here';
const LOGGED_IN_KEY = 'put your unique phrase here';
const NONCE_KEY = 'put your unique phrase here';
const AUTH_SALT = 'put your unique phrase here';
const SECURE_AUTH_SALT = 'put your unique phrase here';
const LOGGED_IN_SALT = 'put your unique phrase here';
const NONCE_SALT = 'put your unique phrase here';


