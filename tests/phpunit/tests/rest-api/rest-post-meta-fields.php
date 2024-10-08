<?php
/**
 * Unit tests covering WP_REST_Posts meta functionality.
 *
 * @package WordPress
 * @subpackage REST API
 *
 * @group restapi
 */
class WP_Test_REST_Post_Meta_Fields extends WP_Test_REST_TestCase
{
    protected static $wp_meta_keys_saved;
    protected static $post_id;
    protected static $cpt_post_id;

    public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
    {
        register_post_type(
            'cpt',
            [
                'show_in_rest' => true,
                'supports'     => ['custom-fields', 'revisions'],
            ]
        );

        self::$wp_meta_keys_saved = isset($GLOBALS['wp_meta_keys']) ? $GLOBALS['wp_meta_keys'] : [];
        self::$post_id            = $factory->post->create();
        self::$cpt_post_id        = $factory->post->create(['post_type' => 'cpt']);
    }

    public static function wpTearDownAfterClass()
    {
        $GLOBALS['wp_meta_keys'] = self::$wp_meta_keys_saved;
        wp_delete_post(self::$post_id, true);
        wp_delete_post(self::$cpt_post_id, true);

        unregister_post_type('cpt');
    }

    public function set_up()
    {
        parent::set_up();

        register_meta(
            'post',
            'test_single',
            [
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'string',
            ]
        );
        register_meta(
            'post',
            'test_multi',
            [
                'show_in_rest' => true,
                'single'       => false,
                'type'         => 'string',
            ]
        );
        register_meta(
            'post',
            'test_bad_auth',
            [
                'show_in_rest'  => true,
                'single'        => true,
                'auth_callback' => '__return_false',
                'type'          => 'string',
            ]
        );
        register_meta(
            'post',
            'test_bad_auth_multi',
            [
                'show_in_rest'  => true,
                'single'        => false,
                'auth_callback' => '__return_false',
                'type'          => 'string',
            ]
        );
        register_meta('post', 'test_no_rest', []);
        register_meta(
            'post',
            'test_rest_disabled',
            [
                'show_in_rest' => false,
                'type'         => 'string',
            ]
        );
        register_meta(
            'post',
            'test_custom_schema',
            [
                'single'       => true,
                'type'         => 'integer',
                'show_in_rest' => [
                    'schema' => [
                        'type' => 'number',
                    ],
                ],
            ]
        );
        register_meta(
            'post',
            'test_custom_schema_multi',
            [
                'single'       => false,
                'type'         => 'integer',
                'show_in_rest' => [
                    'schema' => [
                        'type' => 'number',
                    ],
                ],
            ]
        );
        register_meta(
            'post',
            'test_invalid_type',
            [
                'single'       => true,
                'type'         => 'lalala',
                'show_in_rest' => true,
            ]
        );
        register_meta(
            'post',
            'test_no_type',
            [
                'single'       => true,
                'type'         => null,
                'show_in_rest' => true,
            ]
        );

        register_meta(
            'post',
            'test_custom_name',
            [
                'single'       => true,
                'type'         => 'string',
                'show_in_rest' => [
                    'name' => 'new_name',
                ],
            ]
        );

        register_meta(
            'post',
            'test_custom_name_multi',
            [
                'single'       => false,
                'type'         => 'string',
                'show_in_rest' => [
                    'name' => 'new_name_multi',
                ],
            ]
        );

        register_post_type(
            'cpt',
            [
                'show_in_rest' => true,
                'supports'     => ['custom-fields', 'revisions'],
            ]
        );

        register_post_meta(
            'cpt',
            'test_cpt_single',
            [
                'show_in_rest' => true,
                'single'       => true,
            ]
        );

        register_post_meta(
            'cpt',
            'test_cpt_multi',
            [
                'show_in_rest' => true,
                'single'       => false,
            ]
        );

        // Register 'test_single' on subtype to override for bad auth.
        register_post_meta(
            'cpt',
            'test_single',
            [
                'show_in_rest'  => true,
                'single'        => true,
                'auth_callback' => '__return_false',
            ]
        );

        register_meta(
            'post',
            'test_boolean_update',
            [
                'single'            => true,
                'type'              => 'boolean',
                'sanitize_callback' => 'absint',
                'show_in_rest'      => true,
            ]
        );

        register_meta(
            'post',
            'test_textured_text_update',
            [
                'single'            => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'show_in_rest'      => true,
            ]
        );

        register_meta(
            'post',
            'test_json_encoded',
            [
                'single'       => true,
                'type'         => 'string',
                'show_in_rest' => true,
            ]
        );

        register_meta(
            'post',
            'test\'slashed\'key',
            [
                'single'       => true,
                'type'         => 'string',
                'show_in_rest' => true,
            ]
        );

        register_post_meta(
            'post',
            'with_default',
            [
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'default'      => 'Goodnight Moon',
            ]
        );

        register_post_meta(
            'post',
            'with_label',
            [
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'label'        => 'Meta Label',
                'default'      => '',
            ]
        );

        /** @var WP_REST_Server $wp_rest_server */
        global $wp_rest_server;
        $wp_rest_server = new Spy_REST_Server();
        do_action('rest_api_init', $wp_rest_server);
    }

    protected function grant_write_permission()
    {
        // Ensure we have write permission.
        $user = self::factory()->user->create(
            [
                'role' => 'editor',
            ]
        );
        wp_set_current_user($user);
    }

    public function test_get_value()
    {
        add_post_meta(self::$post_id, 'test_single', 'testvalue');

        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));
        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $this->assertArrayHasKey('meta', $data);

        $meta = (array) $data['meta'];
        $this->assertArrayHasKey('test_single', $meta);
        $this->assertSame('testvalue', $meta['test_single']);
    }

    /**
     * @depends test_get_value
     */
    public function test_get_multi_value()
    {
        add_post_meta(self::$post_id, 'test_multi', 'value1');
        $request = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $meta = (array) $data['meta'];
        $this->assertArrayHasKey('test_multi', $meta);
        $this->assertIsArray($meta['test_multi']);
        $this->assertContains('value1', $meta['test_multi']);

        // Check after an update.
        add_post_meta(self::$post_id, 'test_multi', 'value2');

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $meta = (array) $data['meta'];
        $this->assertContains('value1', $meta['test_multi']);
        $this->assertContains('value2', $meta['test_multi']);
    }

    /**
     * @depends test_get_value
     */
    public function test_get_unregistered()
    {
        add_post_meta(self::$post_id, 'test_unregistered', 'value1');
        $request = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $meta = (array) $data['meta'];
        $this->assertArrayNotHasKey('test_unregistered', $meta);
    }

    /**
     * @depends test_get_value
     */
    public function test_get_registered_no_api_access()
    {
        add_post_meta(self::$post_id, 'test_no_rest', 'for_the_wicked');
        $request = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $meta = (array) $data['meta'];
        $this->assertArrayNotHasKey('test_no_rest', $meta);
    }

    /**
     * @depends test_get_value
     */
    public function test_get_registered_api_disabled()
    {
        add_post_meta(self::$post_id, 'test_rest_disabled', 'sleepless_nights');
        $request = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $meta = (array) $data['meta'];
        $this->assertArrayNotHasKey('test_rest_disabled', $meta);
    }

    public function test_get_value_types()
    {
        register_meta(
            'post',
            'test_string',
            [
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'string',
            ]
        );
        register_meta(
            'post',
            'test_number',
            [
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'number',
            ]
        );
        register_meta(
            'post',
            'test_bool',
            [
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'boolean',
            ]
        );

        /** @var WP_REST_Server $wp_rest_server */
        global $wp_rest_server;
        $wp_rest_server = new Spy_REST_Server();
        do_action('rest_api_init', $wp_rest_server);

        add_post_meta(self::$post_id, 'test_string', 42);
        add_post_meta(self::$post_id, 'test_number', '42');
        add_post_meta(self::$post_id, 'test_bool', 1);

        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $meta = (array) $data['meta'];

        $this->assertArrayHasKey('test_string', $meta);
        $this->assertIsString($meta['test_string']);
        $this->assertSame('42', $meta['test_string']);

        $this->assertArrayHasKey('test_number', $meta);
        $this->assertIsFloat($meta['test_number']);
        $this->assertSame(42.0, $meta['test_number']);

        $this->assertArrayHasKey('test_bool', $meta);
        $this->assertIsBool($meta['test_bool']);
        $this->assertTrue($meta['test_bool']);
    }

    public function test_get_value_custom_name()
    {
        add_post_meta(self::$post_id, 'test_custom_name', 'janet');

        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));
        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $this->assertArrayHasKey('meta', $data);

        $meta = (array) $data['meta'];
        $this->assertArrayHasKey('new_name', $meta);
        $this->assertSame('janet', $meta['new_name']);
    }

    /**
     * @depends test_get_value
     */
    public function test_set_value()
    {
        // Ensure no data exists currently.
        $values = get_post_meta(self::$post_id, 'test_single', false);
        $this->assertEmpty($values);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_single' => 'test_value',
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_single', false);
        $this->assertNotEmpty($meta);
        $this->assertCount(1, $meta);
        $this->assertSame('test_value', $meta[0]);

        $data = $response->get_data();
        $meta = (array) $data['meta'];
        $this->assertArrayHasKey('test_single', $meta);
        $this->assertSame('test_value', $meta['test_single']);
    }

    /**
     * @depends test_get_value
     */
    public function test_set_duplicate_single_value()
    {
        // Start with an existing metakey and value.
        $values = update_post_meta(self::$post_id, 'test_single', 'test_value');
        $this->assertSame('test_value', get_post_meta(self::$post_id, 'test_single', true));

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_single' => 'test_value',
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_single', true);
        $this->assertNotEmpty($meta);
        $this->assertSame('test_value', $meta);

        $data = $response->get_data();
        $meta = (array) $data['meta'];
        $this->assertArrayHasKey('test_single', $meta);
        $this->assertSame('test_value', $meta['test_single']);
    }

    /**
     * @depends test_set_value
     */
    public function test_set_value_unauthenticated()
    {
        $data = [
            'meta' => [
                'test_single' => 'test_value',
            ],
        ];

        wp_set_current_user(0);

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertErrorResponse('rest_cannot_edit', $response, 401);

        // Check that the value wasn't actually updated.
        $this->assertEmpty(get_post_meta(self::$post_id, 'test_single', false));
    }

    /**
     * @depends test_set_value
     */
    public function test_set_value_blocked()
    {
        $data = [
            'meta' => [
                'test_bad_auth' => 'test_value',
            ],
        ];

        $this->grant_write_permission();

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertErrorResponse('rest_cannot_update', $response, 403);
        $this->assertEmpty(get_post_meta(self::$post_id, 'test_bad_auth', false));
    }

    /**
     * @depends test_set_value
     */
    public function test_set_value_db_error()
    {
        $data = [
            'meta' => [
                'test_single' => 'test_value',
            ],
        ];

        $this->grant_write_permission();

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        /**
         * Disable showing error as the below is going to intentionally
         * trigger a DB error.
         */
        global $wpdb;
        $wpdb->suppress_errors = true;
        add_filter('query', [$this, 'error_insert_query']);

        $response = rest_get_server()->dispatch($request);
        remove_filter('query', [$this, 'error_insert_query']);
        $wpdb->show_errors = true;

        $this->assertErrorResponse('rest_meta_database_error', $response, 500);
    }

    public function test_set_value_invalid_type()
    {
        $values = get_post_meta(self::$post_id, 'test_invalid_type', false);
        $this->assertEmpty($values);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_invalid_type' => 'test_value',
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertEmpty(get_post_meta(self::$post_id, 'test_invalid_type', false));
    }

    public function test_set_value_multiple()
    {
        // Ensure no data exists currently.
        $values = get_post_meta(self::$post_id, 'test_multi', false);
        $this->assertEmpty($values);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_multi' => ['val1'],
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_multi', false);
        $this->assertNotEmpty($meta);
        $this->assertCount(1, $meta);
        $this->assertSame('val1', $meta[0]);

        // Add another value.
        $data = [
            'meta' => [
                'test_multi' => ['val1', 'val2'],
            ],
        ];
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_multi', false);
        $this->assertNotEmpty($meta);
        $this->assertCount(2, $meta);
        $this->assertContains('val1', $meta);
        $this->assertContains('val2', $meta);
    }

    /**
     * Test removing only one item with duplicate items.
     */
    public function test_set_value_remove_one()
    {
        add_post_meta(self::$post_id, 'test_multi', 'c');
        add_post_meta(self::$post_id, 'test_multi', 'n');
        add_post_meta(self::$post_id, 'test_multi', 'n');

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_multi' => ['c', 'n'],
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_multi', false);
        $this->assertNotEmpty($meta);
        $this->assertCount(2, $meta);
        $this->assertContains('c', $meta);
        $this->assertContains('n', $meta);
    }

    /**
     * @depends test_set_value_multiple
     */
    public function test_set_value_multiple_unauthenticated()
    {
        // Ensure no data exists currently.
        $values = get_post_meta(self::$post_id, 'test_multi', false);
        $this->assertEmpty($values);

        wp_set_current_user(0);

        $data    = [
            'meta' => [
                'test_multi' => ['val1'],
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertErrorResponse('rest_cannot_edit', $response, 401);

        $meta = get_post_meta(self::$post_id, 'test_multi', false);
        $this->assertEmpty($meta);
    }

    public function test_set_value_invalid_value()
    {
        register_meta(
            'post',
            'my_meta_key',
            [
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'string',
            ]
        );

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'my_meta_key' => ['c', 'n'],
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertErrorResponse('rest_invalid_type', $response, 400);
    }

    public function test_set_value_invalid_value_multiple()
    {
        register_meta(
            'post',
            'my_meta_key',
            [
                'show_in_rest' => true,
                'single'       => false,
                'type'         => 'string',
            ]
        );

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'my_meta_key' => [['a']],
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertErrorResponse('rest_invalid_type', $response, 400);
    }

    public function test_set_value_sanitized()
    {
        register_meta(
            'post',
            'my_meta_key',
            [
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'integer',
            ]
        );

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'my_meta_key' => '1', // Set to a string.
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $data     = $response->get_data();
        $this->assertSame(1, $data['meta']['my_meta_key']);
    }

    public function test_set_value_csv()
    {
        register_meta(
            'post',
            'my_meta_key',
            [
                'show_in_rest' => true,
                'single'       => false,
                'type'         => 'integer',
            ]
        );

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'my_meta_key' => '1,2,3', // Set to a string.
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $data     = $response->get_data();
        $this->assertSame([1, 2, 3], $data['meta']['my_meta_key']);
    }

    /**
     * @depends test_set_value_multiple
     */
    public function test_set_value_multiple_blocked()
    {
        $data = [
            'meta' => [
                'test_bad_auth_multi' => ['test_value'],
            ],
        ];

        $this->grant_write_permission();

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertErrorResponse('rest_cannot_update', $response, 403);
        $this->assertEmpty(get_post_meta(self::$post_id, 'test_bad_auth_multi', false));
    }

    public function test_add_multi_value_db_error()
    {
        // Ensure no data exists currently.
        $values = get_post_meta(self::$post_id, 'test_multi', false);
        $this->assertEmpty($values);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_multi' => ['val1'],
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        /**
         * Disable showing error as the below is going to intentionally
         * trigger a DB error.
         */
        global $wpdb;
        $wpdb->suppress_errors = true;
        add_filter('query', [$this, 'error_insert_query']);

        $response = rest_get_server()->dispatch($request);
        remove_filter('query', [$this, 'error_insert_query']);
        $wpdb->show_errors = true;

        $this->assertErrorResponse('rest_meta_database_error', $response, 500);
    }

    /**
     * @depends test_get_value
     */
    public function test_set_value_single_custom_schema()
    {
        // Ensure no data exists currently.
        $values = get_post_meta(self::$post_id, 'test_custom_schema', false);
        $this->assertEmpty($values);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_custom_schema' => 3,
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_custom_schema', false);
        $this->assertNotEmpty($meta);
        $this->assertCount(1, $meta);
        $this->assertEquals(3, $meta[0]);

        $data = $response->get_data();
        $meta = (array) $data['meta'];
        $this->assertArrayHasKey('test_custom_schema', $meta);
        $this->assertEquals(3, $meta['test_custom_schema']);
    }

    public function test_set_value_multiple_custom_schema()
    {
        // Ensure no data exists currently.
        $values = get_post_meta(self::$post_id, 'test_custom_schema_multi', false);
        $this->assertEmpty($values);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_custom_schema_multi' => [2],
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_custom_schema_multi', false);
        $this->assertNotEmpty($meta);
        $this->assertCount(1, $meta);
        $this->assertEquals(2, $meta[0]);

        // Add another value.
        $data = [
            'meta' => [
                'test_custom_schema_multi' => [2, 8],
            ],
        ];
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_custom_schema_multi', false);
        $this->assertNotEmpty($meta);
        $this->assertCount(2, $meta);
        $this->assertContains('2', $meta);
        $this->assertContains('8', $meta);
    }

    /**
     * @depends test_get_value_custom_name
     */
    public function test_set_value_custom_name()
    {
        // Ensure no data exists currently.
        $values = get_post_meta(self::$post_id, 'test_custom_name', false);
        $this->assertEmpty($values);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'new_name' => 'janet',
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_custom_name', false);
        $this->assertNotEmpty($meta);
        $this->assertCount(1, $meta);
        $this->assertSame('janet', $meta[0]);

        $data = $response->get_data();
        $meta = (array) $data['meta'];
        $this->assertArrayHasKey('new_name', $meta);
        $this->assertSame('janet', $meta['new_name']);
    }

    public function test_set_value_custom_name_multiple()
    {
        // Ensure no data exists currently.
        $values = get_post_meta(self::$post_id, 'test_custom_name_multi', false);
        $this->assertEmpty($values);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'new_name_multi' => ['janet'],
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_custom_name_multi', false);
        $this->assertNotEmpty($meta);
        $this->assertCount(1, $meta);
        $this->assertSame('janet', $meta[0]);

        // Add another value.
        $data = [
            'meta' => [
                'new_name_multi' => ['janet', 'graeme'],
            ],
        ];
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_custom_name_multi', false);
        $this->assertNotEmpty($meta);
        $this->assertCount(2, $meta);
        $this->assertContains('janet', $meta);
        $this->assertContains('graeme', $meta);
    }

    /**
     * @ticket 38989
     */
    public function test_set_value_invalid_meta_string_request_type()
    {
        update_post_meta(self::$post_id, 'test_single', 'So I tied an onion to my belt, which was the style at the time.');
        $post_original = get_post(self::$post_id);

        $this->grant_write_permission();

        $data = [
            'title' => 'Ignore this title',
            'meta'  => 'Not an array.',
        ];

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);

        $this->assertErrorResponse('rest_invalid_param', $response, 400);

        // The meta value should not have changed.
        $current_value = get_post_meta(self::$post_id, 'test_single', true);
        $this->assertSame('So I tied an onion to my belt, which was the style at the time.', $current_value);

        // Ensure the post title update was not processed.
        $post_updated = get_post(self::$post_id);
        $this->assertSame($post_original->post_title, $post_updated->post_title);
    }

    /**
     * @ticket 38989
     */
    public function test_set_value_invalid_meta_float_request_type()
    {
        update_post_meta(self::$post_id, 'test_single', 'Now, to take the ferry cost a nickel, and in those days, nickels had pictures of bumblebees on them.');
        $post_original = get_post(self::$post_id);

        $this->grant_write_permission();

        $data = [
            'content' => 'Ignore this content.',
            'meta'    => 1.234,
        ];

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertErrorResponse('rest_invalid_param', $response, 400);

        // The meta value should not have changed.
        $current_value = get_post_meta(self::$post_id, 'test_single', true);
        $this->assertSame('Now, to take the ferry cost a nickel, and in those days, nickels had pictures of bumblebees on them.', $current_value);

        // Ensure the post content update was not processed.
        $post_updated = get_post(self::$post_id);
        $this->assertSame($post_original->post_content, $post_updated->post_content);
    }

    /**
     * @ticket 50790
     */
    public function test_remove_multi_value_with_empty_array()
    {
        add_post_meta(self::$post_id, 'test_multi', 'val1');
        $values = get_post_meta(self::$post_id, 'test_multi', false);
        $this->assertSame(['val1'], $values);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_multi' => [],
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_multi', false);
        $this->assertEmpty($meta);
    }

    /**
     * Ensure deleting non-existent meta data behaves gracefully.
     *
     * @ticket 52787
     * @dataProvider data_delete_does_not_trigger_error_if_no_meta_values
     *
     * @param array|null $delete_value Value used to delete meta data.
     */
    public function test_delete_does_not_trigger_error_if_no_meta_values($delete_value)
    {
        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_multi' => $delete_value,
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());
    }

    /**
     * Data provider for test_delete_does_not_trigger_error_if_no_meta_values().
     *
     * @return array[] Array of test parameters.
     */
    public function data_delete_does_not_trigger_error_if_no_meta_values()
    {
        return [
            [[]],
            [null],
        ];
    }

    public function test_remove_multi_value_db_error()
    {
        add_post_meta(self::$post_id, 'test_multi', 'val1');
        $values = get_post_meta(self::$post_id, 'test_multi', false);
        $this->assertSame(['val1'], $values);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_multi' => [],
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        /**
         * Disable showing error as the below is going to intentionally
         * trigger a DB error.
         */
        global $wpdb;
        $wpdb->suppress_errors = true;
        add_filter('query', [$this, 'error_delete_query']);

        $response = rest_get_server()->dispatch($request);
        remove_filter('query', [$this, 'error_delete_query']);
        $wpdb->show_errors = true;

        $this->assertErrorResponse('rest_meta_database_error', $response, 500);
    }


    public function test_delete_value()
    {
        add_post_meta(self::$post_id, 'test_single', 'val1');
        $current = get_post_meta(self::$post_id, 'test_single', true);
        $this->assertSame('val1', $current);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_single' => null,
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_single', false);
        $this->assertEmpty($meta);
    }

    /**
     * @depends test_delete_value
     */
    public function test_delete_value_blocked()
    {
        add_post_meta(self::$post_id, 'test_bad_auth', 'val1');
        $current = get_post_meta(self::$post_id, 'test_bad_auth', true);
        $this->assertSame('val1', $current);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_bad_auth' => null,
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertErrorResponse('rest_cannot_delete', $response, 403);

        $meta = get_post_meta(self::$post_id, 'test_bad_auth', true);
        $this->assertSame('val1', $meta);
    }

    /**
     * @depends test_delete_value
     */
    public function test_delete_value_db_error()
    {
        add_post_meta(self::$post_id, 'test_single', 'val1');
        $current = get_post_meta(self::$post_id, 'test_single', true);
        $this->assertSame('val1', $current);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'test_single' => null,
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);
        /**
         * Disable showing error as the below is going to intentionally
         * trigger a DB error.
         */
        global $wpdb;
        $wpdb->suppress_errors = true;
        add_filter('query', [$this, 'error_delete_query']);

        $response = rest_get_server()->dispatch($request);
        remove_filter('query', [$this, 'error_delete_query']);
        $wpdb->show_errors = true;

        $this->assertErrorResponse('rest_meta_database_error', $response, 500);
    }

    public function test_delete_value_custom_name()
    {
        add_post_meta(self::$post_id, 'test_custom_name', 'janet');
        $current = get_post_meta(self::$post_id, 'test_custom_name', true);
        $this->assertSame('janet', $current);

        $this->grant_write_permission();

        $data    = [
            'meta' => [
                'new_name' => null,
            ],
        ];
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $meta = get_post_meta(self::$post_id, 'test_custom_name', false);
        $this->assertEmpty($meta);
    }

    public function test_get_schema()
    {
        $request  = new WP_REST_Request('OPTIONS', sprintf('/wp/v2/posts/%d', self::$post_id));
        $response = rest_get_server()->dispatch($request);

        $data   = $response->get_data();
        $schema = $data['schema'];

        $this->assertArrayHasKey('meta', $schema['properties']);
        $meta_schema = $schema['properties']['meta']['properties'];

        $this->assertArrayHasKey('test_single', $meta_schema);
        $this->assertSame('string', $meta_schema['test_single']['type']);

        $this->assertArrayHasKey('test_multi', $meta_schema);
        $this->assertSame('array', $meta_schema['test_multi']['type']);
        $this->assertArrayHasKey('items', $meta_schema['test_multi']);
        $this->assertSame('string', $meta_schema['test_multi']['items']['type']);

        $this->assertArrayHasKey('test_custom_schema', $meta_schema);
        $this->assertSame('number', $meta_schema['test_custom_schema']['type']);

        $this->assertArrayNotHasKey('test_no_rest', $meta_schema);
        $this->assertArrayNotHasKey('test_rest_disabled', $meta_schema);
        $this->assertArrayNotHasKey('test_invalid_type', $meta_schema);
        $this->assertArrayNotHasKey('test_no_type', $meta_schema);
    }

    /**
     * @ticket 38323
     *
     * @dataProvider data_get_subtype_meta_value
     */
    public function test_get_subtype_meta_value($post_type, $meta_key, $single, $in_post_type)
    {
        $post_id  = self::$post_id;
        $endpoint = 'posts';
        if ('cpt' === $post_type) {
            $post_id  = self::$cpt_post_id;
            $endpoint = 'cpt';
        }

        $meta_value = 'testvalue';

        add_post_meta($post_id, $meta_key, $meta_value);

        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/%s/%d', $endpoint, $post_id));
        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();

        $this->assertArrayHasKey('meta', $data);
        $this->assertIsArray($data['meta']);

        if ($in_post_type) {
            $expected_value = $meta_value;
            if (! $single) {
                $expected_value = [$expected_value];
            }

            $this->assertArrayHasKey($meta_key, $data['meta']);
            $this->assertSame($expected_value, $data['meta'][$meta_key]);
        } else {
            $this->assertArrayNotHasKey($meta_key, $data['meta']);
        }
    }

    public function data_get_subtype_meta_value()
    {
        return [
            ['cpt', 'test_cpt_single', true, true],
            ['cpt', 'test_cpt_multi', false, true],
            ['cpt', 'test_single', true, true],
            ['cpt', 'test_multi', false, true],
            ['post', 'test_cpt_single', true, false],
            ['post', 'test_cpt_multi', false, false],
            ['post', 'test_single', true, true],
            ['post', 'test_multi', false, true],
        ];
    }

    /**
     * @ticket 38323
     *
     * @dataProvider data_set_subtype_meta_value
     */
    public function test_set_subtype_meta_value($post_type, $meta_key, $single, $in_post_type, $can_write)
    {
        $post_id  = self::$post_id;
        $endpoint = 'posts';
        if ('cpt' === $post_type) {
            $post_id  = self::$cpt_post_id;
            $endpoint = 'cpt';
        }

        $meta_value = 'value_to_set';

        $this->grant_write_permission();

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/%s/%d', $endpoint, $post_id));
        $request->set_body_params(
            [
                'meta' => [
                    $meta_key => $meta_value,
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        if (! $can_write) {
            $this->assertSame(403, $response->get_status());
            $this->assertEmpty(get_post_meta($post_id, $meta_key, $single));

            return;
        }

        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $this->assertArrayHasKey('meta', $data);
        $this->assertIsArray($data['meta']);

        if ($in_post_type) {
            $expected_value = $meta_value;
            if (! $single) {
                $expected_value = [$expected_value];
            }

            $this->assertSame($expected_value, get_post_meta($post_id, $meta_key, $single));
            $this->assertArrayHasKey($meta_key, $data['meta']);
            $this->assertSame($expected_value, $data['meta'][$meta_key]);
        } else {
            $this->assertEmpty(get_post_meta($post_id, $meta_key, $single));
            $this->assertArrayNotHasKey($meta_key, $data['meta']);
        }
    }

    public function data_set_subtype_meta_value()
    {
        $data = $this->data_get_subtype_meta_value();

        foreach ($data as $index => $dataset) {
            $can_write = true;

            // This combination is not writable because of an auth callback of '__return_false'.
            if ('cpt' === $dataset[0] && 'test_single' === $dataset[1]) {
                $can_write = false;
            }

            $data[$index][] = $can_write;
        }

        return $data;
    }

    /**
     * @ticket 42069
     *
     * @dataProvider data_update_value_return_success_with_same_value
     */
    public function test_update_value_return_success_with_same_value($meta_key, $meta_value)
    {
        $this->grant_write_permission();

        $data = [
            'meta' => [
                $meta_key => $meta_value,
            ],
        ];

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params($data);

        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());

        // Verify the returned meta value is correct.
        $data = $response->get_data();
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey($meta_key, $data['meta']);
        $this->assertSame($meta_value, $data['meta'][$meta_key]);
    }

    public function data_update_value_return_success_with_same_value()
    {
        return [
            ['test_boolean_update', false],
            ['test_boolean_update', true],
            ['test_textured_text_update', 'She said, "What about the > 10,000 penguins in the kitchen?"'],
            ['test_textured_text_update', "He's about to do something rash..."],
            ['test_json_encoded', json_encode(['foo' => 'bar'])],
            ['test\'slashed\'key', 'Hello'],
        ];
    }

    /**
     * @ticket 42069
     */
    public function test_slashed_meta_key()
    {

        add_post_meta(self::$post_id, 'test\'slashed\'key', 'Hello');

        $request = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));

        $response = rest_get_server()->dispatch($request);
        $data     = $response->get_data();

        $this->assertArrayHasKey('test\'slashed\'key', $data['meta']);
        $this->assertSame('Hello', $data['meta']['test\'slashed\'key']);
    }

    /**
     * @ticket 43392
     */
    public function test_object_single()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'object',
            [
                'single'       => true,
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            'project' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'object' => [
                        'project' => 'WordPress',
                    ],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $data     = $response->get_data();

        $this->assertArrayHasKey('object', $data['meta']);
        $this->assertArrayHasKey('project', $data['meta']['object']);
        $this->assertSame('WordPress', $data['meta']['object']['project']);

        $meta = get_post_meta(self::$post_id, 'object', true);
        $this->assertArrayHasKey('project', $meta);
        $this->assertSame('WordPress', $meta['project']);
    }

    /**
     * @ticket 43392
     */
    public function test_object_multiple()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'object',
            [
                'single'       => false,
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            'project' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'object' => [
                        [
                            'project' => 'WordPress',
                        ],
                        [
                            'project' => 'bbPress',
                        ],
                    ],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $data     = $response->get_data();

        $this->assertArrayHasKey('object', $data['meta']);
        $this->assertCount(2, $data['meta']['object']);

        $this->assertArrayHasKey('project', $data['meta']['object'][0]);
        $this->assertSame('WordPress', $data['meta']['object'][0]['project']);

        $this->assertArrayHasKey('project', $data['meta']['object'][1]);
        $this->assertSame('bbPress', $data['meta']['object'][1]['project']);

        $meta = get_post_meta(self::$post_id, 'object');

        $this->assertCount(2, $meta);

        $this->assertArrayHasKey('project', $meta[0]);
        $this->assertSame('WordPress', $meta[0]['project']);

        $this->assertArrayHasKey('project', $meta[1]);
        $this->assertSame('bbPress', $meta[1]['project']);
    }

    /**
     * @ticket 43392
     */
    public function test_array_single()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'list',
            [
                'single'       => true,
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'type'  => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'list' => ['WordPress', 'bbPress'],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $data     = $response->get_data();

        $this->assertArrayHasKey('list', $data['meta']);
        $this->assertSame(['WordPress', 'bbPress'], $data['meta']['list']);

        $meta = get_post_meta(self::$post_id, 'list', true);
        $this->assertSame(['WordPress', 'bbPress'], $meta);
    }

    /**
     * @ticket 43392
     */
    public function test_array_of_objects_multiple()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'list_of_objects',
            [
                'single'       => false,
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'version' => [
                                    'type' => 'string',
                                ],
                                'artist'  => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'list_of_objects' => [
                        // Meta 1.
                        [
                            [
                                'version' => '5.2',
                                'artist'  => 'Jaco',
                            ],
                            [
                                'version' => '5.1',
                                'artist'  => 'Betty',
                            ],
                        ],
                        // Meta 2.
                        [
                            [
                                'version' => '4.9',
                                'artist'  => 'Tipton',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $data     = $response->get_data();

        $this->assertArrayHasKey('list_of_objects', $data['meta']);
        $this->assertCount(2, $data['meta']['list_of_objects']);

        $this->assertSame(
            [
                [
                    'version' => '5.2',
                    'artist'  => 'Jaco',
                ],
                [
                    'version' => '5.1',
                    'artist'  => 'Betty',
                ],
            ],
            $data['meta']['list_of_objects'][0]
        );

        $this->assertSame(
            [
                [
                    'version' => '4.9',
                    'artist'  => 'Tipton',
                ],
            ],
            $data['meta']['list_of_objects'][1]
        );

        $meta = get_post_meta(self::$post_id, 'list_of_objects');

        $this->assertCount(2, $meta);

        $this->assertSame(
            [
                [
                    'version' => '5.2',
                    'artist'  => 'Jaco',
                ],
                [
                    'version' => '5.1',
                    'artist'  => 'Betty',
                ],
            ],
            $meta[0]
        );

        $this->assertSame(
            [
                [
                    'version' => '4.9',
                    'artist'  => 'Tipton',
                ],
            ],
            $meta[1]
        );
    }

    /**
     * @ticket 43392
     */
    public function test_php_objects_returned_as_null()
    {
        register_post_meta(
            'post',
            'object',
            [
                'single'       => true,
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            'project' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $basic          = new Basic_Object();
        $basic->project = 'WordPress';
        update_post_meta(self::$post_id, 'object', $basic);

        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));
        $response = rest_get_server()->dispatch($request);
        $data     = $response->get_data();

        $this->assertArrayHasKey('object', $data['meta']);
        $this->assertNull($data['meta']['object']);
    }

    /**
     * @ticket 43392
     */
    public function test_php_objects_returned_as_null_multiple()
    {
        register_post_meta(
            'post',
            'object',
            [
                'single'       => false,
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            'project' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $basic          = new Basic_Object();
        $basic->project = 'WordPress';
        add_post_meta(self::$post_id, 'object', ['project' => 'bbPress']);
        add_post_meta(self::$post_id, 'object', $basic);

        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));
        $response = rest_get_server()->dispatch($request);
        $data     = $response->get_data();

        $this->assertArrayHasKey('object', $data['meta']);
        $this->assertCount(2, $data['meta']['object']);
        $this->assertSame(['project' => 'bbPress'], $data['meta']['object'][0]);
        $this->assertNull($data['meta']['object'][1]);
    }

    /**
     * @ticket 43392
     */
    public function test_php_jsonserializable_object_returns_value()
    {
        require_once __DIR__ . '/../../includes/class-jsonserializable-object.php';

        register_post_meta(
            'post',
            'object',
            [
                'single'       => true,
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            'project' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        update_post_meta(self::$post_id, 'object', new JsonSerializable_Object(['project' => 'WordPress']));

        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));
        $response = rest_get_server()->dispatch($request);
        $data     = $response->get_data();

        $this->assertArrayHasKey('object', $data['meta']);
        $this->assertSame(['project' => 'WordPress'], $data['meta']['object']);
    }

    /**
     * @ticket 43392
     */
    public function test_updating_meta_to_null_for_key_with_existing_php_object_does_not_delete_meta_value()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'object',
            [
                'single'       => true,
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            'project' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $basic          = new Basic_Object();
        $basic->project = 'WordPress';
        update_post_meta(self::$post_id, 'object', $basic);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'object' => null,
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(500, $response->get_status());
    }

    /**
     * @ticket 43392
     */
    public function test_updating_non_single_meta_to_null_for_key_with_existing_php_object_does_not_set_meta_value_to_null()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'object',
            [
                'single'       => false,
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            'project' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $basic          = new Basic_Object();
        $basic->project = 'WordPress';
        add_post_meta(self::$post_id, 'object', ['project' => 'bbPress']);
        add_post_meta(self::$post_id, 'object', $basic);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'object' => [
                        ['project' => 'BuddyPress'],
                        null,
                    ],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(500, $response->get_status());
    }

    /**
     * @ticket 43392
     */
    public function test_object_rejects_additional_properties_by_default()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'object',
            [
                'single'       => true,
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            'project' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'object' => [
                        'project'     => 'BuddyPress',
                        'awesomeness' => 100,
                    ],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(400, $response->get_status());
    }

    /**
     * @ticket 43392
     */
    public function test_object_allows_additional_properties_if_explicitly_set()
    {
        $this->grant_write_permission();

        $value = [
            'project'     => 'BuddyPress',
            'awesomeness' => 100,
        ];

        register_post_meta(
            'post',
            'object',
            [
                'single'       => true,
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'                 => 'object',
                        'additionalProperties' => true,
                        'properties'           => [
                            'project' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'object' => $value,
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $this->assertSame($value, $response->get_data()['meta']['object']);

        $this->assertSame($value, get_post_meta(self::$post_id, 'object', true));
    }

    /**
     * @ticket 43392
     */
    public function test_object_allows_additional_properties_and_uses_its_schema()
    {
        $this->grant_write_permission();

        $value = [
            'project'     => 'BuddyPress',
            'awesomeness' => 'fabulous',
        ];

        register_post_meta(
            'post',
            'object',
            [
                'single'       => true,
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'                 => 'object',
                        'additionalProperties' => [
                            'type' => 'number',
                        ],
                        'properties'           => [
                            'project' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'object' => $value,
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(400, $response->get_status());
    }

    /**
     * @ticket 43392
     */
    public function test_invalid_meta_value_are_set_to_null_in_response()
    {
        register_post_meta(
            'post',
            'email',
            [
                'single'       => true,
                'type'         => 'string',
                'show_in_rest' => [
                    'schema' => [
                        'type'   => 'string',
                        'format' => 'email',
                    ],
                ],
            ]
        );

        update_post_meta(self::$post_id, 'email', 'invalid_meta_value');

        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));
        $response = rest_get_server()->dispatch($request);

        $this->assertNull($response->get_data()['meta']['email']);
    }

    /**
     * @ticket 43392
     * @ticket 48363
     * @dataProvider data_meta_values_are_not_set_to_null_in_response_if_type_safely_serializable
     */
    public function test_meta_values_are_not_set_to_null_in_response_if_type_safely_serializable($type, $stored, $expected)
    {
        register_post_meta(
            'post',
            'safe',
            [
                'single'       => true,
                'show_in_rest' => true,
                'type'         => $type,
            ]
        );

        update_post_meta(self::$post_id, 'safe', $stored);

        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));
        $response = rest_get_server()->dispatch($request);

        $this->assertSame($expected, $response->get_data()['meta']['safe']);
    }

    public function data_meta_values_are_not_set_to_null_in_response_if_type_safely_serializable()
    {
        return [
            ['boolean', 'true', true],
            ['boolean', 'false', false],
            ['boolean', '1', true],
            ['boolean', '0', false],
            ['boolean', '', false],
            ['integer', '', 0],
            ['integer', '1', 1],
            ['integer', '0', 0],
            ['number', '', 0.0],
            ['number', '1.1', 1.1],
            ['number', '0.0', 0.0],
            ['string', '', ''],
            ['string', '1', '1'],
            ['string', '0', '0'],
            ['string', 'str', 'str'],
        ];
    }

    /**
     * @ticket 43392
     */
    public function test_update_multi_meta_value_object()
    {
        register_post_meta(
            'post',
            'object',
            [
                'single'       => false,
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            'project' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        add_post_meta(
            self::$post_id,
            'object',
            [
                'project' => 'WordPress',
            ]
        );
        add_post_meta(
            self::$post_id,
            'object',
            [
                'project' => 'bbPress',
            ]
        );

        $this->grant_write_permission();

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'object' => [
                        ['project' => 'WordPress'],
                        ['project' => 'BuddyPress'],
                    ],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $this->assertArrayHasKey('object', $data['meta']);

        $this->assertCount(2, $data['meta']['object']);
        $this->assertSame(['project' => 'WordPress'], $data['meta']['object'][0]);
        $this->assertSame(['project' => 'BuddyPress'], $data['meta']['object'][1]);

        $meta = get_post_meta(self::$post_id, 'object');
        $this->assertCount(2, $meta);
        $this->assertSame(['project' => 'WordPress'], $meta[0]);
        $this->assertSame(['project' => 'BuddyPress'], $meta[1]);
    }

    /**
     * @ticket 43392
     */
    public function test_update_multi_meta_value_array()
    {
        register_post_meta(
            'post',
            'list',
            [
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'type'  => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );

        add_post_meta(self::$post_id, 'list', ['WordPress', 'bbPress']);
        add_post_meta(self::$post_id, 'list', ['WordCamp']);

        $this->grant_write_permission();

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'list' => [
                        ['WordPress', 'bbPress'],
                        ['BuddyPress'],
                    ],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $this->assertArrayHasKey('list', $data['meta']);

        $this->assertCount(2, $data['meta']['list']);
        $this->assertSame(['WordPress', 'bbPress'], $data['meta']['list'][0]);
        $this->assertSame(['BuddyPress'], $data['meta']['list'][1]);

        $meta = get_post_meta(self::$post_id, 'list');
        $this->assertCount(2, $meta);
        $this->assertSame(['WordPress', 'bbPress'], $meta[0]);
        $this->assertSame(['BuddyPress'], $meta[1]);
    }

    /**
     * @ticket 47928
     */
    public function test_update_meta_with_unchanged_array_values()
    {
        register_post_meta(
            'post',
            'list',
            [
                'single'       => true,
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'type'  => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );

        add_post_meta(self::$post_id, 'list', ['WordCamp']);

        $this->grant_write_permission();

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'list' => ['WordCamp'],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $this->assertSame(['WordCamp'], $data['meta']['list']);
    }

    /**
     * @ticket 47928
     */
    public function test_update_meta_with_unchanged_object_values()
    {
        register_post_meta(
            'post',
            'object',
            [
                'single'       => true,
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            'project' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        add_post_meta(self::$post_id, 'object', ['project' => 'WordCamp']);

        $this->grant_write_permission();

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'object' => ['project' => 'WordCamp'],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $this->assertSame(['project' => 'WordCamp'], $data['meta']['object']);
    }

    /**
     * @ticket 57745
     */
    public function test_update_meta_with_unchanged_values_and_custom_authentication()
    {
        register_post_meta(
            'post',
            'authenticated',
            [
                'single'        => true,
                'type'          => 'boolean',
                'default'       => false,
                'show_in_rest'  => true,
                'auth_callback' => '__return_false',
            ]
        );

        add_post_meta(self::$post_id, 'authenticated', false);

        $this->grant_write_permission();

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'authenticated' => false,
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $this->assertSame(false, $data['meta']['authenticated']);
    }

    /**
     * @ticket 43392
     */
    public function test_register_meta_issues_doing_it_wrong_when_show_in_rest_is_true()
    {
        $this->setExpectedIncorrectUsage('register_meta');

        $registered = register_meta(
            'post',
            'invalid_array',
            [
                'type'         => 'array',
                'show_in_rest' => true,
            ]
        );

        self::assertFalse($registered);
    }

    /**
     * @ticket 43392
     */
    public function test_register_meta_issues_doing_it_wrong_when_show_in_rest_omits_schema()
    {
        $this->setExpectedIncorrectUsage('register_meta');

        $registered = register_meta(
            'post',
            'invalid_array',
            [
                'type'         => 'array',
                'show_in_rest' => [
                    'prepare_callback' => 'rest_sanitize_value_from_schema',
                ],
            ]
        );

        self::assertFalse($registered);
    }

    /**
     * @ticket 43392
     */
    public function test_register_meta_issues_doing_it_wrong_when_show_in_rest_omits_schema_items()
    {
        $this->setExpectedIncorrectUsage('register_meta');

        $registered = register_meta(
            'post',
            'invalid_array',
            [
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'default' => ['Hi!'],
                    ],
                ],
            ]
        );

        self::assertFalse($registered);
    }

    /**
     * @ticket 48264
     */
    public function test_update_array_of_ints_meta()
    {
        $this->grant_write_permission();
        register_post_meta(
            'post',
            'items',
            [
                'single'       => true,
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'items' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
            ]
        );

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'items' => [1, 2, 3],
                ],
            ]
        );

        rest_get_server()->dispatch($request);
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
    }

    /**
     * @ticket 48264
     */
    public function test_update_array_of_ints_meta_stored_strings_are_updated()
    {
        $this->grant_write_permission();
        register_post_meta(
            'post',
            'items',
            [
                'single'       => true,
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'items' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
            ]
        );

        update_post_meta(self::$post_id, 'items', ['1', '2', '3']);
        $response = rest_get_server()->dispatch(new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id)));
        $this->assertSame([1, 2, 3], $response->get_data()['meta']['items']);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'items' => [1, 2, 3],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $this->assertSame([1, 2, 3], get_post_meta(self::$post_id, 'items', true));
    }

    /**
     * @ticket 48264
     */
    public function test_update_array_of_ints_meta_string_request_data_is_set_as_ints()
    {
        $this->grant_write_permission();
        register_post_meta(
            'post',
            'items',
            [
                'single'       => true,
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'items' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
            ]
        );

        update_post_meta(self::$post_id, 'items', [1, 2, 3]);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'items' => ['1', '2', '3'],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $this->assertSame([1, 2, 3], get_post_meta(self::$post_id, 'items', true));
    }

    /**
     * @ticket 48264
     */
    public function test_update_array_of_ints_meta_string_request_data_and_string_stored_data()
    {
        $this->grant_write_permission();
        register_post_meta(
            'post',
            'items',
            [
                'single'       => true,
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'items' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
            ]
        );

        update_post_meta(self::$post_id, 'items', ['1', '2', '3']);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'items' => ['1', '2', '3'],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $this->assertSame([1, 2, 3], get_post_meta(self::$post_id, 'items', true));
    }

    /**
     * @ticket 48264
     */
    public function test_update_array_of_bools_meta()
    {
        $this->grant_write_permission();
        register_post_meta(
            'post',
            'items',
            [
                'single'       => true,
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'items' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ]
        );

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'items' => [true, false],
                ],
            ]
        );

        rest_get_server()->dispatch($request);
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
    }

    /**
     * @ticket 48264
     */
    public function test_update_array_of_bools_meta_stored_strings_are_updated()
    {
        $this->grant_write_permission();
        register_post_meta(
            'post',
            'items',
            [
                'single'       => true,
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'items' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ]
        );

        update_post_meta(self::$post_id, 'items', ['1', '0']);

        $response = rest_get_server()->dispatch(new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id)));
        $this->assertSame([true, false], $response->get_data()['meta']['items']);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'items' => [true, false],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $this->assertSame([true, false], get_post_meta(self::$post_id, 'items', true));
    }

    /**
     * @ticket 48264
     */
    public function test_update_array_of_bools_meta_string_request_data_is_set_as_bools()
    {
        $this->grant_write_permission();
        register_post_meta(
            'post',
            'items',
            [
                'single'       => true,
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'items' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ]
        );

        update_post_meta(self::$post_id, 'items', [true, false]);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'items' => ['1', '0'],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $this->assertSame([true, false], get_post_meta(self::$post_id, 'items', true));
    }

    /**
     * @ticket 48264
     */
    public function test_update_array_of_bools_meta_string_request_data_and_string_stored_data()
    {
        $this->grant_write_permission();
        register_post_meta(
            'post',
            'items',
            [
                'single'       => true,
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'items' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ]
        );

        update_post_meta(self::$post_id, 'items', ['1', '0']);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'items' => ['1', '0'],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $this->assertSame([true, false], get_post_meta(self::$post_id, 'items', true));
    }

    /**
     * @ticket 48264
     */
    public function test_update_array_of_bools_with_string_values_stored_and_opposite_request_data()
    {
        $this->grant_write_permission();
        register_post_meta(
            'post',
            'items',
            [
                'single'       => true,
                'type'         => 'array',
                'show_in_rest' => [
                    'schema' => [
                        'items' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ]
        );

        update_post_meta(self::$post_id, 'items', ['1', '0']);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'items' => [false, true],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $this->assertSame([false, true], get_post_meta(self::$post_id, 'items', true));
    }

    /**
     * @ticket 48363
     */
    public function test_boolean_meta_update_to_false_stores_0()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'boolean',
            [
                'single'            => true,
                'type'              => 'boolean',
                'show_in_rest'      => true,
                'sanitize_callback' => static function ($value) {
                    return $value ? '1' : '0';
                },
            ]
        );

        update_post_meta(self::$post_id, 'boolean', 1);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'boolean' => false,
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $this->assertSame('0', get_post_meta(self::$post_id, 'boolean', true));
    }

    /**
     * @ticket 49339
     */
    public function test_update_multi_meta_value_handles_integer_types()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'multi_integer',
            [
                'type'         => 'integer',
                'show_in_rest' => true,
            ]
        );

        $mid1 = add_post_meta(self::$post_id, 'multi_integer', 1);
        $mid2 = add_post_meta(self::$post_id, 'multi_integer', 2);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'multi_integer' => [2, 3],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());
        $this->assertSame([2, 3], $response->get_data()['meta']['multi_integer']);

        $this->assertFalse(get_metadata_by_mid('post', $mid1));
        $this->assertNotFalse(get_metadata_by_mid('post', $mid2));
    }

    /**
     * @ticket 49339
     */
    public function test_update_multi_meta_value_handles_boolean_types()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'multi_boolean',
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'absint',
                'show_in_rest'      => true,
            ]
        );

        $mid1 = add_post_meta(self::$post_id, 'multi_boolean', 1);
        $mid2 = add_post_meta(self::$post_id, 'multi_boolean', 0);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'multi_boolean' => [0],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());
        $this->assertSameSetsWithIndex([false], $response->get_data()['meta']['multi_boolean']);

        $this->assertFalse(get_metadata_by_mid('post', $mid1));
        $this->assertNotFalse(get_metadata_by_mid('post', $mid2));
    }

    /**
     * @ticket 49339
     */
    public function test_update_multi_meta_value_handles_object_types()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'multi_object',
            [
                'type'         => 'object',
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            'a' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $mid1 = add_post_meta(self::$post_id, 'multi_object', ['a' => 'ant']);
        $mid2 = add_post_meta(self::$post_id, 'multi_object', ['a' => 'anaconda']);

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    'multi_object' => [
                        ['a' => 'anaconda'],
                        ['a' => 'alpaca'],
                    ],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());
        $this->assertSame(
            [
                ['a' => 'anaconda'],
                ['a' => 'alpaca'],
            ],
            $response->get_data()['meta']['multi_object']
        );

        $this->assertFalse(get_metadata_by_mid('post', $mid1));
        $this->assertNotFalse(get_metadata_by_mid('post', $mid2));
    }

    /**
     * @ticket 43941
     * @dataProvider data_get_default_data
     */
    public function test_get_default_value($args, $expected)
    {
        $object_type = 'post';
        $meta_key    = 'registered_key1';
        $registered  = register_meta(
            $object_type,
            $meta_key,
            $args
        );

        $this->assertTrue($registered);

        // Check for default value.
        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d', self::$post_id));
        $response = rest_get_server()->dispatch($request);

        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $this->assertArrayHasKey('meta', $data);

        $meta = (array) $data['meta'];
        $this->assertArrayHasKey($meta_key, $meta);
        $this->assertSame($expected, $meta[$meta_key]);
    }

    public function data_get_default_data()
    {
        return [
            [
                [
                    'show_in_rest' => true,
                    'single'       => true,
                    'default'      => 'wibble',
                ],
                'wibble',
            ],
            [
                [
                    'show_in_rest' => true,
                    'single'       => false,
                    'default'      => 'wibble',
                ],
                ['wibble'],
            ],
            [
                [
                    'show_in_rest'   => true,
                    'single'         => true,
                    'object_subtype' => 'post',
                    'default'        => 'wibble',
                ],
                'wibble',
            ],
            [
                [
                    'show_in_rest'   => true,
                    'single'         => false,
                    'object_subtype' => 'post',
                    'default'        => 'wibble',
                ],
                ['wibble'],
            ],
            [
                [
                    'single'       => true,
                    'show_in_rest' => [
                        'schema' => [
                            'type'       => 'object',
                            'properties' => [
                                'wibble' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                    'type'         => 'object',
                    'default'      => ['wibble' => 'dibble'],
                ],
                ['wibble' => 'dibble'],
            ],
            [
                [
                    'show_in_rest' => [
                        'schema' => [
                            'type'       => 'object',
                            'properties' => [
                                'wibble' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                    'type'         => 'object',
                    'single'       => false,
                    'default'      => ['wibble' => 'dibble'],
                ],
                [['wibble' => 'dibble']],
            ],

            [
                [
                    'show_in_rest' => [
                        'schema' => [
                            'type'  => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'single'       => true,
                    'type'         => 'array',
                    'default'      => ['dibble'],
                ],
                ['dibble'],
            ],
            [
                [
                    'show_in_rest' => [
                        'schema' => [
                            'type'  => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'single'       => false,
                    'type'         => 'array',
                    'default'      => ['dibble'],
                ],
                [['dibble']],
            ],
            'array of objects' => [
                [
                    'type'         => 'array',
                    'single'       => true,
                    'show_in_rest' => [
                        'schema' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'default'      => [
                        [
                            'name' => 'Kirk',
                        ],
                    ],
                ],
                [
                    [
                        'name' => 'Kirk',
                    ],
                ],
            ],
        ];
    }

    /**
     * @ticket 43941
     */
    public function test_set_default_in_schema()
    {
        register_post_meta(
            'post',
            'greeting',
            [
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => [
                    'schema' => [
                        'default' => 'Hello World',
                    ],
                ],
            ]
        );

        $response = rest_do_request('/wp/v2/posts/' . self::$post_id);
        $this->assertSame('Hello World', $response->get_data()['meta']['greeting']);
    }

    /**
     * @ticket 43941
     */
    public function test_default_is_added_to_schema()
    {
        $request  = new WP_REST_Request('OPTIONS', '/wp/v2/posts');
        $response = rest_do_request($request);

        $schema = $response->get_data()['schema']['properties']['meta']['properties']['with_default'];
        $this->assertArrayHasKey('default', $schema, 'Schema is expected to have the default property');
        $this->assertSame('Goodnight Moon', $schema['default'], 'Schema default is expected to be defined and contain the value of the meta default argument.');
    }

    /**
     * @ticket 61998
     */
    public function test_title_is_added_to_schema()
    {
        $request  = new WP_REST_Request('OPTIONS', '/wp/v2/posts');
        $response = rest_do_request($request);

        $schema = $response->get_data()['schema']['properties']['meta']['properties']['with_label'];

        $this->assertArrayHasKey('title', $schema, 'Schema is expected to have the title property');
        $this->assertSame('Meta Label', $schema['title'], 'Schema title is expected to be defined and contain the value of the meta label argument.');
    }

    /**
     * Ensures that REST API calls with post meta containing the default value for the
     * registered meta field stores the default value into the database.
     *
     * When the default value isn't persisted in the database, a read of the post meta
     * at some point in the future might return a different value if the code setting the
     * default changed. This ensures that once a value is intentionally saved into the
     * database that it will remain durably in future reads.
     *
     * @ticket 55600
     *
     * @dataProvider data_scalar_default_values
     *
     * @param string $type              Scalar type of default value: one of `boolean`, `integer`, `number`, or `string`.
     * @param mixed  $default_value     Appropriate default value for given type.
     * @param mixed  $alternative_value Ignored in this test.
     */
    public function test_scalar_singular_default_is_saved_to_db($type, $default_value, $alternative_value)
    {
        $this->grant_write_permission();

        $meta_key_single = "with_{$type}_default";

        register_post_meta(
            'post',
            $meta_key_single,
            [
                'type'         => $type,
                'single'       => true,
                'show_in_rest' => true,
                'default'      => $default_value,
            ]
        );

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    $meta_key_single => $default_value,
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(
            200,
            $response->get_status(),
            "API call should have returned successfully but didn't: check test setup."
        );

        $this->assertSame(
            [(string) $default_value],
            get_metadata_raw('post', self::$post_id, $meta_key_single, false),
            'Should have stored a single meta value with string-cast version of default value.'
        );
    }

    /**
     * Ensures that REST API calls with multi post meta values (containing the default)
     * for the registered meta field stores the default value into the database.
     *
     * When the default value isn't persisted in the database, a read of the post meta
     * at some point in the future might return a different value if the code setting the
     * default changed. This ensures that once a value is intentionally saved into the
     * database that it will remain durably in future reads.
     *
     * Further, the total count of stored values may be wrong if the default value
     * is culled from the results of a "multi" read.
     *
     * @ticket 55600
     *
     * @dataProvider data_scalar_default_values
     *
     * @param string $type              Scalar type of default value: one of `boolean`, `integer`, `number`, or `string`.
     * @param mixed  $default_value     Appropriate default value for given type.
     * @param mixed  $alternative_value Appropriate value for given type that doesn't match the default value.
     */
    public function test_scalar_multi_default_is_saved_to_db($type, $default_value, $alternative_value)
    {
        $this->grant_write_permission();

        $meta_key_multiple = "with_multi_{$type}_default";

        // Register non-singular post meta for type.
        register_post_meta(
            'post',
            $meta_key_multiple,
            [
                'type'         => $type,
                'single'       => false,
                'show_in_rest' => true,
                'default'      => $default_value,
            ]
        );

        // Write the default value as the sole value.
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    $meta_key_multiple => [$default_value],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(
            200,
            $response->get_status(),
            "API call should have returned successfully but didn't: check test setup."
        );

        $this->assertSame(
            [(string) $default_value],
            get_metadata_raw('post', self::$post_id, $meta_key_multiple, false),
            'Should have stored a single meta value with string-cast version of default value.'
        );

        // Write multiple values, including the default, to ensure it remains.
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    $meta_key_multiple => [
                        $default_value,
                        $alternative_value,
                    ],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(
            200,
            $response->get_status(),
            "API call should have returned successfully but didn't: check test setup."
        );

        $this->assertSame(
            [(string) $default_value, (string) $alternative_value],
            get_metadata_raw('post', self::$post_id, $meta_key_multiple, false),
            'Should have stored both the default and non-default string-cast values.'
        );
    }

    /**
     * Ensures that REST API calls with post meta containing an object as the default
     * value for the registered meta field stores the default value into the database.
     *
     * When the default value isn't persisted in the database, a read of the post meta
     * at some point in the future might return a different value if the code setting the
     * default changed. This ensures that once a value is intentionally saved into the
     * database that it will remain durably in future reads.
     *
     * @ticket 55600
     *
     * @dataProvider data_scalar_default_values
     *
     * @param string $type              Scalar type of default value: one of `boolean`, `integer`, `number`, or `string`.
     * @param mixed  $default_value     Appropriate default value for given type.
     * @param mixed  $alternative_value Ignored in this test.
     */
    public function test_object_singular_default_is_saved_to_db($type, $default_value, $alternative_value)
    {
        $this->grant_write_permission();

        $meta_key_single = "with_{$type}_default";

        // Register singular post meta for type.
        register_post_meta(
            'post',
            $meta_key_single,
            [
                'type'         => 'object',
                'single'       => true,
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            $type => ['type' => $type],
                        ],
                    ],
                ],
                'default'      => (object) [$type => $default_value],
            ]
        );

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    $meta_key_single => (object) [$type => $default_value],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(
            200,
            $response->get_status(),
            "API call should have returned successfully but didn't: check test setup."
        );

        // Objects stored into the database are read back as arrays.
        $this->assertSame(
            [[$type => $default_value]],
            get_metadata_raw('post', self::$post_id, $meta_key_single, false),
            'Should have stored a single meta value with an object representing the default value.'
        );
    }

    /**
     * Ensures that REST API calls with multi post meta values (containing an object as
     * the default) for the registered meta field stores the default value into the database.
     *
     * When the default value isn't persisted in the database, a read of the post meta
     * at some point in the future might return a different value if the code setting the
     * default changed. This ensures that once a value is intentionally saved into the
     * database that it will remain durably in future reads.
     *
     * Further, the total count of stored values may be wrong if the default value
     * is culled from the results of a "multi" read.
     *
     * @ticket 55600
     *
     * @dataProvider data_scalar_default_values
     *
     * @param string $type              Scalar type of default value: one of `boolean`, `integer`, `number`, or `string`.
     * @param mixed  $default_value     Appropriate default value for given type.
     * @param mixed  $alternative_value Appropriate value for given type that doesn't match the default value.
     */
    public function test_object_multi_default_is_saved_to_db($type, $default_value, $alternative_value)
    {
        $this->grant_write_permission();

        $meta_key_multiple = "with_multi_{$type}_default";

        // Register non-singular post meta for type.
        register_post_meta(
            'post',
            $meta_key_multiple,
            [
                'type'         => 'object',
                'single'       => false,
                'show_in_rest' => [
                    'schema' => [
                        'type'       => 'object',
                        'properties' => [
                            $type => ['type' => $type],
                        ],
                    ],
                ],
                'default'      => (object) [$type => $default_value],
            ]
        );

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    $meta_key_multiple => [(object) [$type => $default_value]],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(
            200,
            $response->get_status(),
            "API call should have returned successfully but didn't: check test setup."
        );

        // Objects stored into the database are read back as arrays.
        $this->assertSame(
            [[$type => $default_value]],
            get_metadata_raw('post', self::$post_id, $meta_key_multiple, false),
            'Should have stored a single meta value with an object representing the default value.'
        );

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    $meta_key_multiple => [
                        (object) [$type => $default_value],
                        (object) [$type => $alternative_value],
                    ],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(
            200,
            $response->get_status(),
            "API call should have returned successfully but didn't: check test setup."
        );

        // Objects stored into the database are read back as arrays.
        $this->assertSame(
            [[$type => $default_value], [$type => $alternative_value]],
            get_metadata_raw('post', self::$post_id, $meta_key_multiple, false),
            'Should have stored a single meta value with an object representing the default value.'
        );
    }

    /**
     * Ensures that REST API calls with post meta containing a list array as the default
     * value for the registered meta field stores the default value into the database.
     *
     * When the default value isn't persisted in the database, a read of the post meta
     * at some point in the future might return a different value if the code setting the
     * default changed. This ensures that once a value is intentionally saved into the
     * database that it will remain durably in future reads.
     *
     * @ticket 55600
     *
     * @dataProvider data_scalar_default_values
     *
     * @param string $type              Scalar type of default value: one of `boolean`, `integer`, `number`, or `string`.
     * @param mixed  $default_value     Appropriate default value for given type.
     * @param mixed  $alternative_value Ignored in this test.
     */
    public function test_array_singular_default_is_saved_to_db($type, $default_value, $alternative_value)
    {
        $this->grant_write_permission();

        $meta_key_single = "with_{$type}_default";

        // Register singular post meta for type.
        register_post_meta(
            'post',
            $meta_key_single,
            [
                'type'         => 'array',
                'single'       => true,
                'show_in_rest' => [
                    'schema' => [
                        'type'  => 'array',
                        'items' => [
                            'type' => $type,
                        ],
                    ],
                ],
                'default'      => $default_value,
            ]
        );

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    $meta_key_single => [$default_value],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(
            200,
            $response->get_status(),
            "API call should have returned successfully but didn't: check test setup."
        );

        $this->assertSame(
            [[$default_value]],
            get_metadata_raw('post', self::$post_id, $meta_key_single, false),
            'Should have stored a single meta value with an array containing only the default value.'
        );
    }

    /**
     * Ensures that REST API calls with multi post meta values (containing a list array as
     * the default) for the registered meta field stores the default value into the database.
     *
     * When the default value isn't persisted in the database, a read of the post meta
     * at some point in the future might return a different value if the code setting the
     * default changed. This ensures that once a value is intentionally saved into the
     * database that it will remain durably in future reads.
     *
     * Further, the total count of stored values may be wrong if the default value
     * is culled from the results of a "multi" read.
     *
     * @ticket 55600
     *
     * @dataProvider data_scalar_default_values
     *
     * @param string $type              Scalar type of default value: one of `boolean`, `integer`, `number`, or `string`.
     * @param mixed  $default_value     Appropriate default value for given type.
     * @param mixed  $alternative_value Appropriate value for given type that doesn't match the default value.
     */
    public function test_array_multi_default_is_saved_to_db($type, $default_value, $alternative_value)
    {
        $this->grant_write_permission();

        $meta_key_multiple = "with_multi_{$type}_default";

        // Register non-singular post meta for type.
        register_post_meta(
            'post',
            $meta_key_multiple,
            [
                'type'         => 'array',
                'single'       => false,
                'show_in_rest' => [
                    'schema' => [
                        'type'  => 'array',
                        'items' => [
                            'type' => $type,
                        ],
                    ],
                ],
                'default'      => $default_value,
            ]
        );

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    $meta_key_multiple => [[$default_value]],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(
            200,
            $response->get_status(),
            "API call should have returned successfully but didn't: check test setup."
        );

        $this->assertSame(
            [[$default_value]],
            get_metadata_raw('post', self::$post_id, $meta_key_multiple, false),
            'Should have stored a single meta value with an object representing the default value.'
        );

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/posts/%d', self::$post_id));
        $request->set_body_params(
            [
                'meta' => [
                    $meta_key_multiple => [
                        [$default_value],
                        [$alternative_value],
                    ],
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(
            200,
            $response->get_status(),
            "API call should have returned successfully but didn't: check test setup."
        );

        $this->assertSame(
            [[$default_value], [$alternative_value]],
            get_metadata_raw('post', self::$post_id, $meta_key_multiple, false),
            'Should have stored a single meta value with an object representing the default value.'
        );
    }

    /**
     * @ticket 48823
     */
    public function test_multiple_errors_are_returned_at_once()
    {
        $this->grant_write_permission();
        register_post_meta(
            'post',
            'error_1',
            [
                'single'       => true,
                'show_in_rest' => [
                    'schema' => [
                        'enum' => ['a', 'b'],
                    ],
                ],
            ]
        );
        register_post_meta(
            'post',
            'error_2',
            [
                'single'       => true,
                'show_in_rest' => [
                    'schema' => [
                        'minLength' => 1,
                    ],
                ],
            ]
        );

        $request = new WP_REST_Request('PUT', '/wp/v2/posts/' . self::$post_id);
        $request->set_body_params(
            [
                'meta' => [
                    'error_1' => 'c',
                    'error_2' => '',
                ],
            ]
        );
        $response = rest_do_request($request);
        $error    = $response->as_error();
        $this->assertWPError($error);
        $this->assertContains('meta.error_1 is not one of a and b.', $error->get_error_messages());
        $this->assertContains('meta.error_2 must be at least 1 character long.', $error->get_error_messages());
    }

    /**
     * Internal function used to disable an insert query which
     * will trigger a wpdb error for testing purposes.
     */
    public function error_insert_query($query)
    {
        if (strpos($query, 'INSERT') === 0) {
            $query = '],';
        }
        return $query;
    }

    /**
     * Internal function used to disable an insert query which
     * will trigger a wpdb error for testing purposes.
     */
    public function error_delete_query($query)
    {
        if (strpos($query, 'DELETE') === 0) {
            $query = '],';
        }
        return $query;
    }

    /**
     * Test that single post meta is revisioned when saving to the posts REST API endpoint.
     *
     * @ticket 20564
     */
    public function test_revisioned_single_post_meta_with_posts_endpoint()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'foo',
            [
                'single'            => true,
                'show_in_rest'      => true,
                'revisions_enabled' => true,
            ]
        );

        $post_id = self::$post_id;

        // Update the post, saving the meta.
        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', $post_id));
        $request->set_body_params(
            [
                'title' => 'Revision 1',
                'meta'  => [
                    'foo' => 'bar',
                ],
            ]
        );
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        // Get the last revision.
        $revisions   = wp_get_post_revisions($post_id, ['posts_per_page' => 1]);
        $revision_id = array_shift($revisions)->ID;

        // Check that the revisions endpoint returns the correct meta value.
        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d/revisions/%d', $post_id, $revision_id));
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertSame('bar', $response->get_data()['meta']['foo']);

        // Check that the post meta is set correctly.
        $this->assertSame('bar', get_post_meta($revision_id, 'foo', true));

        // Create two more revisions with different meta values for the foo key.
        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', $post_id));
        $request->set_body_params(
            [
                'title' => 'Revision 2',
                'meta'  => [
                    'foo' => 'baz',
                ],
            ]
        );
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        // Get the last revision.
        $revisions     = wp_get_post_revisions($post_id, ['posts_per_page' => 1]);
        $revision_id_2 = array_shift($revisions)->ID;

        // Check that the revision has the correct meta value.
        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d/revisions/%d', $post_id, $revision_id_2));
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $this->assertSame('baz', $response->get_data()['meta']['foo']);

        // Check that the post meta is set correctly.
        $this->assertSame('baz', get_post_meta($revision_id_2, 'foo', true));

        // One more revision!
        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', $post_id));
        $request->set_body_params(
            [
                'title' => 'Revision 3',
                'meta'  => [
                    'foo' => 'qux',
                ],
            ]
        );
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        // Get the last revision.
        $revisions     = wp_get_post_revisions($post_id, ['posts_per_page' => 1]);
        $revision_id_3 = array_shift($revisions)->ID;

        // Check that the revision has the correct meta value.
        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d/revisions/%d', $post_id, $revision_id_3));
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());
        $this->assertSame('qux', $response->get_data()['meta']['foo']);

        // Check that the post meta is set correctly.
        $this->assertSame('qux', get_post_meta($revision_id_3, 'foo', true));

        // Restore Revision 3 and verify the post gets the correct meta value.
        wp_restore_post_revision($revision_id_3);
        $this->assertSame('qux', get_post_meta($post_id, 'foo', true));

        // Restore Revision 2 and verify the post gets the correct meta value.
        wp_restore_post_revision($revision_id_2);
        $this->assertSame('baz', get_post_meta($post_id, 'foo', true));
    }

    /**
     * Test that multi-post meta is revisioned when saving to the posts REST API endpoint.
     *
     * @ticket 20564
     */
    public function test_revisioned_multiple_post_meta_with_posts_endpoint()
    {
        $this->grant_write_permission();

        register_post_meta(
            'post',
            'foo',
            [
                'single'            => false,
                'show_in_rest'      => true,
                'revisions_enabled' => true,
            ]
        );

        $post_id = self::$post_id;

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', $post_id));
        $request->set_body_params(
            [
                'title' => 'Revision 1',
                'meta'  => [
                    'foo' => [
                        'bar',
                        'bat',
                        'baz',
                    ],
                ],
            ]
        );
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        // Log the current post meta.
        $meta = get_post_meta($post_id);

        // Update the post.
        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', $post_id));
        $request->set_body_params(
            [
                'title' => 'Revision 1 update',
            ]
        );
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        // Get the last revision.
        $revisions     = wp_get_post_revisions($post_id, ['posts_per_page' => 1]);
        $revision_id_1 = array_shift($revisions)->ID;

        // Check that the revision has the correct meta value.
        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d/revisions/%d', $post_id, $revision_id_1));
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $this->assertSame(
            ['bar', 'bat', 'baz'],
            $response->get_data()['meta']['foo']
        );
        $this->assertSame(
            ['bar', 'bat', 'baz'],
            get_post_meta($revision_id_1, 'foo')
        );

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', $post_id));
        $request->set_body_params(
            [
                'title' => 'Revision 2',
                'meta'  => [
                    'foo' => [
                        'car',
                        'cat',
                    ],
                ],
            ]
        );
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        // Get the last revision.
        $revisions     = wp_get_post_revisions($post_id, ['posts_per_page' => 1]);
        $revision_id_2 = array_shift($revisions)->ID;

        // Check that the revision has the correct meta value.
        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d/revisions/%d', $post_id, $revision_id_2));
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $this->assertSame(
            ['car', 'cat'],
            $response->get_data()['meta']['foo']
        );
        $this->assertSame(['car', 'cat'], get_post_meta($revision_id_2, 'foo'));

        $request = new WP_REST_Request('PUT', sprintf('/wp/v2/posts/%d', $post_id));
        $request->set_body_params(
            [
                'title' => 'Revision 3',
                'meta'  => [
                    'foo' => null,
                ],
            ]
        );
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        // Get the last revision.
        $revisions     = wp_get_post_revisions($post_id, ['posts_per_page' => 1]);
        $revision_id_3 = array_shift($revisions)->ID;

        // Check that the revision has the correct meta value.
        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/posts/%d/revisions/%d', $post_id, $revision_id_3));
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $this->assertSame(
            [],
            $response->get_data()['meta']['foo']
        );
        $this->assertSame([], get_post_meta($revision_id_3, 'foo'));

        // Restore Revision 3 and verify the post gets the correct meta value.
        wp_restore_post_revision($revision_id_3);
        $this->assertSame([], get_post_meta($post_id, 'foo'));

        // Restore Revision 2 and verify the post gets the correct meta value.
        wp_restore_post_revision($revision_id_2);
        $this->assertSame(['car', 'cat'], get_post_meta($post_id, 'foo'));
    }

    /**
     * Test post meta revisions with a custom post type and the page post type.
     *
     * @group revision
     * @dataProvider data_revisioned_single_post_meta_with_posts_endpoint_page_and_cpt_data_provider
     */
    public function test_revisioned_single_post_meta_with_posts_endpoint_page_and_cpt($passed, $expected, $post_type)
    {

        $this->grant_write_permission();

        // Create the custom meta.
        register_post_meta(
            $post_type,
            'foo',
            [
                'show_in_rest'      => true,
                'revisions_enabled' => true,
                'single'            => true,
                'type'              => 'string',
            ]
        );

        // Set up a new post.
        $post_id = $this->factory->post->create(
            [
                'post_content' => 'initial content',
                'post_type'    => $post_type,
                'meta_input'   => [
                    'foo' => 'foo',
                ],
            ]
        );

        $plural_mapping = [
            'page' => 'pages',
            'cpt'  => 'cpt',
        ];
        $request        = new WP_REST_Request('GET', sprintf('/wp/v2/%s', $plural_mapping[$post_type]));

        $response = rest_get_server()->dispatch($request);

        $request = new WP_REST_Request('POST', sprintf('/wp/v2/%s/%d', $plural_mapping[$post_type], $post_id));
        $request->set_body_params(
            [
                'title' => 'Revision 1',
                'meta'  => [
                    'foo' => $passed,
                ],
            ]
        );

        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        // Update the post.
        $request = new WP_REST_Request('POST', sprintf('/wp/v2/%s/%d', $plural_mapping[$post_type], $post_id));
        $request->set_body_params(
            [
                'title' => 'Revision 1 update',
            ]
        );
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        // Get the last revision.
        $revisions = wp_get_post_revisions($post_id, ['posts_per_page' => 1]);

        $revision_id_1 = array_shift($revisions)->ID;

        // Check that the revision has the correct meta value.
        $request  = new WP_REST_Request('GET', sprintf('/wp/v2/%s/%d/revisions/%d', $plural_mapping[$post_type], $post_id, $revision_id_1));
        $response = rest_get_server()->dispatch($request);
        $this->assertSame(200, $response->get_status());

        $this->assertSame(
            $passed,
            $response->get_data()['meta']['foo']
        );

        $this->assertSame(
            [$passed],
            get_post_meta($revision_id_1, 'foo')
        );

        unregister_post_meta($post_type, 'foo');
        wp_delete_post($post_id, true);
    }

    /**
     * Provide data for the meta revision checks.
     */
    public function data_revisioned_single_post_meta_with_posts_endpoint_page_and_cpt_data_provider()
    {
        return [
            [
                'Test string',
                'Test string',
                'cpt',
            ],
            [
                'Test string',
                'Test string',
                'page',
            ],
            [
                'Test string',
                false,
                'cpt',
            ],
        ];
    }

    /**
     * Data provider.
     *
     * Provides example default values of scalar types;
     * in contrast to arrays, objects, etc...
     *
     * @return array[]
     */
    public static function data_scalar_default_values()
    {
        return [
            'boolean default' => ['boolean', true, false],
            'integer default' => ['integer', 42, 43],
            'number default'  => ['number', 42.99, 43.99],
            'string default'  => ['string', 'string', 'string2'],
        ];
    }
}
