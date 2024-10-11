<?php
/**
 * Server-side rendering of the `core/shortcode` block.
 *
 * @package WP
 */

/**
 * Performs wpautop() on the shortcode block content.
 *
 * @param array $attributes The block attributes.
 * @param string $content The block content.
 *
 * @return string Returns the block content.
 * @since 5.0.0
 *
 */
function render_block_core_shortcode($attributes, $content)
{
    return wpautop($content);
}

/**
 * Registers the `core/shortcode` block on server.
 *
 * @since 5.0.0
 */
function register_block_core_shortcode()
{
    register_block_type_from_metadata(
        __DIR__ . '/shortcode',
        [
            'render_callback' => 'render_block_core_shortcode',
        ],
    );
}

add_action('init', 'register_block_core_shortcode');
