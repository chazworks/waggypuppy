<?php

/**
 * Tests for the WP_Customize_Selective_Refresh class.
 *
 * @package WP
 *
 * @group customize
 */
class Test_WP_Customize_Selective_Refresh extends WP_UnitTestCase
{

    /**
     * Manager.
     *
     * @var WP_Customize_Manager
     */
    public $wp_customize;

    /**
     * Component.
     *
     * @var WP_Customize_Selective_Refresh
     */
    public $selective_refresh;

    /**
     * Set up the test fixture.
     */
    public function set_up()
    {
        parent::set_up();
        require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
        $GLOBALS['wp_customize'] = new WP_Customize_Manager();
        $this->wp_customize = $GLOBALS['wp_customize'];
        if (isset($this->wp_customize->selective_refresh)) {
            $this->selective_refresh = $this->wp_customize->selective_refresh;
        }
    }

    /**
     * Test WP_Customize_Selective_Refresh::__construct().
     *
     * @see WP_Customize_Selective_Refresh::__construct()
     */
    public function test_construct()
    {
        $this->assertSame($this->selective_refresh, $this->wp_customize->selective_refresh);
    }

    /**
     * Test WP_Customize_Selective_Refresh::register_scripts().
     *
     * @see WP_Customize_Selective_Refresh::register_scripts()
     */
    public function test_register_scripts()
    {
        $scripts = new WP_Scripts();
        $handles = [
            'customize-selective-refresh',
            'customize-preview-nav-menus',
            'customize-preview-widgets',
        ];
        foreach ($handles as $handle) {
            $this->assertArrayHasKey($handle, $scripts->registered);
        }
    }

    /**
     * Test WP_Customize_Selective_Refresh::partials().
     *
     * @see WP_Customize_Selective_Refresh::partials()
     */
    public function test_partials()
    {
        $this->assertIsArray($this->selective_refresh->partials());
    }

    /**
     * Test CRUD methods for partials.
     *
     * @see WP_Customize_Selective_Refresh::get_partial()
     * @see WP_Customize_Selective_Refresh::add_partial()
     * @see WP_Customize_Selective_Refresh::remove_partial()
     */
    public function test_crud_partial()
    {
        $partial = $this->selective_refresh->add_partial('foo');
        $this->assertSame($this->selective_refresh, $partial->component);
        $this->assertInstanceOf('WP_Customize_Partial', $partial);
        $this->assertSame($partial, $this->selective_refresh->get_partial($partial->id));
        $this->assertArrayHasKey($partial->id, $this->selective_refresh->partials());

        $this->selective_refresh->remove_partial($partial->id);
        $this->assertEmpty($this->selective_refresh->get_partial($partial->id));
        $this->assertArrayNotHasKey($partial->id, $this->selective_refresh->partials());

        $partial = new WP_Customize_Partial($this->selective_refresh, 'bar');
        $this->assertSame($partial, $this->selective_refresh->add_partial($partial));
        $this->assertSame($partial, $this->selective_refresh->get_partial('bar'));
        $this->assertSameSets(['bar'], array_keys($this->selective_refresh->partials()));

        add_filter('customize_dynamic_partial_args', [$this, 'filter_customize_dynamic_partial_args'], 10, 2);
        add_filter('customize_dynamic_partial_class', [$this, 'filter_customize_dynamic_partial_class'], 10, 3);

        $partial = $this->selective_refresh->add_partial('recognized-class');
        $this->assertInstanceOf('Tested_Custom_Partial', $partial);
        $this->assertSame('.recognized', $partial->selector);
    }

    /**
     * Test WP_Customize_Selective_Refresh::init_preview().
     *
     * @see WP_Customize_Selective_Refresh::init_preview()
     */
    public function test_init_preview()
    {
        $this->selective_refresh->init_preview();
        $this->assertSame(10,
            has_action('template_redirect', [$this->selective_refresh, 'handle_render_partials_request']));
        $this->assertSame(10, has_action('wp_enqueue_scripts', [$this->selective_refresh, 'enqueue_preview_scripts']));
    }

    /**
     * Test WP_Customize_Selective_Refresh::enqueue_preview_scripts().
     *
     * @see WP_Customize_Selective_Refresh::enqueue_preview_scripts()
     */
    public function test_enqueue_preview_scripts()
    {
        $scripts = wp_scripts();
        $this->assertNotContains('customize-selective-refresh', $scripts->queue);
        $this->selective_refresh->enqueue_preview_scripts();
        $this->assertContains('customize-selective-refresh', $scripts->queue);
        $this->assertSame(1000, has_action('wp_footer', [$this->selective_refresh, 'export_preview_data']));
    }

    /**
     * Test WP_Customize_Selective_Refresh::export_preview_data().
     *
     * @see WP_Customize_Selective_Refresh::export_preview_data()
     */
    public function test_export_preview_data()
    {
        $user_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        $user = new WP_User($user_id);
        do_action('customize_register', $this->wp_customize);
        $user->remove_cap('top_secret_clearance');
        $this->wp_customize->add_setting(
            'top_secret_message',
            [
                'capability' => 'top_secret_clearance', // The administrator role lacks this.
            ],
        );
        $this->selective_refresh->add_partial(
            'blogname',
            [
                'selector' => '#site-title',
            ],
        );
        $this->selective_refresh->add_partial(
            'top_secret_message',
            [
                'settings' => ['top_secret_message'],
            ],
        );
        ob_start();
        $this->selective_refresh->export_preview_data();
        $html = ob_get_clean();
        $this->assertTrue((bool)preg_match('/_customizePartialRefreshExports = ({.+})/s', $html, $matches));
        $exported_data = json_decode($matches[1], true);
        $this->assertIsArray($exported_data);
        $this->assertArrayHasKey('partials', $exported_data);
        $this->assertIsArray($exported_data['partials']);
        $this->assertArrayHasKey('blogname', $exported_data['partials']);
        $this->assertArrayNotHasKey('top_secret_message', $exported_data['partials']);
        $this->assertSame('#site-title', $exported_data['partials']['blogname']['selector']);
        $this->assertArrayHasKey('renderQueryVar', $exported_data);
        $this->assertArrayHasKey('l10n', $exported_data);
    }

    /**
     * Test WP_Customize_Selective_Refresh::add_dynamic_partials().
     *
     * @see WP_Customize_Selective_Refresh::add_dynamic_partials()
     */
    public function test_add_dynamic_partials()
    {
        $partial_ids = ['recognized', 'recognized-class', 'unrecognized', 'already-added'];

        $partials = $this->selective_refresh->add_dynamic_partials($partial_ids);
        $this->assertEmpty($partials);

        $this->selective_refresh->add_partial('already-added');

        add_filter('customize_dynamic_partial_args', [$this, 'filter_customize_dynamic_partial_args'], 10, 2);
        add_filter('customize_dynamic_partial_class', [$this, 'filter_customize_dynamic_partial_class'], 10, 3);

        $partials = $this->selective_refresh->add_dynamic_partials($partial_ids);
        $this->assertSameSets(['recognized', 'recognized-class'], wp_list_pluck($partials, 'id'));

        $this->assertInstanceOf('Tested_Custom_Partial', $this->selective_refresh->get_partial('recognized-class'));
        $this->assertNotInstanceOf('Tested_Custom_Partial', $this->selective_refresh->get_partial('recognized'));
        $this->assertSame('.recognized', $this->selective_refresh->get_partial('recognized')->selector);
    }

    /**
     * Filter customize_dynamic_partial_args.
     *
     * @param false|array $partial_args The arguments to the WP_Customize_Partial constructor.
     * @param string $partial_id ID for dynamic partial.
     * @return false|array Dynamic partial args.
     * @see Test_WP_Customize_Selective_Refresh::test_add_dynamic_partials()
     *
     */
    public function filter_customize_dynamic_partial_args($partial_args, $partial_id)
    {
        $this->assertTrue(false === $partial_args || is_array($partial_args));
        $this->assertIsString($partial_id);

        if (preg_match('/^recognized/', $partial_id)) {
            $partial_args = [
                'selector' => '.recognized',
            ];
        }

        return $partial_args;
    }

    /**
     * Filter customize_dynamic_partial_class.
     *
     * @param string $partial_class WP_Customize_Partial or a subclass.
     * @param string $partial_id ID for dynamic partial.
     * @param array $partial_args The arguments to the WP_Customize_Partial constructor.
     * @return string
     * @see Test_WP_Customize_Selective_Refresh::test_add_dynamic_partials()
     *
     */
    public function filter_customize_dynamic_partial_class($partial_class, $partial_id, $partial_args)
    {
        $this->assertIsArray($partial_args);
        $this->assertIsString($partial_id);
        $this->assertIsString($partial_class);

        if ('recognized-class' === $partial_id) {
            $partial_class = 'Tested_Custom_Partial';
        }

        return $partial_class;
    }

    /**
     * Test WP_Customize_Selective_Refresh::is_render_partials_request().
     *
     * @see WP_Customize_Selective_Refresh::is_render_partials_request()
     */
    public function test_is_render_partials_request()
    {
        $this->assertFalse($this->selective_refresh->is_render_partials_request());
        $_POST[WP_Customize_Selective_Refresh::RENDER_QUERY_VAR] = '1';
        $this->assertTrue($this->selective_refresh->is_render_partials_request());
    }

    /**
     * Tear down.
     */
    public function tear_down()
    {
        $this->wp_customize = null;
        unset($GLOBALS['wp_customize']);
        unset($GLOBALS['wp_scripts']);
        parent::tear_down();
    }
}

require_once ABSPATH . WPINC . '/customize/class-wp-customize-partial.php';

/**
 * Class Tested_Custom_Partial
 */
class Tested_Custom_Partial extends WP_Customize_Partial
{

    /**
     * Type.
     *
     * @var string
     */
    public $type = 'custom';
}
