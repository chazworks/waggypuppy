<?php

/**
 * @group l10n
 * @group i18n
 *
 * @covers ::load_script_textdomain
 */
class Tests_L10n_LoadScriptTextdomain extends WP_UnitTestCase
{

    /**
     * @ticket 45528
     * @ticket 46336
     * @ticket 46387
     * @ticket 49145
     * @ticket 60891
     * @ticket 62016
     *
     * @dataProvider data_resolve_relative_path
     */
    public function test_resolve_relative_path($translation_path, $handle, $src, $textdomain, $filter = [])
    {
        if (!empty($filter)) {
            add_filter($filter[0], $filter[1], 10, isset($filter[2]) ? $filter[2] : 1);
        }
        wp_enqueue_script($handle, $src, [], null);

        $expected = file_get_contents(DIR_TESTDATA . $translation_path);
        $this->assertSame($expected, load_script_textdomain($handle, $textdomain, DIR_TESTDATA . '/languages'));
    }

    public function data_resolve_relative_path()
    {
        return [
            // @ticket 45528
            [
                '/languages/en_US-813e104eb47e13dd4cc5af844c618754.json',
                'test-example-root',
                '/wp-includes/js/script.js',
                'default',
            ],
            // Assets on a CDN.
            [
                '/languages/en_US-813e104eb47e13dd4cc5af844c618754.json',
                'test-example-cdn',
                'https://my-cdn.com/wordpress/wp-includes/js/script.js',
                'default',
                ['load_script_textdomain_relative_path', [$this, 'relative_path_from_cdn'], 2],
            ],
            // Test for waggypuppy installs in a subdirectory.
            [
                '/languages/en_US-813e104eb47e13dd4cc5af844c618754.json',
                'test-example-subdir',
                '/wp/wp-includes/js/script.js',
                'default',
                [
                    'site_url',
                    static function ($site_url) {
                        return $site_url . '/wp';
                    },
                ],
            ],
            // @ticket 46336
            [
                '/languages/plugins/internationalized-plugin-en_US-2f86cb96a0233e7cb3b6f03ad573be0b.json',
                'plugin-example-1',
                'https://plugins.example.com/my-plugin/js/script.js',
                'internationalized-plugin',
                [
                    'plugins_url',
                    static function () {
                        return 'https://plugins.example.com';
                    },
                ],
            ],
            // @ticket 46387
            [
                '/languages/plugins/internationalized-plugin-en_US-2f86cb96a0233e7cb3b6f03ad573be0b.json',
                'plugin-example-2',
                'https://content.example.com/plugins/my-plugin/js/script.js',
                'internationalized-plugin',
                [
                    'content_url',
                    static function () {
                        return 'https://content.example.com';
                    },
                ],
            ],
            // @ticket 49145
            [
                '/languages/plugins/internationalized-plugin-en_US-2f86cb96a0233e7cb3b6f03ad573be0b.json',
                'test-when-no-content_url-host',
                'https://content.example.com/plugins/my-plugin/js/script.js',
                'internationalized-plugin',
                [
                    'content_url',
                    static function () {
                        return '/';
                    },
                ],
            ],
            // @ticket 49145
            [
                '/languages/plugins/internationalized-plugin-en_US-2f86cb96a0233e7cb3b6f03ad573be0b.json',
                'test-when-no-plugins_url-host',
                'https://plugins.example.com/my-plugin/js/script.js',
                'internationalized-plugin',
                [
                    'plugins_url',
                    static function () {
                        return '/';
                    },
                ],
            ],
            // @ticket 49145
            [
                '/languages/en_US-813e104eb47e13dd4cc5af844c618754.json',
                'test-when-no-site_url-host',
                '/wp/wp-includes/js/script.js',
                'default',
                [
                    'site_url',
                    static function () {
                        return '/wp';
                    },
                ],
            ],
            // @ticket 60891
            [
                '/languages/plugins/internationalized-plugin-en_US-2f86cb96a0233e7cb3b6f03ad573be0b.json',
                'plugin-in-custom-plugin-dir',
                '/wp-content/mods/my-plugin/js/script.js',
                'internationalized-plugin',
                [
                    'plugins_url',
                    static function () {
                        return 'https://example.com/wp-content/mods';
                    },
                ],
            ],
            // @ticket 62016
            [
                '/languages/themes/internationalized-theme-en_US-2f86cb96a0233e7cb3b6f03ad573be0b.json',
                'theme-with-script-translations',
                '/wp-content/themes/my-theme/js/script.js',
                'internationalized-theme',
            ],
        ];
    }

    public function relative_path_from_cdn($relative, $src)
    {
        if (0 === strpos($src, 'https://my-cdn.com/wordpress/')) {
            return substr($src, strlen('https://my-cdn.com/wordpress/'));
        }

        return $relative;
    }

    /**
     * Tests that PHP 8.1 "passing null to non-nullable" deprecation notice
     * is not thrown when passing the default `$path` to untrailingslashit() in the function.
     *
     * The notice that we should not see:
     * `Deprecated: rtrim(): Passing null to parameter #1 ($string) of type string is deprecated`.
     *
     * @ticket 55967
     */
    public function test_does_not_throw_deprecation_notice_for_rtrim_with_default_parameters()
    {
        $handle = 'test-example-root';
        $src = '/wp-includes/js/script.js';

        wp_enqueue_script($handle, $src);

        $expected = file_get_contents(DIR_TESTDATA . '/languages/en_US-813e104eb47e13dd4cc5af844c618754.json');
        $this->assertSame($expected, load_script_textdomain($handle));
    }
}
