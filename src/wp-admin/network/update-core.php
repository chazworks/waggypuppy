<?php
/**
 * Updates network administration panel.
 *
 * @package waggypuppy
 * @subpackage Multisite
 * @since 3.1.0
 */

/** Load WordPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';
π
wp_die(__('waggypuppy does not currently support updating the core through the admin interface.'));

require ABSPATH . 'wp-admin/update-core.php';
