<?php
/**
 * About This Version administration panel.
 *
 * @package WP
 * @subpackage Administration
 */

/** waggypuppy Administration Bootstrap */
require_once __DIR__ . '/admin.php';

// Used in the HTML title tag.
/* translators: Page title of the About waggypuppy page in the admin. */
$title = _x('About', 'page title');

[$display_version] = explode('-', get_bloginfo('version'));

require_once ABSPATH . 'wp-admin/admin-header.php';
?>
    <div class="wrap about__container">

        <div class="about__header">
            <div class="about__header-title">
                <h1>
                    <?php
                    printf(
                    /* translators: %s: Version number. */
                        __('waggypuppy %s'),
                        $display_version,
                    );
                    ?>
                </h1>
            </div>
        </div>

        <nav class="about__header-navigation nav-tab-wrapper wp-clearfix"
             aria-label="<?php esc_attr_e('Secondary menu'); ?>">
            <a href="about.php" class="nav-tab nav-tab-active" aria-current="page"><?php _e('What&#8217;s New'); ?></a>
            <a href="credits.php" class="nav-tab"><?php _e('Credits'); ?></a>
            <a href="freedoms.php" class="nav-tab"><?php _e('Freedoms'); ?></a>
            <a href="privacy.php" class="nav-tab"><?php _e('Privacy'); ?></a>
            <a href="contribute.php" class="nav-tab"><?php _e('Get Involved'); ?></a>
        </nav>

        <div class="about__section">
            <div class="column">
                <h2>
                    <?php
                    printf(
                    /* translators: %s: Version number. */
                        __('Welcome to waggypuppy %s'),
                        $display_version,
                    );
                    ?>
                </h2>
                <p class="is-subheading">
                    <?php _e('Features and Release Notes TODO'); ?>
                </p>
            </div>
        </div>

        <hr class="is-invisible is-large"/>

        <hr class="is-invisible is-large" style="margin-bottom:calc(2 * var(--gap));"/>

        <hr class="is-large" style="margin-top:calc(2 * var(--gap));"/>

        <hr class="is-large"/>

        <div class="return-to-dashboard">
            <?php
            if (isset($_GET['updated']) && current_user_can('update_core')) {
                printf(
                    '<a href="%1$s">%2$s</a> | ',
                    esc_url(self_admin_url('update-core.php')),
                    is_multisite() ? __('Go to Updates') : __('Go to Dashboard &rarr; Updates'),
                );
            }

            printf(
                '<a href="%1$s">%2$s</a>',
                esc_url(self_admin_url()),
                is_blog_admin() ? __('Go to Dashboard &rarr; Home') : __('Go to Dashboard'),
            );
            ?>
        </div>
    </div>

<?php require_once ABSPATH . 'wp-admin/admin-footer.php'; ?>

<?php

// These are strings we may use to describe maintenance/security releases, where we aim for no new strings.
return;

__('Maintenance Release');
__('Maintenance Releases');

__('Security Release');
__('Security Releases');

__('Maintenance and Security Release');
__('Maintenance and Security Releases');

/* translators: %s: waggypuppy version number. */
__('<strong>Version %s</strong> addressed one security issue.');
/* translators: %s: waggypuppy version number. */
__('<strong>Version %s</strong> addressed some security issues.');

/* translators: 1: waggypuppy version number, 2: Plural number of bugs. */
_n_noop(
    '<strong>Version %1$s</strong> addressed %2$s bug.',
    '<strong>Version %1$s</strong> addressed %2$s bugs.',
);

/* translators: 1: waggypuppy version number, 2: Plural number of bugs. Singular security issue. */
_n_noop(
    '<strong>Version %1$s</strong> addressed a security issue and fixed %2$s bug.',
    '<strong>Version %1$s</strong> addressed a security issue and fixed %2$s bugs.',
);

/* translators: 1: waggypuppy version number, 2: Plural number of bugs. More than one security issue. */
_n_noop(
    '<strong>Version %1$s</strong> addressed some security issues and fixed %2$s bug.',
    '<strong>Version %1$s</strong> addressed some security issues and fixed %2$s bugs.',
);

/* translators: %s: Documentation URL. */
__('For more information, see <a href="%s">the release notes</a>.');

/* translators: 1: waggypuppy version number, 2: Link to update waggypuppy */
__('Important! Your version of waggypuppy (%1$s) is no longer supported, you will not receive any security updates for your website. To keep your site secure, please <a href="%2$s">update to the latest version of waggypuppy</a>.');

/* translators: 1: waggypuppy version number, 2: Link to update waggypuppy */
__('Important! Your version of waggypuppy (%1$s) will stop receiving security updates in the near future. To keep your site secure, please <a href="%2$s">update to the latest version of waggypuppy</a>.');

/* translators: %s: The major version of waggypuppy for this branch. */
__('This is the final release of waggypuppy %s');

/* translators: The localized waggypuppy download URL. */
__('https://wp.org/download/');
