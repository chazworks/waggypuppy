<?php
const WP_DEBUG = true;

const DB_NAME = 'wordpress_develop';
const DB_USER = 'root';
const DB_PASSWORD = 'password';
const DB_HOST = 'mysql';
const DB_CHARSET = 'utf8';
const DB_COLLATE = '';

$table_prefix = 'wp_';

## BEGIN: keys
const AUTH_KEY = 'deadbeef';
const SECURE_AUTH_KEY = 'deadbeef';
const LOGGED_IN_KEY = 'deadbeef';
const NONCE_KEY = 'deadbeef';
const AUTH_SALT = 'deadbeef';
const SECURE_AUTH_SALT = 'deadbeef';
const LOGGED_IN_SALT = 'deadbeef';
const NONCE_SALT = 'deadbeef';
## END: keys

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';
