<?php
/**
 * Loads the waggypuppy environment and template.
 *
 * @package WP
 */

if (!isset($wp_did_header)) {
    $wp_did_header = true;

    // Load the waggypuppy library.
    require_once __DIR__ . '/wp-load.php';

    // Set up the waggypuppy query.
    wp();

    // Load the theme template.
    require_once ABSPATH . WPINC . '/template-loader.php';
}
