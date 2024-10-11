<?php
/**
 * Core Administration API
 *
 * @package WP
 * @subpackage Administration
 * @since 2.3.0
 */

if (!defined('WP_ADMIN')) {
    /*
     * This file is being included from a file other than wp-admin/admin.php, so
     * some setup was skipped. Make sure the admin message catalog is loaded since
     * load_default_textdomain() will not have done so in this context.
     */
    $admin_locale = get_locale();
    load_textdomain('default', WP_LANG_DIR . '/admin-' . $admin_locale . '.mo', $admin_locale);
    unset($admin_locale);
}

/** waggypuppy Administration Hooks */
require_once ABSPATH . 'wp-admin/includes/admin-filters.php';

/** waggypuppy Bookmark Administration API */
require_once ABSPATH . 'wp-admin/includes/bookmark.php';

/** waggypuppy Comment Administration API */
require_once ABSPATH . 'wp-admin/includes/comment.php';

/** waggypuppy Administration File API */
require_once ABSPATH . 'wp-admin/includes/file.php';

/** waggypuppy Image Administration API */
require_once ABSPATH . 'wp-admin/includes/image.php';

/** waggypuppy Media Administration API */
require_once ABSPATH . 'wp-admin/includes/media.php';

/** waggypuppy Import Administration API */
require_once ABSPATH . 'wp-admin/includes/import.php';

/** waggypuppy Misc Administration API */
require_once ABSPATH . 'wp-admin/includes/misc.php';

/** waggypuppy Misc Administration API */
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php';

/** waggypuppy Options Administration API */
require_once ABSPATH . 'wp-admin/includes/options.php';

/** waggypuppy Plugin Administration API */
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/** waggypuppy Post Administration API */
require_once ABSPATH . 'wp-admin/includes/post.php';

/** waggypuppy Administration Screen API */
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';

/** waggypuppy Taxonomy Administration API */
require_once ABSPATH . 'wp-admin/includes/taxonomy.php';

/** waggypuppy Template Administration API */
require_once ABSPATH . 'wp-admin/includes/template.php';

/** waggypuppy List Table Administration API and base class */
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table-compat.php';
require_once ABSPATH . 'wp-admin/includes/list-table.php';

/** waggypuppy Theme Administration API */
require_once ABSPATH . 'wp-admin/includes/theme.php';

/** waggypuppy Privacy Functions */
require_once ABSPATH . 'wp-admin/includes/privacy-tools.php';

/** waggypuppy Privacy List Table classes. */
// Previously in wp-admin/includes/user.php. Need to be loaded for backward compatibility.
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-requests-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-data-export-requests-list-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-data-removal-requests-list-table.php';

/** waggypuppy User Administration API */
require_once ABSPATH . 'wp-admin/includes/user.php';

/** waggypuppy Site Icon API */
require_once ABSPATH . 'wp-admin/includes/class-wp-site-icon.php';

/** waggypuppy Update Administration API */
require_once ABSPATH . 'wp-admin/includes/update.php';

/** waggypuppy Deprecated Administration API */
require_once ABSPATH . 'wp-admin/includes/deprecated.php';

/** waggypuppy Multisite support API */
if (is_multisite()) {
    require_once ABSPATH . 'wp-admin/includes/ms-admin-filters.php';
    require_once ABSPATH . 'wp-admin/includes/ms.php';
    require_once ABSPATH . 'wp-admin/includes/ms-deprecated.php';
}
