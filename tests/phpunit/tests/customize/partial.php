<?php

/**
 * Tests for the Test_WP_Customize_Partial class.
 *
 * @package WP
 *
 * @group customize
 */
class Test_WP_Customize_Partial extends WP_UnitTestCase
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
     * Set up.
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
     * Test WP_Customize_Partial::__construct().
     *
     * @see WP_Customize_Partial::__construct()
     */
    public function test_construct_default_args()
    {
        $partial_id = 'blogname';
        $partial = new WP_Customize_Partial($this->selective_refresh, $partial_id);
        $this->assertSame($partial_id, $partial->id);
        $this->assertSame($this->selective_refresh, $partial->component);
        $this->assertSame('default', $partial->type);
        $this->assertEmpty($partial->selector);
        $this->assertSame([$partial_id], $partial->settings);
        $this->assertSame($partial_id, $partial->primary_setting);
        $this->assertSame([$partial, 'render_callback'], $partial->render_callback);
        $this->assertFalse($partial->container_inclusive);
        $this->assertTrue($partial->fallback_refresh);
    }

    /**
     * Render post content partial.
     *
     * @param WP_Customize_Partial $partial Partial.
     * @return string|false Content or false if error.
     */
    public function render_post_content_partial($partial)
    {
        $id_data = $partial->id_data();
        $post_id = (int)$id_data['keys'][0];
        if (empty($post_id)) {
            return false;
        }
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        return apply_filters('the_content', $post->post_content);
    }

    /**
     * Test WP_Customize_Partial::__construct().
     *
     * @see WP_Customize_Partial::__construct()
     */
    public function test_construct_non_default_args()
    {
        $post_id = self::factory()->post->create(
            [
                'post_title' => 'Hello World',
                'post_content' => 'Lorem Ipsum',
            ],
        );

        $partial_id = sprintf('post_content[%d]', $post_id);
        $args = [
            'type' => 'post',
            'selector' => "article.post-$post_id .entry-content",
            'settings' => ['user[1]', "post[$post_id]"],
            'primary_setting' => "post[$post_id]",
            'render_callback' => [$this, 'render_post_content_partial'],
            'container_inclusive' => false,
            'fallback_refresh' => false,
        ];
        $partial = new WP_Customize_Partial($this->selective_refresh, $partial_id, $args);
        $this->assertSame($partial_id, $partial->id);
        $this->assertSame($this->selective_refresh, $partial->component);
        $this->assertSame($args['type'], $partial->type);
        $this->assertSame($args['selector'], $partial->selector);
        $this->assertSameSets($args['settings'], $partial->settings);
        $this->assertSame($args['primary_setting'], $partial->primary_setting);
        $this->assertSame($args['render_callback'], $partial->render_callback);
        $this->assertFalse($partial->container_inclusive);
        $this->assertFalse($partial->fallback_refresh);
        $this->assertStringContainsString('Lorem Ipsum', $partial->render());

        $partial = new WP_Customize_Partial(
            $this->selective_refresh,
            $partial_id,
            [
                'settings' => 'blogdescription',
            ],
        );
        $this->assertSame(['blogdescription'], $partial->settings);
        $this->assertSame('blogdescription', $partial->primary_setting);
    }

    /**
     * Test WP_Customize_Partial::id_data().
     *
     * @see WP_Customize_Partial::id_data()
     */
    public function test_id_data()
    {
        $partial = new WP_Customize_Partial($this->selective_refresh, 'foo');
        $id_data = $partial->id_data();
        $this->assertSame('foo', $id_data['base']);
        $this->assertSame([], $id_data['keys']);

        $partial = new WP_Customize_Partial($this->selective_refresh, 'bar[baz][quux]');
        $id_data = $partial->id_data();
        $this->assertSame('bar', $id_data['base']);
        $this->assertSame(['baz', 'quux'], $id_data['keys']);
    }

    /**
     * Keep track of filter calls to customize_partial_render.
     *
     * @var int
     */
    protected $count_filter_customize_partial_render = 0;

    /**
     * Keep track of filter calls to customize_partial_render_{$partial->id}.
     *
     * @var int
     */
    protected $count_filter_customize_partial_render_with_id = 0;

    /**
     * Filter customize_partial_render.
     *
     * @param string|false $rendered Content.
     * @param WP_Customize_Partial $partial Partial.
     * @param array $container_context Data.
     * @return string|false Content.
     */
    public function filter_customize_partial_render($rendered, $partial, $container_context)
    {
        $this->assertTrue(false === $rendered || is_string($rendered));
        $this->assertInstanceOf('WP_Customize_Partial', $partial);
        $this->assertIsArray($container_context);
        $this->count_filter_customize_partial_render += 1;
        return $rendered;
    }

    /**
     * Filter customize_partial_render_{$partial->id}.
     *
     * @param string|false $rendered Content.
     * @param WP_Customize_Partial $partial Partial.
     * @param array $container_context Data.
     * @return string|false Content.
     */
    public function filter_customize_partial_render_with_id($rendered, $partial, $container_context)
    {
        $this->assertSame(sprintf('customize_partial_render_%s', $partial->id), current_filter());
        $this->assertTrue(false === $rendered || is_string($rendered));
        $this->assertInstanceOf('WP_Customize_Partial', $partial);
        $this->assertIsArray($container_context);
        $this->count_filter_customize_partial_render_with_id += 1;
        return $rendered;
    }

    /**
     * Bad render_callback().
     *
     * @return string Content.
     */
    public function render_echo_and_return()
    {
        echo 'foo';
        return 'bar';
    }

    /**
     * Echo render_callback().
     */
    public function render_echo()
    {
        echo 'foo';
    }

    /**
     * Return render_callback().
     *
     * @return string Content.
     */
    public function render_return()
    {
        return 'bar';
    }

    /**
     * Test WP_Customize_Partial::render() with a bad return_callback.
     *
     * @see WP_Customize_Partial::render()
     */
    public function test_render_with_bad_callback_should_give_preference_to_return_value()
    {
        $partial = new WP_Customize_Partial(
            $this->selective_refresh,
            'foo',
            [
                'render_callback' => [$this, 'render_echo_and_return'],
            ],
        );
        $this->setExpectedIncorrectUsage('render');
        $this->assertSame('bar', $partial->render());
    }

    /**
     * Test WP_Customize_Partial::render() with a return_callback that echos.
     *
     * @see WP_Customize_Partial::render()
     */
    public function test_render_echo_callback()
    {
        $partial = new WP_Customize_Partial(
            $this->selective_refresh,
            'foo',
            [
                'render_callback' => [$this, 'render_echo'],
            ],
        );
        $count_filter_customize_partial_render = $this->count_filter_customize_partial_render;
        $count_filter_customize_partial_render_with_id = $this->count_filter_customize_partial_render_with_id;
        add_filter('customize_partial_render', [$this, 'filter_customize_partial_render'], 10, 3);
        add_filter("customize_partial_render_{$partial->id}", [$this, 'filter_customize_partial_render_with_id'], 10,
            3);
        $rendered = $partial->render();
        $this->assertSame('foo', $rendered);
        $this->assertSame($count_filter_customize_partial_render + 1, $this->count_filter_customize_partial_render);
        $this->assertSame($count_filter_customize_partial_render_with_id + 1,
            $this->count_filter_customize_partial_render_with_id);
    }

    /**
     * Test WP_Customize_Partial::render() with a return_callback that echos.
     *
     * @see WP_Customize_Partial::render()
     */
    public function test_render_return_callback()
    {
        $partial = new WP_Customize_Partial(
            $this->selective_refresh,
            'foo',
            [
                'render_callback' => [$this, 'render_return'],
            ],
        );
        $count_filter_customize_partial_render = $this->count_filter_customize_partial_render;
        $count_filter_customize_partial_render_with_id = $this->count_filter_customize_partial_render_with_id;
        add_filter('customize_partial_render', [$this, 'filter_customize_partial_render'], 10, 3);
        add_filter("customize_partial_render_{$partial->id}", [$this, 'filter_customize_partial_render_with_id'], 10,
            3);
        $rendered = $partial->render();
        $this->assertSame('bar', $rendered);
        $this->assertSame($count_filter_customize_partial_render + 1, $this->count_filter_customize_partial_render);
        $this->assertSame($count_filter_customize_partial_render_with_id + 1,
            $this->count_filter_customize_partial_render_with_id);
    }

    /**
     * Test WP_Customize_Partial::render_callback() default.
     *
     * @see WP_Customize_Partial::render_callback()
     */
    public function test_render_callback_default()
    {
        $partial = new WP_Customize_Partial($this->selective_refresh, 'foo');
        $this->assertFalse($partial->render_callback($partial, []));
        $this->assertFalse(call_user_func($partial->render_callback, $partial, []));
    }

    /**
     * Test WP_Customize_Partial::json().
     *
     * @see WP_Customize_Partial::json()
     */
    public function test_json()
    {
        $post_id = 123;
        $partial_id = sprintf('post_content[%d]', $post_id);
        $args = [
            'type' => 'post',
            'selector' => "article.post-$post_id .entry-content",
            'settings' => ['user[1]', "post[$post_id]"],
            'primary_setting' => "post[$post_id]",
            'render_callback' => [$this, 'render_post_content_partial'],
            'container_inclusive' => false,
            'fallback_refresh' => false,
        ];
        $partial = new WP_Customize_Partial($this->selective_refresh, $partial_id, $args);

        $exported = $partial->json();
        $this->assertArrayHasKey('settings', $exported);
        $this->assertArrayHasKey('primarySetting', $exported);
        $this->assertArrayHasKey('selector', $exported);
        $this->assertArrayHasKey('type', $exported);
        $this->assertArrayHasKey('fallbackRefresh', $exported);
        $this->assertArrayHasKey('containerInclusive', $exported);
    }

    /**
     * Test WP_Customize_Partial::check_capabilities().
     *
     * @see WP_Customize_Partial::check_capabilities()
     */
    public function test_check_capabilities()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        do_action('customize_register', $this->wp_customize);
        $partial = new WP_Customize_Partial(
            $this->selective_refresh,
            'blogname',
            [
                'settings' => ['blogname'],
            ],
        );
        $this->assertTrue($partial->check_capabilities());

        $partial = new WP_Customize_Partial(
            $this->selective_refresh,
            'blogname',
            [
                'settings' => ['blogname', 'non_existing'],
            ],
        );
        $this->assertFalse($partial->check_capabilities());

        $this->wp_customize->add_setting(
            'top_secret_message',
            [
                'capability' => 'top_secret_clearance',
            ],
        );
        $partial = new WP_Customize_Partial(
            $this->selective_refresh,
            'blogname',
            [
                'settings' => ['blogname', 'top_secret_clearance'],
            ],
        );
        $this->assertFalse($partial->check_capabilities());

        $partial = new WP_Customize_Partial(
            $this->selective_refresh,
            'no_setting',
            [
                'settings' => [],
            ],
        );
        $this->assertTrue($partial->check_capabilities());

        $partial = new WP_Customize_Partial(
            $this->selective_refresh,
            'no_setting',
            [
                'settings' => [],
                'capability' => 'top_secret_clearance',
            ],
        );
        $this->assertFalse($partial->check_capabilities());

        $partial = new WP_Customize_Partial(
            $this->selective_refresh,
            'no_setting',
            [
                'settings' => [],
                'capability' => 'edit_theme_options',
            ],
        );
        $this->assertTrue($partial->check_capabilities());
    }

    /**
     * Tear down.
     */
    public function tear_down()
    {
        $this->wp_customize = null;
        unset($GLOBALS['wp_customize']);
        parent::tear_down();
    }
}
