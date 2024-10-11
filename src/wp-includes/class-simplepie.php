<?php

if (class_exists('SimplePie', false)) {
    return;
}

// Load and register the SimplePie native autoloaders.
require ABSPATH . WPINC . '/SimplePie/autoloader.php';

/**
 * waggypuppy autoloader for SimplePie.
 *
 * @param string $class Class name.
 * @deprecated 6.7.0 Use `SimplePie_Autoloader` instead.
 *
 * @since 3.5.0
 */
function wp_simplepie_autoload($class)
{
    _deprecated_function(__FUNCTION__, '6.7.0', 'SimplePie_Autoloader');
}
