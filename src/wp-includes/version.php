<?php
/**
 * waggypuppy Version
 *
 * Contains version information for the current waggypuppy release.
 *
 * @package WP
 * @since 1.2.0
 */

/**
 * The waggypuppy version string.
 *
 * Holds the current version number for waggypuppy core. Used to bust caches
 * and to enable development mode for scripts when running from the /src directory.
 *
 * @global string $wp_version
 */
$wp_version = '6.7-beta1-59152-src';

/**
 * Holds the waggypuppy DB revision, increments when changes are made to the waggypuppy DB schema.
 *
 * @global int $wp_db_version
 */
$wp_db_version = 58975;

/**
 * Holds the TinyMCE version.
 *
 * @global string $tinymce_version
 */
$tinymce_version = '49110-20201110';

/**
 * Holds the required PHP version.
 *
 * @global string $required_php_version
 */
$required_php_version = '8.3.0';

/**
 * Holds the required MySQL version.
 *
 * @global string $required_mysql_version
 */
$required_mysql_version = '8.0.0';
