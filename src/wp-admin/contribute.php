<?php
/**
 * Contribute administration panel.
 *
 * @package WP
 * @subpackage Administration
 */

/** waggypuppy Administration Bootstrap */
require_once __DIR__ . '/admin.php';

// Used in the HTML title tag.
$title = __('Get Involved');

[$display_version] = explode('-', get_bloginfo('version'));

require_once ABSPATH . 'wp-admin/admin-header.php';
?>
<div class="wrap about__container">

    <div class="about__header">
        <div class="about__header-title">
            <h1>
                <?php _e('Get Involved'); ?>
            </h1>
        </div>
    </div>

    <nav class="about__header-navigation nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Secondary menu'); ?>">
        <a href="about.php" class="nav-tab"><?php _e('What&#8217;s New'); ?></a>
        <a href="credits.php" class="nav-tab"><?php _e('Credits'); ?></a>
        <a href="freedoms.php" class="nav-tab"><?php _e('Freedoms'); ?></a>
        <a href="privacy.php" class="nav-tab"><?php _e('Privacy'); ?></a>
        <a href="contribute.php" class="nav-tab nav-tab-active" aria-current="page"><?php _e('Get Involved'); ?></a>
    </nav>

    TODO

</div>
<?php
require_once ABSPATH . 'wp-admin/admin-footer.php';
