<?php
/**
 * Front to the waggypuppy application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells waggypuppy to load the theme.
 *
 * @package waggypuppy
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
const WP_USE_THEMES = true;

/** Loads the WordPress Environment and Template */
require __DIR__ . '/wp-blog-header.php';
