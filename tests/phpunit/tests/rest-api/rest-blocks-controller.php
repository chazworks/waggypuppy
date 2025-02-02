<?php

/**
 * Unit tests covering WP_REST_Blocks_Controller functionality.
 *
 * @package WP
 * @subpackage REST_API
 * @since 5.0.0
 *
 * @covers WP_REST_Blocks_Controller
 *
 * @group restapi-blocks
 * @group restapi
 */
class REST_Blocks_Controller_Test extends WP_UnitTestCase
{

    /**
     * Our fake block's post ID.
     *
     * @since 5.0.0
     *
     * @var int
     */
    protected static $post_id;

    /**
     * Our fake user IDs, keyed by their role.
     *
     * @since 5.0.0
     *
     * @var array
     */
    protected static $user_ids;

    /**
     * Create fake data before our tests run.
     *
     * @param WP_UnitTest_Factory $factory Helper that lets us create fake data.
     * @since 5.0.0
     *
     */
    public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
    {
        self::$post_id = wp_insert_post(
            [
                'post_type' => 'wp_block',
                'post_status' => 'publish',
                'post_title' => 'My cool block',
                'post_content' => '<!-- wp:paragraph --><p>Hello!</p><!-- /wp:paragraph -->',
            ],
        );

        self::$user_ids = [
            'editor' => $factory->user->create(['role' => 'editor']),
            'author' => $factory->user->create(['role' => 'author']),
            'contributor' => $factory->user->create(['role' => 'contributor']),
        ];
    }

    /**
     * Delete our fake data after our tests run.
     *
     * @since 5.0.0
     */
    public static function wpTearDownAfterClass()
    {
        wp_delete_post(self::$post_id);

        foreach (self::$user_ids as $user_id) {
            self::delete_user($user_id);
        }
    }

    /**
     * Test cases for test_capabilities().
     *
     * @since 5.0.0
     */
    public function data_capabilities()
    {
        return [
            ['create', 'editor', 201],
            ['create', 'author', 201],
            ['create', 'contributor', 403],
            ['create', null, 401],

            ['read', 'editor', 200],
            ['read', 'author', 200],
            ['read', 'contributor', 200],
            ['read', null, 401],

            ['update_delete_own', 'editor', 200],
            ['update_delete_own', 'author', 200],
            ['update_delete_own', 'contributor', 403],

            ['update_delete_others', 'editor', 200],
            ['update_delete_others', 'author', 403],
            ['update_delete_others', 'contributor', 403],
            ['update_delete_others', null, 401],
        ];
    }

    /**
     * Exhaustively check that each role either can or cannot create, edit,
     * update, and delete synced patterns.
     *
     * @ticket 45098
     *
     * @dataProvider data_capabilities
     *
     * @param string $action Action to perform in the test.
     * @param string $role User role to test.
     * @param int $expected_status Expected HTTP response status.
     */
    public function test_capabilities($action, $role, $expected_status)
    {
        if ($role) {
            $user_id = self::$user_ids[$role];
            wp_set_current_user($user_id);
        } else {
            wp_set_current_user(0);
        }

        switch ($action) {
            case 'create':
                $request = new WP_REST_Request('POST', '/wp/v2/blocks');
                $request->set_body_params(
                    [
                        'title' => 'Test',
                        'content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
                    ],
                );

                $response = rest_get_server()->dispatch($request);
                $this->assertSame($expected_status, $response->get_status());

                break;

            case 'read':
                $request = new WP_REST_Request('GET', '/wp/v2/blocks/' . self::$post_id);

                $response = rest_get_server()->dispatch($request);
                $this->assertSame($expected_status, $response->get_status());

                break;

            case 'update_delete_own':
                $post_id = wp_insert_post(
                    [
                        'post_type' => 'wp_block',
                        'post_status' => 'publish',
                        'post_title' => 'My cool block',
                        'post_content' => '<!-- wp:paragraph --><p>Hello!</p><!-- /wp:paragraph -->',
                        'post_author' => $user_id,
                    ],
                );

                $request = new WP_REST_Request('PUT', '/wp/v2/blocks/' . $post_id);
                $request->set_body_params(
                    [
                        'title' => 'Test',
                        'content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
                    ],
                );

                $response = rest_get_server()->dispatch($request);
                $this->assertSame($expected_status, $response->get_status());

                $request = new WP_REST_Request('DELETE', '/wp/v2/blocks/' . $post_id);

                $response = rest_get_server()->dispatch($request);
                $this->assertSame($expected_status, $response->get_status());

                wp_delete_post($post_id);

                break;

            case 'update_delete_others':
                $request = new WP_REST_Request('PUT', '/wp/v2/blocks/' . self::$post_id);
                $request->set_body_params(
                    [
                        'title' => 'Test',
                        'content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
                    ],
                );

                $response = rest_get_server()->dispatch($request);
                $this->assertSame($expected_status, $response->get_status());

                $request = new WP_REST_Request('DELETE', '/wp/v2/blocks/' . self::$post_id);

                $response = rest_get_server()->dispatch($request);
                $this->assertSame($expected_status, $response->get_status());

                break;

            default:
                $this->fail("'$action' is not a valid action.");
        }
    }

    /**
     * Check that the raw title and content of a block can be accessed when there
     * is no set schema, and that the rendered content of a block is not included
     * in the response.
     */
    public function test_content()
    {
        wp_set_current_user(self::$user_ids['author']);

        $request = new WP_REST_Request('GET', '/wp/v2/blocks/' . self::$post_id);
        $response = rest_get_server()->dispatch($request);
        $data = $response->get_data();

        $this->assertSame(
            [
                'raw' => 'My cool block',
            ],
            $data['title'],
        );
        $this->assertSame(
            [
                'raw' => '<!-- wp:paragraph --><p>Hello!</p><!-- /wp:paragraph -->',
                'protected' => false,
            ],
            $data['content'],
        );
    }

    /**
     * Check that the `wp_pattern_sync_status` postmeta is moved from meta array to top
     * level of response.
     *
     * @ticket 58677
     */
    public function test_wp_patterns_sync_status_post_meta()
    {
        register_post_meta(
            'wp_block',
            'wp_pattern_sync_status',
            [
                'single' => true,
                'type' => 'string',
                'show_in_rest' => [
                    'schema' => [
                        'type' => 'string',
                        'properties' => [
                            'sync_status' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        );
        wp_set_current_user(self::$user_ids['author']);

        $request = new WP_REST_Request('GET', '/wp/v2/blocks/' . self::$post_id);
        $response = rest_get_server()->dispatch($request);
        $data = $response->get_data();

        $this->assertArrayHasKey('wp_pattern_sync_status', $data);
        $this->assertArrayNotHasKey('wp_pattern_sync_status', $data['meta']);
    }
}
