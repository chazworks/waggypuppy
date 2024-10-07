<?php
/**
 * User Profile Administration Screen.
 *
 * @package __VAR_WP_TC
 * @subpackage Administration
 */

/**
 * This is a profile page.
 *
 * @since 2.5.0
 * @var bool
 */
define( 'IS_PROFILE_PAGE', true );

/** Load User Editing Page */
require_once __DIR__ . '/user-edit.php';
