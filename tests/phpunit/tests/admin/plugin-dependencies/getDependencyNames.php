<?php
/**
 * Tests for the WP_Plugin_Dependencies::get_dependency_names() method.
 *
 * @package WP
 */

require_once __DIR__ . '/base.php';

/**
 * @group admin
 * @group plugins
 *
 * @covers WP_Plugin_Dependencies::get_dependency_names
 * @covers WP_Plugin_Dependencies::get_dependency_api_data
 * @covers WP_Plugin_Dependencies::get_dependencies
 * @covers WP_Plugin_Dependencies::get_dependency_filepaths
 */
class Tests_Admin_WPPluginDependencies_GetDependencyNames extends WP_PluginDependencies_UnitTestCase
{

    /**
     * Mocks an API response.
     *
     * @param string $type The type of response. Accepts 'success' or 'failure'.
     */
    private function mock_api_response($type)
    {
        add_filter(
            'plugins_api',
            function ($bypass, $action, $args) use ($type) {
                if ('plugin_information' === $action && isset($args->slug)
                    && str_starts_with($args->slug, 'dependency')) {
                    if ('success' === $type) {
                        return (object)[
                            'slug' => $args->slug,
                            'name' => 'Dependency ' . str_replace('dependency', '', $args->slug),
                        ];
                    } elseif ('failure' === $type) {
                        return new WP_Error('plugin_not_found', 'Plugin not found.');
                    }
                }

                return $bypass;
            },
            10,
            3,
        );
    }

    /**
     * Tests that dependency names are retrieved.
     *
     * @ticket 22316
     *
     * @global string $pagenow The filename of the current screen.
     */
    public function test_should_get_dependency_names()
    {
        global $pagenow;

        // Backup $pagenow.
        $old_pagenow = $pagenow;

        // Ensure is_admin() and screen checks pass.
        $pagenow = 'plugins.php';
        set_current_screen('plugins.php');

        $this->set_property_value(
            'plugins',
            ['dependent/dependent.php' => ['RequiresPlugins' => 'dependency, dependency2']],
        );

        $this->mock_api_response('success');
        self::$instance::initialize();

        $this->set_property_value(
            'dependency_filepaths',
            [
                'dependency' => 'dependency/dependency.php',
                'dependency2' => 'dependency2/dependency2.php',
            ],
        );

        $this->set_property_value(
            'dependency_api_data',
            [
                'dependency' => [
                    'name' => 'Dependency 1',
                ],
                'dependency2' => [
                    'name' => 'Dependency 2',
                ],
            ],
        );

        $actual = self::$instance::get_dependency_names('dependent/dependent.php');

        // Restore $pagenow.
        $pagenow = $old_pagenow;

        $this->assertSame(
            [
                'dependency' => 'Dependency 1',
                'dependency2' => 'Dependency 2',
            ],
            $actual,
        );
    }

    /**
     * Tests that dependency slugs are used if their name is not available.
     *
     * @ticket 22316
     *
     * @global string $pagenow The filename of the current screen.
     */
    public function test_should_use_dependency_name_from_file()
    {
        global $pagenow;

        // Backup $pagenow.
        $old_pagenow = $pagenow;

        // Ensure is_admin() and screen checks pass.
        $pagenow = 'plugins.php';
        set_current_screen('plugins.php');

        $this->set_property_value(
            'plugins',
            [
                'dependent/dependent.php' => ['RequiresPlugins' => 'dependency, dependency2'],
                'dependency/dependency.php' => [
                    'Name' => 'Dependency 1',
                    'RequiresPlugins' => '',
                ],
                'dependency2/dependency2.php' => [
                    'Name' => 'Dependency 2',
                    'RequiresPlugins' => '',
                ],
            ],
        );

        $this->mock_api_response('failure');
        self::$instance::initialize();

        $this->set_property_value(
            'dependency_filepaths',
            [
                'dependency' => 'dependency/dependency.php',
                'dependency2' => 'dependency2/dependency2.php',
            ],
        );

        // The plugins are not in the Plugins repository.
        $this->set_property_value('dependency_api_data', []);

        $actual = self::$instance::get_dependency_names('dependent/dependent.php');

        // Restore $pagenow.
        $pagenow = $old_pagenow;

        $this->assertSame(
            [
                'dependency' => 'Dependency 1',
                'dependency2' => 'Dependency 2',
            ],
            $actual,
        );
    }

    /**
     * Tests that dependency slugs are used if their name is not available.
     *
     * @ticket 22316
     *
     * @global string $pagenow The filename of the current screen.
     */
    public function test_should_use_dependency_slugs()
    {
        global $pagenow;

        // Backup $pagenow.
        $old_pagenow = $pagenow;

        // Ensure is_admin() and screen checks pass.
        $pagenow = 'plugins.php';
        set_current_screen('plugins.php');

        $this->set_property_value(
            'plugins',
            ['dependent/dependent.php' => ['RequiresPlugins' => 'dependency, dependency2']],
        );

        $this->mock_api_response('failure');
        self::$instance::initialize();

        // The plugins are not in the Plugins repository.
        $this->set_property_value('dependency_api_data', []);

        $actual = self::$instance::get_dependency_names('dependent/dependent.php');

        // Restore $pagenow.
        $pagenow = $old_pagenow;

        $this->assertSame(
            [
                'dependency' => 'dependency',
                'dependency2' => 'dependency2',
            ],
            $actual,
        );
    }

    /**
     * Tests that `$dependency_api_data` is set when it's not already available.
     *
     * @ticket 22316
     *
     * @global string $pagenow The filename of the current screen.
     */
    public function test_should_set_dependency_data_when_not_already_available()
    {
        global $pagenow;

        // Backup $pagenow.
        $old_pagenow = $pagenow;

        // Ensure is_admin() and screen checks pass.
        $pagenow = 'plugins.php';
        set_current_screen('plugins.php');

        $this->set_property_value(
            'plugins',
            [
                'dependent/dependent.php' => [
                    'Name' => 'Dependent 1',
                    'RequiresPlugins' => 'dependency',
                ],
                'dependency/dependency.php' => [
                    'Name' => 'Dependency 1',
                    'RequiresPlugins' => '',
                ],
            ],
        );

        $this->set_property_value('dependency_slugs', ['dependency']);

        set_site_transient('wp_plugin_dependencies_plugin_data', ['dependency' => false]);
        set_site_transient('wp_plugin_dependencies_plugin_timeout_dependency', true, 12 * HOUR_IN_SECONDS);

        $this->mock_api_response('success');
        self::$instance::get_dependency_names('dependent');

        // Restore $pagenow.
        $pagenow = $old_pagenow;

        $this->assertSame(
            ['dependency' => ['Name' => 'Dependency 1']],
            $this->get_property_value('dependency_api_data'),
        );
    }
}
