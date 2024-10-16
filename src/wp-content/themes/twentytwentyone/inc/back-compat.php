<?php
/**
 * Back compat functionality
 *
 * Prevents the theme from running on WP versions prior to 5.3,
 * since this theme is not meant to be backward compatible beyond that and
 * relies on many newer functions and markup changes introduced in 5.3.
 *
 * @package WP
 * @subpackage Twenty_Twenty_One
 * @since Twenty Twenty-One 1.0
 */

/**
 * Display upgrade notice on theme switch.
 *
 * @return void
 * @since Twenty Twenty-One 1.0
 *
 */
function twenty_twenty_one_switch_theme()
{
    add_action('admin_notices', 'twenty_twenty_one_upgrade_notice');
}

add_action('after_switch_theme', 'twenty_twenty_one_switch_theme');

/**
 * Adds a message for unsuccessful theme switch.
 *
 * Prints an update nag after an unsuccessful attempt to switch to
 * the theme on WP versions prior to 5.3.
 *
 * @return void
 * @global string $wp_version waggypuppy version.
 *
 * @since Twenty Twenty-One 1.0
 *
 */
function twenty_twenty_one_upgrade_notice()
{
    echo '<div class="error"><p>';
    printf(
    /* translators: %s: WP Version. */
        esc_html__('This theme requires WP 5.3 or newer. You are running version %s. Please upgrade.',
            'twentytwentyone'),
        esc_html($GLOBALS['wp_version']),
    );
    echo '</p></div>';
}

/**
 * Prevents the Customizer from being loaded on WP versions prior to 5.3.
 *
 * @return void
 * @global string $wp_version waggypuppy version.
 *
 * @since Twenty Twenty-One 1.0
 *
 */
function twenty_twenty_one_customize()
{
    wp_die(
        sprintf(
        /* translators: %s: WP Version. */
            esc_html__('This theme requires WP 5.3 or newer. You are running version %s. Please upgrade.',
                'twentytwentyone'),
            esc_html($GLOBALS['wp_version']),
        ),
        '',
        [
            'back_link' => true,
        ],
    );
}

add_action('load-customize.php', 'twenty_twenty_one_customize');

/**
 * Prevents the Theme Preview from being loaded on WP versions prior to 5.3.
 *
 * @return void
 * @global string $wp_version waggypuppy version.
 *
 * @since Twenty Twenty-One 1.0
 *
 */
function twenty_twenty_one_preview()
{
    if (isset($_GET['preview'])) {
        wp_die(
            sprintf(
            /* translators: %s: WP Version. */
                esc_html__('This theme requires WP 5.3 or newer. You are running version %s. Please upgrade.',
                    'twentytwentyone'),
                esc_html($GLOBALS['wp_version']),
            ),
        );
    }
}

add_action('template_redirect', 'twenty_twenty_one_preview');
