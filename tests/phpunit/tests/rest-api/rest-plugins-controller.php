<?php

/**
 * Unit tests covering WP_REST_Plugins_Controller functionality.
 *
 * @package WP
 * @subpackage REST API
 *
 * @group restapi
 */
class WP_REST_Plugins_Controller_Test extends WP_Test_REST_Controller_Testcase
{

    const BASE = '/wp/v2/plugins';
    const PLUGIN = 'test-plugin/test-plugin';
    const PLUGIN_FILE = self::PLUGIN . '.php';

    /**
     * Subscriber user ID.
     *
     * @since 5.5.0
     *
     * @var int
     */
    private static $subscriber_id;

    /**
     * Super administrator user ID.
     *
     * @since 5.5.0
     *
     * @var int
     */
    private static $super_admin;

    /**
     * Administrator user id.
     *
     * @since 5.5.0
     *
     * @var int
     */
    private static $admin;

    /**
     * JSON decoded response from the wp.org plugin API.
     *
     * @var stdClass
     */
    private static $plugin_api_decoded_response;

    /**
     * Set up class test fixtures.
     *
     * @param WP_UnitTest_Factory $factory waggypuppy unit test factory.
     * @since 5.5.0
     *
     */
    public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
    {
        self::$subscriber_id = $factory->user->create(
            [
                'role' => 'subscriber',
            ],
        );
        self::$super_admin = $factory->user->create(
            [
                'role' => 'administrator',
            ],
        );
        self::$admin = $factory->user->create(
            [
                'role' => 'administrator',
            ],
        );

        if (is_multisite()) {
            grant_super_admin(self::$super_admin);
        }

        self::$plugin_api_decoded_response = json_decode(file_get_contents(DIR_TESTDATA
            . '/plugins/link-manager.json'));
    }

    /**
     * Clean up test fixtures.
     *
     * @since 5.5.0
     */
    public static function wpTearDownAfterClass()
    {
        self::delete_user(self::$subscriber_id);
        self::delete_user(self::$super_admin);
        self::delete_user(self::$admin);
    }

    public function tear_down()
    {
        if (file_exists(WP_PLUGIN_DIR . '/test-plugin/test-plugin.php')) {
            // Remove plugin files.
            $this->rmdir(WP_PLUGIN_DIR . '/test-plugin');
            // Delete empty directory.
            rmdir(WP_PLUGIN_DIR . '/test-plugin');
        }

        if (file_exists(DIR_TESTDATA . '/link-manager.zip')) {
            unlink(DIR_TESTDATA . '/link-manager.zip');
        }

        if (file_exists(WP_PLUGIN_DIR . '/link-manager/link-manager.php')) {
            // Remove plugin files.
            $this->rmdir(WP_PLUGIN_DIR . '/link-manager');
            // Delete empty directory.
            rmdir(WP_PLUGIN_DIR . '/link-manager');
        }

        parent::tear_down();
    }

    /**
     * @ticket 50321
     */
    public function test_register_routes()
    {
        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey(self::BASE, $routes);
        $this->assertArrayHasKey(self::BASE . '/(?P<plugin>[^.\/]+(?:\/[^.\/]+)?)', $routes);
    }

    /**
     * @ticket 50321
     */
    public function test_context_param()
    {
        // Collection.
        $request = new WP_REST_Request('OPTIONS', self::BASE);
        $response = rest_get_server()->dispatch($request);
        $data = $response->get_data();
        $this->assertSame('view', $data['endpoints'][0]['args']['context']['default']);
        $this->assertSame(['view', 'embed', 'edit'], $data['endpoints'][0]['args']['context']['enum']);
        // Single.
        $request = new WP_REST_Request('OPTIONS', self::BASE . '/' . self::PLUGIN);
        $response = rest_get_server()->dispatch($request);
        $data = $response->get_data();
        $this->assertSame('view', $data['endpoints'][0]['args']['context']['default']);
        $this->assertSame(['view', 'embed', 'edit'], $data['endpoints'][0]['args']['context']['enum']);
    }

    /**
     * @ticket 50321
     */
    public function test_get_items()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$super_admin);

        $response = rest_do_request(self::BASE);
        $this->assertSame(200, $response->get_status());

        $items = wp_list_filter($response->get_data(), ['plugin' => self::PLUGIN]);

        $this->assertCount(1, $items);
        $this->check_get_plugin_data(array_shift($items));
    }

    /**
     * @ticket 50321
     */
    public function test_get_items_search()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('GET', self::BASE);
        $request->set_query_params(['search' => 'testeroni']);
        $response = rest_do_request($request);
        $this->assertCount(0, $response->get_data());

        $request = new WP_REST_Request('GET', self::BASE);
        $request->set_query_params(['search' => 'Cool']);
        $response = rest_do_request($request);
        $this->assertCount(1, wp_list_filter($response->get_data(), ['plugin' => self::PLUGIN]));
    }

    /**
     * @ticket 50321
     */
    public function test_get_items_status()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('GET', self::BASE);
        $request->set_query_params(['status' => 'inactive']);
        $response = rest_do_request($request);
        $this->assertCount(1, wp_list_filter($response->get_data(), ['plugin' => self::PLUGIN]));

        $request = new WP_REST_Request('GET', self::BASE);
        $request->set_query_params(['status' => 'active']);
        $response = rest_do_request($request);
        $this->assertCount(0, wp_list_filter($response->get_data(), ['plugin' => self::PLUGIN]));
    }

    /**
     * @ticket 50321
     */
    public function test_get_items_status_multiple()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('GET', self::BASE);
        $request->set_query_params(['status' => ['inactive', 'active']]);
        $response = rest_do_request($request);

        $this->assertGreaterThan(0, count(wp_list_filter($response->get_data(), ['plugin' => self::PLUGIN], 'NOT')));
        $this->assertCount(1, wp_list_filter($response->get_data(), ['plugin' => self::PLUGIN]));
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_get_items_status_network_active()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('GET', self::BASE);
        $request->set_query_params(['status' => 'network-active']);
        $response = rest_do_request($request);
        $this->assertCount(0, wp_list_filter($response->get_data(), ['plugin' => self::PLUGIN]));

        activate_plugin(self::PLUGIN_FILE, '', true);
        $request = new WP_REST_Request('GET', self::BASE);
        $request->set_query_params(['status' => 'network-active']);
        $response = rest_do_request($request);
        $this->assertCount(1, wp_list_filter($response->get_data(), ['plugin' => self::PLUGIN]));
    }

    /**
     * @ticket 50321
     */
    public function test_get_items_logged_out()
    {
        $response = rest_do_request(self::BASE);
        $this->assertSame(401, $response->get_status());
    }

    /**
     * @ticket 50321
     */
    public function test_get_items_insufficient_permissions()
    {
        wp_set_current_user(self::$subscriber_id);
        $response = rest_do_request(self::BASE);
        $this->assertSame(403, $response->get_status());
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_cannot_get_items_if_plugins_menu_not_available()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$admin);

        $request = new WP_REST_Request('GET', self::BASE);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_cannot_view_plugins', $response->as_error(), 403);
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_get_items_if_plugins_menu_available()
    {
        $this->create_test_plugin();
        $this->enable_plugins_menu_item();
        wp_set_current_user(self::$admin);

        $response = rest_do_request(self::BASE);
        $this->assertSame(200, $response->get_status());
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_get_items_excludes_network_only_plugin_if_not_active()
    {
        $this->create_test_plugin(true);
        $this->enable_plugins_menu_item();
        wp_set_current_user(self::$admin);

        $response = rest_do_request(self::BASE);
        $this->assertSame(200, $response->get_status());

        $items = wp_list_filter($response->get_data(), ['plugin' => self::PLUGIN]);
        $this->assertCount(0, $items);
    }

    /**
     * @group ms-excluded
     * @ticket 50321
     */
    public function test_get_items_does_not_exclude_network_only_plugin_if_not_active_on_single_site()
    {
        $this->create_test_plugin(true);
        wp_set_current_user(self::$admin);

        $response = rest_do_request(self::BASE);
        $this->assertSame(200, $response->get_status());

        $items = wp_list_filter($response->get_data(), ['plugin' => self::PLUGIN]);
        $this->assertCount(1, $items);
        $this->check_get_plugin_data(array_shift($items), true);
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_get_items_does_not_exclude_network_only_plugin_if_not_active_but_has_network_caps()
    {
        $this->create_test_plugin(true);
        $this->enable_plugins_menu_item();
        wp_set_current_user(self::$super_admin);

        $response = rest_do_request(self::BASE);
        $this->assertSame(200, $response->get_status());

        $items = wp_list_filter($response->get_data(), ['plugin' => self::PLUGIN]);
        $this->assertCount(1, $items);
        $this->check_get_plugin_data(array_shift($items), true);
    }

    /**
     * @ticket 50321
     */
    public function test_get_item()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$super_admin);

        $response = rest_do_request(self::BASE . '/' . self::PLUGIN);
        $this->assertSame(200, $response->get_status());
        $this->check_get_plugin_data($response->get_data());
    }

    /**
     * @ticket 50321
     */
    public function test_get_item_logged_out()
    {
        $response = rest_do_request(self::BASE . '/' . self::PLUGIN);
        $this->assertSame(401, $response->get_status());
    }

    /**
     * @ticket 50321
     */
    public function test_get_item_insufficient_permissions()
    {
        wp_set_current_user(self::$subscriber_id);
        $response = rest_do_request(self::BASE . '/' . self::PLUGIN);
        $this->assertSame(403, $response->get_status());
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_cannot_get_item_if_plugins_menu_not_available()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$admin);

        $request = new WP_REST_Request('GET', self::BASE . '/' . self::PLUGIN);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_cannot_view_plugin', $response->as_error(), 403);
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_get_item_if_plugins_menu_available()
    {
        $this->create_test_plugin();
        $this->enable_plugins_menu_item();
        wp_set_current_user(self::$admin);

        $response = rest_do_request(self::BASE . '/' . self::PLUGIN);
        $this->assertSame(200, $response->get_status());
    }

    /**
     * @ticket 50321
     */
    public function test_get_item_invalid_plugin()
    {
        wp_set_current_user(self::$super_admin);
        $response = rest_do_request(self::BASE . '/' . self::PLUGIN);
        $this->assertSame(404, $response->get_status());
    }

    /**
     * @ticket 50321
     */
    public function test_create_item()
    {
        wp_set_current_user(self::$super_admin);
        $this->setup_plugin_download();

        $request = new WP_REST_Request('POST', self::BASE);
        $request->set_body_params(['slug' => 'link-manager']);

        $response = rest_do_request($request);
        $this->assertNotWPError($response->as_error());
        $this->assertSame(201, $response->get_status());
        $this->assertSame('Link Manager', $response->get_data()['name']);
    }

    /**
     * @ticket 50321
     */
    public function test_create_item_and_activate()
    {
        wp_set_current_user(self::$super_admin);
        $this->setup_plugin_download();

        $request = new WP_REST_Request('POST', self::BASE);
        $request->set_body_params(
            [
                'slug' => 'link-manager',
                'status' => 'active',
            ],
        );

        $response = rest_do_request($request);
        $this->assertNotWPError($response->as_error());
        $this->assertSame(201, $response->get_status());
        $this->assertSame('Link Manager', $response->get_data()['name']);
        $this->assertTrue(is_plugin_active('link-manager/link-manager.php'));
    }

    /**
     * @ticket 50321
     */
    public function test_create_item_and_activate_errors_if_no_permission_to_activate_plugin()
    {
        wp_set_current_user(self::$super_admin);
        $this->setup_plugin_download();
        $this->disable_activate_permission('link-manager/link-manager.php');

        $request = new WP_REST_Request('POST', self::BASE);
        $request->set_body_params(
            [
                'slug' => 'link-manager',
                'status' => 'active',
            ],
        );

        $response = rest_do_request($request);
        $this->assertErrorResponse('rest_cannot_activate_plugin', $response);
        $this->assertFalse(is_plugin_active('link-manager/link-manager.php'));
    }

    /**
     * @group ms-excluded
     * @ticket 50321
     */
    public function test_create_item_and_network_activate_rejected_if_not_multisite()
    {
        wp_set_current_user(self::$super_admin);
        $this->setup_plugin_download();

        $request = new WP_REST_Request('POST', self::BASE);
        $request->set_body_params(
            [
                'slug' => 'link-manager',
                'status' => 'network-active',
            ],
        );

        $response = rest_do_request($request);
        $this->assertErrorResponse('rest_invalid_param', $response);
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_create_item_and_network_activate()
    {
        wp_set_current_user(self::$super_admin);
        $this->setup_plugin_download();

        $request = new WP_REST_Request('POST', self::BASE);
        $request->set_body_params(
            [
                'slug' => 'link-manager',
                'status' => 'network-active',
            ],
        );

        $response = rest_do_request($request);
        $this->assertNotWPError($response->as_error());
        $this->assertSame(201, $response->get_status());
        $this->assertSame('Link Manager', $response->get_data()['name']);
        $this->assertTrue(is_plugin_active_for_network('link-manager/link-manager.php'));
    }

    /**
     * @ticket 50321
     */
    public function test_create_item_logged_out()
    {
        $request = new WP_REST_Request('POST', self::BASE);
        $request->set_body_params(['slug' => 'link-manager']);

        $response = rest_do_request($request);
        $this->assertSame(401, $response->get_status());
    }

    /**
     * @ticket 50321
     */
    public function test_create_item_insufficient_permissions()
    {
        wp_set_current_user(self::$subscriber_id);
        $request = new WP_REST_Request('POST', self::BASE);
        $request->set_body_params(['slug' => 'link-manager']);

        $response = rest_do_request($request);
        $this->assertSame(403, $response->get_status());
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_cannot_create_item_if_not_super_admin()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$admin);

        $request = new WP_REST_Request('POST', self::BASE);
        $request->set_body_params(['slug' => 'link-manager']);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_cannot_install_plugin', $response->as_error(), 403);
    }

    /**
     * @ticket 50321
     */
    public function test_create_item_wdotorg_unreachable()
    {
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('POST', self::BASE);
        $request->set_body_params(['slug' => 'foo']);

        $this->prevent_requests_to_host('api.aspirecloud.org');

        $this->expectWarning();
        $response = rest_do_request($request);
        $this->assertErrorResponse('plugins_api_failed', $response, 500);
    }

    /**
     * @ticket 50321
     */
    public function test_create_item_unknown_plugin()
    {
        wp_set_current_user(self::$super_admin);
        add_filter(
            'pre_http_request',
            static function () {
                /*
                 * Mocks the request to:
                 * https://api.aspirecloud.org/plugins/info/1.2/?action=plugin_information&request%5Bslug%5D=alex-says-this-block-definitely-doesnt-exist&request%5Bfields%5D%5Bsections%5D=0&request%5Bfields%5D%5Blanguage_packs%5D=1&request%5Blocale%5D=en_US&request%5Bwp_version%5D=5.9
                 */
                return [
                    'headers' => [],
                    'response' => [
                        'code' => 404,
                        'message' => 'Not Found',
                    ],
                    'body' => '{"error":"Plugin not found."}',
                    'cookies' => [],
                    'filename' => null,
                ];
            },
        );

        $request = new WP_REST_Request('POST', self::BASE);
        $request->set_body_params(['slug' => 'alex-says-this-block-definitely-doesnt-exist']);
        $response = rest_do_request($request);

        // Is this an appropriate status?
        $this->assertErrorResponse('plugins_api_failed', $response, 404);
    }

    /**
     * @ticket 50321
     */
    public function test_update_item()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $response = rest_do_request($request);

        $this->assertSame(200, $response->get_status());
    }

    /**
     * @ticket 50321
     */
    public function test_update_item_logged_out()
    {
        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $response = rest_do_request($request);

        $this->assertSame(401, $response->get_status());
    }

    /**
     * @ticket 50321
     */
    public function test_update_item_insufficient_permissions()
    {
        wp_set_current_user(self::$subscriber_id);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $response = rest_do_request($request);

        $this->assertSame(403, $response->get_status());
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_cannot_update_item_if_plugins_menu_not_available()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_cannot_manage_plugins', $response->as_error(), 403);
    }

    /**
     * @ticket 50321
     */
    public function test_update_item_activate_plugin()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'active']);
        $response = rest_do_request($request);

        $this->assertSame(200, $response->get_status());
        $this->assertTrue(is_plugin_active(self::PLUGIN_FILE));
    }

    /**
     * @ticket 50321
     */
    public function test_update_item_activate_plugin_fails_if_no_activate_cap()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$super_admin);
        $this->disable_activate_permission(self::PLUGIN_FILE);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'active']);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_cannot_activate_plugin', $response, 403);
    }

    /**
     * @group ms-excluded
     * @ticket 50321
     */
    public function test_update_item_network_activate_plugin_rejected_if_not_multisite()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'network-active']);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_invalid_param', $response);
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_update_item_network_activate_plugin()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'network-active']);
        $response = rest_do_request($request);

        $this->assertSame(200, $response->get_status());
        $this->assertTrue(is_plugin_active_for_network(self::PLUGIN_FILE));
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_update_item_network_activate_plugin_that_was_active_on_single_site()
    {
        $this->create_test_plugin();
        activate_plugin(self::PLUGIN_FILE);
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'network-active']);
        $response = rest_do_request($request);

        $this->assertSame(200, $response->get_status());
        $this->assertTrue(is_plugin_active_for_network(self::PLUGIN_FILE));
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_update_item_activate_network_only_plugin()
    {
        $this->create_test_plugin(true);
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'active']);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_network_only_plugin', $response, 400);
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_update_item_network_activate_network_only_plugin()
    {
        $this->create_test_plugin(true);
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'network-active']);
        $response = rest_do_request($request);

        $this->assertSame(200, $response->get_status());
        $this->assertTrue(is_plugin_active_for_network(self::PLUGIN_FILE));
    }

    /**
     * @group ms-excluded
     * @ticket 50321
     */
    public function test_update_item_activate_network_only_plugin_on_non_multisite()
    {
        $this->create_test_plugin(true);
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'active']);
        $response = rest_do_request($request);

        $this->assertSame(200, $response->get_status());
        $this->assertTrue(is_plugin_active(self::PLUGIN_FILE));
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_update_item_activate_plugin_for_site_if_menu_item_available()
    {
        $this->create_test_plugin();
        $this->enable_plugins_menu_item();
        wp_set_current_user(self::$admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'active']);
        $response = rest_do_request($request);

        $this->assertNotWPError($response->as_error());
        $this->assertSame(200, $response->get_status());
        $this->assertTrue(is_plugin_active(self::PLUGIN_FILE));
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_update_item_network_activate_plugin_for_site_if_menu_item_available()
    {
        $this->create_test_plugin();
        $this->enable_plugins_menu_item();
        wp_set_current_user(self::$admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'network-active']);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_cannot_manage_network_plugins', $response, 403);
    }

    /**
     * @ticket 50321
     */
    public function test_update_item_deactivate_plugin()
    {
        $this->create_test_plugin();
        activate_plugin(self::PLUGIN_FILE);
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'inactive']);
        $response = rest_do_request($request);

        $this->assertSame(200, $response->get_status());
        $this->assertTrue(is_plugin_inactive(self::PLUGIN_FILE));
    }

    /**
     * @ticket 50321
     */
    public function test_update_item_deactivate_plugin_fails_if_no_deactivate_cap()
    {
        $this->create_test_plugin();
        activate_plugin(self::PLUGIN_FILE);
        wp_set_current_user(self::$super_admin);
        $this->disable_deactivate_permission(self::PLUGIN_FILE);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'inactive']);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_cannot_deactivate_plugin', $response, 403);
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_update_item_deactivate_network_active_plugin()
    {
        $this->create_test_plugin();
        activate_plugin(self::PLUGIN_FILE, '', true);
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'inactive']);
        $response = rest_do_request($request);

        $this->assertSame(200, $response->get_status());
        $this->assertTrue(is_plugin_inactive(self::PLUGIN_FILE));
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_update_item_deactivate_network_active_plugin_if_not_super_admin()
    {
        $this->enable_plugins_menu_item();
        $this->create_test_plugin();
        activate_plugin(self::PLUGIN_FILE, '', true);
        wp_set_current_user(self::$admin);

        $request = new WP_REST_Request('PUT', self::BASE . '/' . self::PLUGIN);
        $request->set_body_params(['status' => 'inactive']);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_cannot_manage_network_plugins', $response, 403);
    }

    /**
     * @ticket 50321
     */
    public function test_delete_item()
    {
        $this->create_test_plugin();
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('DELETE', self::BASE . '/' . self::PLUGIN);
        $response = rest_do_request($request);

        $this->assertNotWPError($response->as_error());
        $this->assertSame(200, $response->get_status());
        $this->assertTrue($response->get_data()['deleted']);
        $this->assertSame(self::PLUGIN, $response->get_data()['previous']['plugin']);
        $this->assertFileDoesNotExist(WP_PLUGIN_DIR . '/' . self::PLUGIN_FILE);
    }

    /**
     * @ticket 50321
     */
    public function test_delete_item_logged_out()
    {
        $request = new WP_REST_Request('DELETE', self::BASE . '/' . self::PLUGIN);
        $response = rest_do_request($request);

        $this->assertSame(401, $response->get_status());
    }

    /**
     * @ticket 50321
     */
    public function test_delete_item_insufficient_permissions()
    {
        wp_set_current_user(self::$subscriber_id);

        $request = new WP_REST_Request('DELETE', self::BASE . '/' . self::PLUGIN);
        $response = rest_do_request($request);

        $this->assertSame(403, $response->get_status());
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_cannot_delete_item_if_plugins_menu_not_available()
    {
        wp_set_current_user(self::$admin);

        $request = new WP_REST_Request('DELETE', self::BASE . '/' . self::PLUGIN);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_cannot_manage_plugins', $response->as_error(), 403);
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_cannot_delete_item_if_plugins_menu_is_available()
    {
        wp_set_current_user(self::$admin);
        $this->enable_plugins_menu_item();

        $request = new WP_REST_Request('DELETE', self::BASE . '/' . self::PLUGIN);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_cannot_manage_plugins', $response->as_error(), 403);
    }

    /**
     * @ticket 50321
     */
    public function test_delete_item_active_plugin()
    {
        $this->create_test_plugin();
        activate_plugin(self::PLUGIN_FILE);
        wp_set_current_user(self::$super_admin);

        $request = new WP_REST_Request('DELETE', self::BASE . '/' . self::PLUGIN);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_cannot_delete_active_plugin', $response);
    }

    /**
     * @ticket 50321
     */
    public function test_prepare_item()
    {
        $this->create_test_plugin();

        $item = get_plugins()[self::PLUGIN_FILE];
        $item['_file'] = self::PLUGIN_FILE;

        $endpoint = new WP_REST_Plugins_Controller();
        $response = $endpoint->prepare_item_for_response($item,
            new WP_REST_Request('GET', self::BASE . '/' . self::PLUGIN));

        $this->check_get_plugin_data($response->get_data());
        $links = $response->get_links();
        $this->assertArrayHasKey('self', $links);
        $this->assertSame(rest_url(self::BASE . '/' . self::PLUGIN), $links['self'][0]['href']);
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_prepare_item_network_active()
    {
        $this->create_test_plugin();
        activate_plugin(self::PLUGIN_FILE, '', true);

        $item = get_plugins()[self::PLUGIN_FILE];
        $item['_file'] = self::PLUGIN_FILE;

        $endpoint = new WP_REST_Plugins_Controller();
        $response = $endpoint->prepare_item_for_response($item,
            new WP_REST_Request('GET', self::BASE . '/' . self::PLUGIN));

        $this->assertSame('network-active', $response->get_data()['status']);
    }

    /**
     * @group ms-required
     * @ticket 50321
     */
    public function test_prepare_item_network_only()
    {
        $this->create_test_plugin(true);

        $item = get_plugins()[self::PLUGIN_FILE];
        $item['_file'] = self::PLUGIN_FILE;

        $endpoint = new WP_REST_Plugins_Controller();
        $response = $endpoint->prepare_item_for_response($item,
            new WP_REST_Request('GET', self::BASE . '/' . self::PLUGIN));

        $this->check_get_plugin_data($response->get_data(), true);
    }

    /**
     * @ticket 50321
     */
    public function test_get_item_schema()
    {
        $request = new WP_REST_Request('OPTIONS', self::BASE);
        $response = rest_get_server()->dispatch($request);
        $data = $response->get_data();
        $properties = $data['schema']['properties'];

        $this->assertCount(12, $properties);
        $this->assertArrayHasKey('plugin', $properties);
        $this->assertArrayHasKey('status', $properties);
        $this->assertArrayHasKey('name', $properties);
        $this->assertArrayHasKey('plugin_uri', $properties);
        $this->assertArrayHasKey('description', $properties);
        $this->assertArrayHasKey('author', $properties);
        $this->assertArrayHasKey('author_uri', $properties);
        $this->assertArrayHasKey('version', $properties);
        $this->assertArrayHasKey('network_only', $properties);
        $this->assertArrayHasKey('requires_wp', $properties);
        $this->assertArrayHasKey('requires_php', $properties);
        $this->assertArrayHasKey('textdomain', $properties);
    }

    /**
     * Checks the response data.
     *
     * @param array $data Prepared plugin data.
     * @param bool $network_only Whether the plugin is network only.
     * @since 5.5.0
     *
     */
    protected function check_get_plugin_data($data, $network_only = false)
    {
        $this->assertSame('test-plugin/test-plugin', $data['plugin']);
        $this->assertSame('1.5.4', $data['version']);
        $this->assertSame('inactive', $data['status']);
        $this->assertSame('Test Plugin', $data['name']);
        $this->assertSame('https://wp.org/plugins/test-plugin/', $data['plugin_uri']);
        $this->assertSame('wp.org', $data['author']);
        $this->assertSame('https://wp.org/', $data['author_uri']);
        $this->assertSame("My 'Cool' Plugin", $data['description']['raw']);
        $this->assertSame('My &#8216;Cool&#8217; Plugin <cite>By <a href="https://wp.org/">wp.org</a>.</cite>',
            $data['description']['rendered']);
        $this->assertSame($network_only, $data['network_only']);
        $this->assertSame('5.6.0', $data['requires_php']);
        $this->assertSame('5.4', $data['requires_wp']);
        $this->assertSame('test-plugin', $data['textdomain']);
    }

    /**
     * Sets up the plugin repository requests to use local data.
     *
     * Requests to the plugin repository are mocked to avoid external HTTP requests so
     * the test suite does not produce false negatives due to network failures.
     *
     * Both the plugin ZIP file and the plugin API response are mocked.
     *
     * @since 5.5.0
     */
    protected function setup_plugin_download()
    {
        copy(DIR_TESTDATA . '/plugins/link-manager.zip', DIR_TESTDATA . '/link-manager.zip');
        add_filter(
            'upgrader_pre_download',
            static function ($reply, $package, $upgrader) {
                if ($upgrader instanceof Plugin_Upgrader) {
                    $reply = DIR_TESTDATA . '/link-manager.zip';
                }

                return $reply;
            },
            10,
            3,
        );

        add_filter(
            'plugins_api',
            function ($bypass, $action, $args) {
                // Only mock the plugin_information (link-manager) request.
                if ('plugin_information' !== $action || 'link-manager' !== $args->slug) {
                    return $bypass;
                }
                return self::$plugin_api_decoded_response;
            },
            10,
            3,
        );

        /*
         * Remove upgrade hooks which are not required for plugin installation tests
         * and may interfere with the results due to a timeout in external HTTP requests.
         */
        remove_action('upgrader_process_complete', ['Language_Pack_Upgrader', 'async_upgrade'], 20);
        remove_action('upgrader_process_complete', 'wp_version_check');
        remove_action('upgrader_process_complete', 'wp_update_plugins');
        remove_action('upgrader_process_complete', 'wp_update_themes');
    }

    /**
     * Disables permission for activating a specific plugin.
     *
     * @param string $plugin The plugin file to disable.
     * @since 5.5.0
     *
     */
    protected function disable_activate_permission($plugin)
    {
        add_filter(
            'map_meta_cap',
            static function ($caps, $cap, $user, $args) use ($plugin) {
                if ('activate_plugin' === $cap && $plugin === $args[0]) {
                    $caps = ['do_not_allow'];
                }

                return $caps;
            },
            10,
            4,
        );
    }

    /**
     * Disables permission for deactivating a specific plugin.
     *
     * @param string $plugin The plugin file to disable.
     * @since 5.5.0
     *
     */
    protected function disable_deactivate_permission($plugin)
    {
        add_filter(
            'map_meta_cap',
            static function ($caps, $cap, $user, $args) use ($plugin) {
                if ('deactivate_plugin' === $cap && $plugin === $args[0]) {
                    $caps = ['do_not_allow'];
                }

                return $caps;
            },
            10,
            4,
        );
    }

    /**
     * Enables the "plugins" as an available menu item.
     *
     * @since 5.5.0
     */
    protected function enable_plugins_menu_item()
    {
        $menu_perms = get_site_option('menu_items', []);
        $menu_perms['plugins'] = true;
        update_site_option('menu_items', $menu_perms);
    }

    /**
     * Creates a test plugin.
     *
     * @param bool $network_only Whether to make this a network only plugin.
     * @since 5.5.0
     *
     */
    private function create_test_plugin($network_only = false)
    {
        $network = $network_only ? PHP_EOL . ' * Network: true' . PHP_EOL : '';

        $php = <<<PHP
            <?php
            /*
             * Plugin Name: Test Plugin
             * Plugin URI: https://wp.org/plugins/test-plugin/
             * Description: My 'Cool' Plugin
             * Version: 1.5.4
             * Author: wp.org
             * Author URI: https://wp.org/
             * Text Domain: test-plugin
             * Requires PHP: 5.6.0
             * Requires at least: 5.4{$network}
             */
            PHP;
        wp_mkdir_p(WP_PLUGIN_DIR . '/test-plugin');
        file_put_contents(WP_PLUGIN_DIR . '/test-plugin/test-plugin.php', $php);
    }

    /**
     * Simulate a network failure on outbound http requests to a given hostname.
     *
     * @param string $blocked_host The host to block connections to.
     * @since 5.5.0
     *
     */
    private function prevent_requests_to_host($blocked_host = 'api.aspirecloud.org')
    {
        add_filter(
            'pre_http_request',
            static function ($response, $parsed_args, $url) use ($blocked_host) {
                if (@parse_url($url, PHP_URL_HOST) === $blocked_host) {
                    return new WP_Error('plugins_api_failed',
                        "An expected error occurred connecting to $blocked_host because of a unit test",
                        "cURL error 7: Failed to connect to $blocked_host port 80: Connection refused");
                }

                return $response;
            },
            10,
            3,
        );
    }
}
