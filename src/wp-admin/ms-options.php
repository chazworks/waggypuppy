<?php
/**
 * Multisite network settings administration panel.
 *
 * @package __VAR_WP_TC
 * @subpackage Multisite
 * @since 3.0.0
 */

require_once __DIR__ . '/admin.php';

wp_redirect( network_admin_url( 'settings.php' ) );
exit;
