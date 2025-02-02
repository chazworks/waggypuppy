<?php

/**
 * @group admin
 * @group upgrade
 *
 * @covers WP_Automatic_Updater
 */
class Tests_Admin_WpAutomaticUpdater extends WP_UnitTestCase
{
    /**
     * An instance of WP_Automatic_Updater.
     *
     * @var WP_Automatic_Updater
     */
    private static $updater;

    /**
     * WP_Automatic_Updater::send_plugin_theme_email
     * made accessible.
     *
     * @var ReflectionMethod
     */
    private static $send_plugin_theme_email;

    /**
     * Sets up shared fixtures.
     */
    public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
    {
        require_once ABSPATH . 'wp-admin/includes/class-wp-automatic-updater.php';
        self::$updater = new WP_Automatic_Updater();

        self::$send_plugin_theme_email = new ReflectionMethod(self::$updater, 'send_plugin_theme_email');
        self::$send_plugin_theme_email->setAccessible(true);
    }

    public function set_up()
    {
        parent::set_up();
        add_filter('pre_wp_mail', '__return_false');
    }

    /**
     * Tests that `WP_Automatic_Updater::send_plugin_theme_email()` appends
     * plugin URLs.
     *
     * @ticket 53049
     *
     * @covers       WP_Automatic_Updater::send_plugin_theme_email
     *
     * @dataProvider data_send_plugin_theme_email_should_append_plugin_urls
     *
     * @param string[] $urls The URL(s) to search for. Must not be empty.
     * @param object[] $successful An array of successful plugin update objects.
     * @param object[] $failed An array of failed plugin update objects.
     */
    public function test_send_plugin_theme_email_should_append_plugin_urls($urls, $successful, $failed)
    {
        add_filter(
            'wp_mail',
            function ($args) use ($urls) {
                foreach ($urls as $url) {
                    $this->assertStringContainsString(
                        $url,
                        $args['message'],
                        'The email message should contain ' . $url,
                    );
                }
            },
        );

        $has_successful = !empty($successful);
        $has_failed = !empty($failed);

        if (!$has_successful && !$has_failed) {
            $this->markTestSkipped('This test requires at least one successful or failed plugin update object.');
        }

        $type = $has_successful && $has_failed ? 'mixed' : (!$has_failed ? 'success' : 'fail');

        $args = [$type, ['plugin' => $successful], ['plugin' => $failed]];
        self::$send_plugin_theme_email->invokeArgs(self::$updater, $args);
    }

    /**
     * Data provider: Provides an array of plugin update objects that should
     * have their URLs appended to the email message.
     *
     * @return array
     */
    public function data_send_plugin_theme_email_should_append_plugin_urls()
    {
        return [
            'successful updates, the current version and the plugin url' => [
                'urls' => ['http://example.org/successful-plugin'],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => 'http://example.org/successful-plugin',
                        ],
                    ],
                ],
                'failed' => [],
            ],
            'successful updates, no current version and the plugin url' => [
                'urls' => ['http://example.org/successful-plugin'],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => 'http://example.org/successful-plugin',
                        ],
                    ],
                ],
                'failed' => [],
            ],
            'failed updates, the current version and the plugin url' => [
                'urls' => ['http://example.org/failed-plugin'],
                'successful' => [],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => 'http://example.org/failed-plugin',
                        ],
                    ],
                ],
            ],
            'failed updates, no current version and the plugin url' => [
                'urls' => ['http://example.org/failed-plugin'],
                'successful' => [],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => 'http://example.org/failed-plugin',
                        ],
                    ],
                ],
            ],
            'mixed updates, the current version and a successful plugin url' => [
                'urls' => ['http://example.org/successful-plugin'],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => 'http://example.org/successful-plugin',
                        ],
                    ],
                ],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
            ],
            'mixed updates, no current version and a successful plugin url' => [
                'urls' => ['http://example.org/successful-plugin'],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => 'http://example.org/successful-plugin',
                        ],
                    ],
                ],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
            ],
            'mixed updates, the current version and a failed plugin url' => [
                'urls' => ['http://example.org/failed-plugin'],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => 'http://example.org/failed-plugin',
                        ],
                    ],
                ],
            ],
            'mixed updates, no current version and a failed plugin url' => [
                'urls' => ['http://example.org/failed-plugin'],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => 'http://example.org/failed-plugin',
                        ],
                    ],
                ],
            ],
            'mixed updates, the current version and both successful and failed plugin urls' => [
                'urls' => [
                    'http://example.org/successful-plugin',
                    'http://example.org/failed-plugin',
                ],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => 'http://example.org/successful-plugin',
                        ],
                    ],
                ],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => 'http://example.org/failed-plugin',
                        ],
                    ],
                ],
            ],
            'mixed updates, no current version and both successful and failed plugin urls' => [
                'urls' => [
                    'http://example.org/successful-plugin',
                    'http://example.org/failed-plugin',
                ],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => 'http://example.org/successful-plugin',
                        ],
                    ],
                ],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => 'http://example.org/failed-plugin',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests that `WP_Automatic_Updater::send_plugin_theme_email()` does not
     * append plugin URLs.
     *
     * @ticket 53049
     *
     * @covers       WP_Automatic_Updater::send_plugin_theme_email
     *
     * @dataProvider data_send_plugin_theme_email_should_not_append_plugin_urls
     *
     * @param string[] $urls The URL(s) to search for. Must not be empty.
     * @param object[] $successful An array of successful plugin update objects.
     * @param object[] $failed An array of failed plugin update objects.
     */
    public function test_send_plugin_theme_email_should_not_append_plugin_urls($urls, $successful, $failed)
    {
        add_filter(
            'wp_mail',
            function ($args) use ($urls) {
                foreach ($urls as $url) {
                    $this->assertStringNotContainsString(
                        $url,
                        $args['message'],
                        'The email message should not contain ' . $url,
                    );
                }
            },
        );

        $has_successful = !empty($successful);
        $has_failed = !empty($failed);

        if (!$has_successful && !$has_failed) {
            $this->markTestSkipped('This test requires at least one successful or failed plugin update object.');
        }

        $type = $has_successful && $has_failed ? 'mixed' : (!$has_failed ? 'success' : 'fail');

        $args = [$type, ['plugin' => $successful], ['plugin' => $failed]];
        self::$send_plugin_theme_email->invokeArgs(self::$updater, $args);
    }

    /**
     * Data provider: Provides an array of plugin update objects that should
     * not have their URL appended to the email message.
     *
     * @return array
     */
    public function data_send_plugin_theme_email_should_not_append_plugin_urls()
    {
        return [
            'successful updates, the current version, but no plugin url' => [
                'urls' => ['http://example.org/successful-plugin'],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
                'failed' => [],
            ],
            'successful updates, but no current version or plugin url' => [
                'urls' => ['http://example.org/successful-plugin'],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
                'failed' => [],
            ],
            'failed updates, the current version, but no plugin url' => [
                'urls' => ['http://example.org/failed-plugin'],
                'successful' => [],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
            ],
            'failed updates, but no current version or plugin url' => [
                'urls' => ['http://example.org/failed-plugin'],
                'successful' => [],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
            ],
            'mixed updates, the current version, but no successful plugin url' => [
                'urls' => ['http://example.org/successful-plugin'],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => 'http://example.org/failed-plugin',
                        ],
                    ],
                ],
            ],
            'mixed updates, but no current version or successful plugin url' => [
                'urls' => ['http://example.org/successful-plugin'],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => 'http://example.org/failed-plugin',
                        ],
                    ],
                ],
            ],
            'mixed updates, the current version, but no failed plugin url' => [
                'urls' => ['http://example.org/failed-plugin'],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => 'http://example.org/successful-plugin',
                        ],
                    ],
                ],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
            ],
            'mixed updates, no current version or failed plugin url' => [
                'urls' => ['http://example.org/failed-plugin'],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => 'http://example.org/successful-plugin',
                        ],
                    ],
                ],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
            ],
            'mixed updates, the current version and no successful or failed plugin urls' => [
                'urls' => [
                    'http://example.org/successful-plugin',
                    'http://example.org/failed-plugin',
                ],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '1.0.0',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
            ],
            'mixed updates, no current version and no successful or failed plugin urls' => [
                'urls' => [
                    'http://example.org/successful-plugin',
                    'http://example.org/failed-plugin',
                ],
                'successful' => [
                    (object)[
                        'name' => 'Successful Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'successful-plugin/successful-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
                'failed' => [
                    (object)[
                        'name' => 'Failed Plugin',
                        'item' => (object)[
                            'current_version' => '',
                            'new_version' => '2.0.0',
                            'plugin' => 'failed-plugin/failed-plugin.php',
                            'url' => '',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests that `WP_Automatic_Updater::is_allowed_dir()` returns true
     * when the `open_basedir` directive is not set.
     *
     * @ticket 42619
     *
     * @covers WP_Automatic_Updater::is_allowed_dir
     */
    public function test_is_allowed_dir_should_return_true_if_open_basedir_is_not_set()
    {
        $this->assertTrue(self::$updater->is_allowed_dir(ABSPATH));
    }

    /**
     * Tests that `WP_Automatic_Updater::is_allowed_dir()` returns true
     * when the `open_basedir` directive is set and the path is allowed.
     *
     * Runs in a separate process to ensure that `open_basedir` changes
     * don't impact other tests should an error occur.
     *
     * This test does not preserve global state to prevent the exception
     * "Serialization of 'Closure' is not allowed" when running in
     * a separate process.
     *
     * @ticket 42619
     *
     * @covers WP_Automatic_Updater::is_allowed_dir
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_is_allowed_dir_should_return_true_if_open_basedir_is_set_and_path_is_allowed()
    {
        // The repository for PHPUnit and test suite resources.
        $abspath_parent = trailingslashit(dirname(ABSPATH));
        $abspath_grandparent = trailingslashit(dirname($abspath_parent));

        $open_basedir_backup = ini_get('open_basedir');
        // Allow access to the directory one level above the repository.
        ini_set('open_basedir', sys_get_temp_dir() . PATH_SEPARATOR . wp_normalize_path($abspath_grandparent));

        // Checking an allowed directory should succeed.
        $actual = self::$updater->is_allowed_dir(wp_normalize_path(ABSPATH));

        ini_set('open_basedir', $open_basedir_backup);

        $this->assertTrue($actual);
    }

    /**
     * Tests that `WP_Automatic_Updater::is_allowed_dir()` returns false
     * when the `open_basedir` directive is set and the path is not allowed.
     *
     * Runs in a separate process to ensure that `open_basedir` changes
     * don't impact other tests should an error occur.
     *
     * This test does not preserve global state to prevent the exception
     * "Serialization of 'Closure' is not allowed" when running in
     * a separate process.
     *
     * @ticket 42619
     *
     * @covers WP_Automatic_Updater::is_allowed_dir
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_is_allowed_dir_should_return_false_if_open_basedir_is_set_and_path_is_not_allowed()
    {
        // The repository for PHPUnit and test suite resources.
        $abspath_parent = trailingslashit(dirname(ABSPATH));
        $abspath_grandparent = trailingslashit(dirname($abspath_parent));

        $open_basedir_backup = ini_get('open_basedir');
        // Allow access to the directory one level above the repository.
        ini_set('open_basedir', sys_get_temp_dir() . PATH_SEPARATOR . wp_normalize_path($abspath_grandparent));

        // Checking a directory not within the allowed path should trigger an `open_basedir` warning.
        $actual = self::$updater->is_allowed_dir('/.git');

        ini_set('open_basedir', $open_basedir_backup);

        $this->assertFalse($actual);
    }

    /**
     * Tests that `WP_Automatic_Updater::is_allowed_dir()` throws `_doing_it_wrong()`
     * when an invalid `$dir` argument is provided.
     *
     * @ticket 42619
     *
     * @covers       WP_Automatic_Updater::is_allowed_dir
     *
     * @expectedIncorrectUsage WP_Automatic_Updater::is_allowed_dir
     *
     * @dataProvider data_is_allowed_dir_should_throw_doing_it_wrong_with_invalid_dir
     *
     * @param mixed $dir The directory to check.
     */
    public function test_is_allowed_dir_should_throw_doing_it_wrong_with_invalid_dir($dir)
    {
        $this->assertFalse(self::$updater->is_allowed_dir($dir));
    }

    /**
     * Data provider.
     *
     * @return array[]
     */
    public function data_is_allowed_dir_should_throw_doing_it_wrong_with_invalid_dir()
    {
        return [
            // Type checks and boolean comparisons.
            'null' => ['dir' => null],
            '(bool) false' => ['dir' => false],
            '(bool) true' => ['dir' => true],
            '(int) 0' => ['dir' => 0],
            '(int) -0' => ['dir' => -0],
            '(int) 1' => ['dir' => 1],
            '(int) -1' => ['dir' => -1],
            '(float) 0.0' => ['dir' => 0.0],
            '(float) -0.0' => ['dir' => -0.0],
            '(float) 1.0' => ['dir' => 1.0],
            'empty string' => ['dir' => ''],
            'empty array' => ['dir' => []],
            'populated array' => ['dir' => [ABSPATH]],
            'empty object' => ['dir' => new stdClass()],
            'populated object' => ['dir' => (object)[ABSPATH]],
            'INF' => ['dir' => INF],
            'NAN' => ['dir' => NAN],

            // Ensures that `trim()` has been called.
            'string with only spaces' => ['dir' => '   '],
            'string with only tabs' => ['dir' => "\t\t"],
            'string with only newlines' => ['dir' => "\n\n"],
            'string with only carriage returns' => ['dir' => "\r\r"],
        ];
    }

    /**
     * Tests that `WP_Automatic_Updater::is_vcs_checkout()` returns `false`
     * when none of the checked directories are allowed.
     *
     * @ticket 58563
     *
     * @covers WP_Automatic_Updater::is_vcs_checkout
     */
    public function test_is_vcs_checkout_should_return_false_when_no_directories_are_allowed()
    {
        $updater_mock = $this->getMockBuilder('WP_Automatic_Updater')
            // Note: setMethods() is deprecated in PHPUnit 9, but still supported.
            ->setMethods(['is_allowed_dir'])
            ->getMock();

        /*
         * As none of the directories should be allowed, simply mocking `WP_Automatic_Updater`
         * and forcing `::is_allowed_dir()` to return `false` removes the need to run the test
         * in a separate process due to setting the `open_basedir` PHP directive.
         */
        $updater_mock->expects($this->any())->method('is_allowed_dir')->willReturn(false);

        $this->assertFalse($updater_mock->is_vcs_checkout(get_temp_dir()));
    }
}
