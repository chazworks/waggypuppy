<?php

/**
 * Unit tests covering WP_REST_Pattern_Directory_Controller functionality.
 *
 * @package WP
 * @subpackage REST API
 *
 * @group restapi
 * @group pattern-directory
 */
class WP_REST_Pattern_Directory_Controller_Test extends WP_Test_REST_Controller_Testcase
{

    /**
     * Contributor user id.
     *
     * @since 5.8.0
     *
     * @var int
     */
    protected static $contributor_id;

    /**
     * An instance of WP_REST_Pattern_Directory_Controller class.
     *
     * @since 6.0.0
     *
     * @var WP_REST_Pattern_Directory_Controller
     */
    private static $controller;

    /**
     * List of URLs captured.
     *
     * @since 6.2.0
     *
     * @var string[]
     */
    protected static $http_request_urls;

    /**
     * Set up class test fixtures.
     *
     * @param WP_UnitTest_Factory $factory waggypuppy unit test factory.
     * @since 5.8.0
     *
     */
    public static function wpSetUpBeforeClass($factory)
    {
        self::$contributor_id = $factory->user->create(
            [
                'role' => 'contributor',
            ],
        );

        self::$http_request_urls = [];

        static::$controller = new WP_REST_Pattern_Directory_Controller();
    }

    /**
     * Tear down after class.
     *
     * @since 6.2.0
     */
    public static function wpTearDownAfterClass()
    {
        self::delete_user(self::$contributor_id);
    }

    /**
     * Clear the captured request URLs after each test.
     *
     * @since 6.2.0
     */
    public function tear_down()
    {
        self::$http_request_urls = [];
        parent::tear_down();
    }

    /**
     * Asserts that the pattern matches the expected response schema.
     *
     * @param WP_REST_Response[] $pattern An individual pattern from the REST API response.
     */
    public function assertPatternMatchesSchema($pattern)
    {
        $schema = static::$controller->get_item_schema();
        $pattern_id = isset($pattern->id) ? $pattern->id : '{pattern ID is missing}';

        $this->assertTrue(
            rest_validate_value_from_schema($pattern, $schema),
            "Pattern ID `$pattern_id` doesn't match the response schema.",
        );

        $this->assertSame(
            array_keys($schema['properties']),
            array_keys($pattern),
            "Pattern ID `$pattern_id` doesn't contain all of the fields expected from the schema.",
        );
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::register_routes
     *
     * @since 5.8.0
     */
    public function test_register_routes()
    {
        $routes = rest_get_server()->get_routes();

        $this->assertArrayHasKey('/wp/v2/pattern-directory/patterns', $routes);
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::get_context_param
     *
     * @since 5.8.0
     */
    public function test_context_param()
    {
        $request = new WP_REST_Request('OPTIONS', '/wp/v2/pattern-directory/patterns');
        $response = rest_get_server()->dispatch($request);
        $patterns = $response->get_data();

        $this->assertSame('view', $patterns['endpoints'][0]['args']['context']['default']);
        $this->assertSame(['view', 'embed', 'edit'], $patterns['endpoints'][0]['args']['context']['enum']);
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::get_items
     *
     * @since 5.8.0
     */
    public function test_get_items()
    {
        wp_set_current_user(self::$contributor_id);
        self::mock_successful_response('browse-all', true);

        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $response = rest_do_request($request);
        $patterns = $response->get_data();

        $this->assertNotWPError($response->as_error());
        $this->assertSame(200, $response->status);
        $this->assertGreaterThan(0, count($patterns));

        array_walk($patterns, [$this, 'assertPatternMatchesSchema']);
        $this->assertSame(['blog post'], $patterns[0]['keywords']);
        $this->assertSame(['header', 'hero'], $patterns[1]['keywords']);
        $this->assertSame(['call to action', 'hero section'], $patterns[2]['keywords']);
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::get_items
     *
     * @since 5.8.0
     */
    public function test_get_items_by_category()
    {
        wp_set_current_user(self::$contributor_id);
        self::mock_successful_response('browse-category', true);

        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $request->set_query_params(['category' => 2]);
        $response = rest_do_request($request);
        $patterns = $response->get_data();

        $this->assertNotWPError($response->as_error());
        $this->assertSame(200, $response->status);
        $this->assertGreaterThan(0, count($patterns));

        array_walk($patterns, [$this, 'assertPatternMatchesSchema']);

        foreach ($patterns as $pattern) {
            $this->assertContains('buttons', $pattern['categories']);
        }
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::get_items
     *
     * @since 5.8.0
     */
    public function test_get_items_by_keyword()
    {
        wp_set_current_user(self::$contributor_id);
        self::mock_successful_response('browse-keyword', true);

        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $request->set_query_params(['keyword' => 11]);
        $response = rest_do_request($request);
        $patterns = $response->get_data();

        $this->assertNotWPError($response->as_error());
        $this->assertSame(200, $response->status);
        $this->assertGreaterThan(0, count($patterns));

        array_walk($patterns, [$this, 'assertPatternMatchesSchema']);
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::get_items
     *
     * @since 5.8.0
     */
    public function test_get_items_search()
    {
        wp_set_current_user(self::$contributor_id);
        self::mock_successful_response('search', true);

        $search_term = 'button';
        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $request->set_query_params(['search' => $search_term]);
        $response = rest_do_request($request);
        $patterns = $response->get_data();

        $this->assertNotWPError($response->as_error());
        $this->assertSame(200, $response->status);
        $this->assertGreaterThan(0, count($patterns));

        array_walk($patterns, [$this, 'assertPatternMatchesSchema']);

        foreach ($patterns as $pattern) {
            $search_field_values = $pattern['title'] . ' ' . $pattern['description'];

            $this->assertStringContainsStringIgnoringCase($search_term, $search_field_values);
        }
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::get_items
     *
     * @since 5.8.0
     */
    public function test_get_items_wdotorg_unavailable()
    {
        wp_set_current_user(self::$contributor_id);
        self::prevent_requests_to_host('api.aspirecloud.org');

        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $response = rest_do_request($request);

        $this->assertErrorResponse('patterns_api_failed', $response, 500);
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::get_items
     *
     * @since 5.8.0
     */
    public function test_get_items_logged_out()
    {
        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $request->set_query_params(['search' => 'button']);
        $response = rest_do_request($request);

        $this->assertErrorResponse('rest_pattern_directory_cannot_view', $response);
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::get_items
     *
     * @since 5.8.0
     */
    public function test_get_items_no_results()
    {
        wp_set_current_user(self::$contributor_id);
        self::mock_successful_response('browse-all', false);

        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $request->set_query_params(['category' => PHP_INT_MAX]);
        $response = rest_do_request($request);
        $patterns = $response->get_data();

        $this->assertSame(200, $response->status);
        $this->assertSame([], $patterns);
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::get_items
     *
     * @since 5.8.0
     */
    public function test_get_items_search_no_results()
    {
        wp_set_current_user(self::$contributor_id);
        self::mock_successful_response('search', false);

        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $request->set_query_params(['search' => '0c4549ee68f24eaaed46a49dc983ecde']);
        $response = rest_do_request($request);
        $patterns = $response->get_data();

        $this->assertSame(200, $response->status);
        $this->assertSame([], $patterns);
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::get_items
     *
     * @since 5.8.0
     */
    public function test_get_items_invalid_response_data()
    {
        wp_set_current_user(self::$contributor_id);
        self::mock_successful_response('invalid-data', true);

        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $response = rest_do_request($request);

        $this->assertSame(500, $response->status);
        $this->assertWPError($response->as_error());
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::get_items
     *
     * @since 5.8.0
     */
    public function test_get_items_prepare_filter()
    {
        wp_set_current_user(self::$contributor_id);
        self::mock_successful_response('browse-all', true);

        // Test that filter changes uncached values.
        add_filter(
            'rest_prepare_block_pattern',
            static function ($response) {
                return 'initial value';
            },
        );

        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $response = rest_do_request($request);
        $patterns = $response->get_data();

        $this->assertSame('initial value', $patterns[0]);

        // Test that filter changes cached values (the previous request primed the cache).
        add_filter(
            'rest_prepare_block_pattern',
            static function ($response) {
                return 'modified the cache';
            },
            11,
        );

        // Test that the filter works against cached values.
        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        $response = rest_do_request($request);
        $patterns = $response->get_data();

        $this->assertSame('modified the cache', $patterns[0]);
    }

    /**
     * Tests if the provided query args are passed through to the wp.org API.
     *
     * @param string $param Query parameter name (ex, page).
     * @param mixed $value Query value to test.
     * @param bool $is_error Whether this value should error or not.
     * @param mixed $expected Expected value (or expected error code).
     * @since 6.2.0
     *
     * @ticket 57501
     *
     * @covers       WP_REST_Pattern_Directory_Controller::get_items
     *
     * @dataProvider data_get_items_query_args
     *
     */
    public function test_get_items_query_args($param, $value, $is_error, $expected)
    {
        wp_set_current_user(self::$contributor_id);
        add_filter('pre_http_request', [$this, 'mock_request_to_apiwporg_url'], 10, 3);

        $request = new WP_REST_Request('GET', '/wp/v2/pattern-directory/patterns');
        if ($value) {
            $request->set_query_params([$param => $value]);
        }

        $response = rest_do_request($request);
        $data = $response->get_data();
        if ($is_error) {
            $this->assertSame($expected, $data['code'], 'Response error code does not match');
            $this->assertStringContainsString($param, $data['message'], 'Response error message does not match');
        } else {
            $this->assertCount(1, self::$http_request_urls, 'The number of HTTP Request URLs is not 1');
            $this->assertStringContainsString($param . '=' . $expected, self::$http_request_urls[0],
                'The param and/or value do not match');
        }
    }

    /**
     * Data provider.
     *
     * return array[]
     */
    public function data_get_items_query_args()
    {
        return [
            'per_page default' => [
                'param' => 'per_page',
                'value' => false,
                'is_error' => false,
                'expected' => 100,
            ],
            'per_page custom-1' => [
                'param' => 'per_page',
                'value' => 5,
                'is_error' => false,
                'expected' => 5,
            ],
            'per_page custom-2' => [
                'param' => 'per_page',
                'value' => 50,
                'is_error' => false,
                'expected' => 50,
            ],
            'per_page invalid-1' => [
                'param' => 'per_page',
                'value' => 200,
                'is_error' => true,
                'expected' => 'rest_invalid_param',
            ],
            'per_page invalid-2' => [
                'param' => 'per_page',
                'value' => 'abc',
                'is_error' => true,
                'expected' => 'rest_invalid_param',
            ],

            'page default' => [
                'param' => 'page',
                'value' => false,
                'is_error' => false,
                'expected' => 1,
            ],
            'page custom' => [
                'param' => 'page',
                'value' => 5,
                'is_error' => false,
                'expected' => 5,
            ],
            'page invalid' => [
                'param' => 'page',
                'value' => 'abc',
                'is_error' => true,
                'expected' => 'rest_invalid_param',
            ],

            'offset custom' => [
                'param' => 'offset',
                'value' => 5,
                'is_error' => false,
                'expected' => 5,
            ],
            'offset invalid-1' => [
                'param' => 'offset',
                'value' => 'abc',
                'is_error' => true,
                'expected' => 'rest_invalid_param',
            ],

            'order default' => [
                'param' => 'order',
                'value' => false,
                'is_error' => false,
                'expected' => 'desc',
            ],
            'order custom' => [
                'param' => 'order',
                'value' => 'asc',
                'is_error' => false,
                'expected' => 'asc',
            ],
            'order invalid-1' => [
                'param' => 'order',
                'value' => 10,
                'is_error' => true,
                'expected' => 'rest_invalid_param',
            ],
            'order invalid-2' => [
                'param' => 'order',
                'value' => 'fake',
                'is_error' => true,
                'expected' => 'rest_invalid_param',
            ],

            'orderby default' => [
                'param' => 'orderby',
                'value' => false,
                'is_error' => false,
                'expected' => 'date',
            ],
            'orderby custom-1' => [
                'param' => 'orderby',
                'value' => 'title',
                'is_error' => false,
                'expected' => 'title',
            ],
            'orderby custom-2' => [
                'param' => 'orderby',
                'value' => 'date',
                'is_error' => false,
                'expected' => 'date',
            ],
            'orderby custom-3' => [
                'param' => 'orderby',
                'value' => 'favorite_count',
                'is_error' => false,
                'expected' => 'favorite_count',
            ],
            'orderby invalid-1' => [
                'param' => 'orderby',
                'value' => 10,
                'is_error' => true,
                'expected' => 'rest_invalid_param',
            ],
            'orderby invalid-2' => [
                'param' => 'orderby',
                'value' => 'fake',
                'is_error' => true,
                'expected' => 'rest_invalid_param',
            ],
        ];
    }

    /**
     * @doesNotPerformAssertions
     */
    public function test_get_item()
    {
        // Controller does not implement get_item().
    }

    /**
     * @doesNotPerformAssertions
     */
    public function test_create_item()
    {
        // Controller does not implement create_item().
    }

    /**
     * @doesNotPerformAssertions
     */
    public function test_update_item()
    {
        // Controller does not implement update_item().
    }

    /**
     * @doesNotPerformAssertions
     */
    public function test_delete_item()
    {
        // Controller does not implement delete_item().
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::prepare_item_for_response
     *
     * @since 5.8.0
     */
    public function test_prepare_item()
    {
        $raw_patterns = json_decode(self::get_raw_response('browse-all'));
        $raw_patterns[0]->extra_field = 'this should be removed';

        $prepared_pattern = static::$controller->prepare_response_for_collection(
            static::$controller->prepare_item_for_response($raw_patterns[0], new WP_REST_Request()),
        );

        $this->assertPatternMatchesSchema($prepared_pattern);
        $this->assertArrayNotHasKey('extra_field', $prepared_pattern);
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::prepare_item_for_response
     *
     * @since 5.8.0
     */
    public function test_prepare_item_search()
    {
        $raw_patterns = json_decode(self::get_raw_response('search'));
        $raw_patterns[0]->extra_field = 'this should be removed';

        $prepared_pattern = static::$controller->prepare_response_for_collection(
            static::$controller->prepare_item_for_response($raw_patterns[0], new WP_REST_Request()),
        );

        $this->assertPatternMatchesSchema($prepared_pattern);
        $this->assertArrayNotHasKey('extra_field', $prepared_pattern);
    }

    /**
     * Get a mocked raw response from api.aspirecloud.org.
     *
     * @return string
     */
    private static function get_raw_response($action)
    {
        $fixtures_dir = DIR_TESTDATA . '/blocks/pattern-directory';

        switch ($action) {
            default:
            case 'browse-all':
                // Response from https://api.aspirecloud.org/patterns/1.0/.
                $response = file_get_contents($fixtures_dir . '/browse-all.json');
                break;

            case 'browse-category':
                // Response from https://api.aspirecloud.org/patterns/1.0/?pattern-categories=2.
                $response = file_get_contents($fixtures_dir . '/browse-category-2.json');
                break;

            case 'browse-keyword':
                // Response from https://api.aspirecloud.org/patterns/1.0/?pattern-keywords=11.
                $response = file_get_contents($fixtures_dir . '/browse-keyword-11.json');
                break;

            case 'search':
                // Response from https://api.aspirecloud.org/patterns/1.0/?search=button.
                $response = file_get_contents($fixtures_dir . '/search-button.json');
                break;

            case 'invalid-data':
                $response = ''; // Any HTTP 200 response from w.org should be in JSON, even if it contains an error message.
                break;
        }

        return $response;
    }

    /**
     * @covers WP_REST_Pattern_Directory_Controller::get_item_schema
     *
     * @since 5.8.0
     *
     * @doesNotPerformAssertions
     */
    public function test_get_item_schema()
    {
        // The controller's schema is hardcoded, so tests would not be meaningful.
    }

    /**
     * Tests if the transient key gets generated correctly.
     *
     * @dataProvider data_get_query_parameters
     *
     * @covers       WP_REST_Pattern_Directory_Controller::get_transient_key
     *
     * @param array $parameters_1 Expected query arguments.
     * @param array $parameters_2 Actual query arguments.
     * @param string $message An error message to display.
     * @param bool $assert_same Assertion type (assertSame vs assertNotSame).
     * @since 6.0.0
     *
     * @ticket 55617
     *
     */
    public function test_transient_keys_get_generated_correctly(
        $parameters_1,
        $parameters_2,
        $message,
        $assert_same = true,
    ) {
        $reflection_method = new ReflectionMethod(static::$controller, 'get_transient_key');
        $reflection_method->setAccessible(true);

        $result_1 = $reflection_method->invoke(self::$controller, $parameters_1);
        $result_2 = $reflection_method->invoke(self::$controller, $parameters_2);

        $this->assertIsString($result_1, 'Transient key #1 must be a string.');
        $this->assertNotEmpty($result_1, 'Transient key #1 must not be empty.');

        $this->assertIsString($result_2, 'Transient key #2 must be a string.');
        $this->assertNotEmpty($result_2, 'Transient key #2 must not be empty.');

        if ($assert_same) {
            $this->assertSame($result_1, $result_2, $message);
        } else {
            $this->assertNotSame($result_1, $result_2, $message);
        }
    }

    /**
     * @since 6.0.0
     *
     * @ticket 55617
     */
    public function data_get_query_parameters()
    {
        return [
            'same key and empty slugs' => [
                'parameters_1' => [
                    'parameter_1' => 1,
                    'slug' => [],
                ],
                'parameters_2' => [
                    'parameter_1' => 1,
                ],
                'message' => 'Empty slugs should not affect the transient key.',
            ],
            'same key and slugs in different order' => [
                'parameters_1' => [
                    'parameter_1' => 1,
                    'slug' => [0, 2],
                ],
                'parameters_2' => [
                    'parameter_1' => 1,
                    'slug' => [2, 0],
                ],
                'message' => 'The order of slugs should not affect the transient key.',
            ],
            'same key and different slugs' => [
                'parameters_1' => [
                    'parameter_1' => 1,
                    'slug' => ['some_slug'],
                ],
                'parameters_2' => [
                    'parameter_1' => 1,
                    'slug' => ['some_other_slug'],
                ],
                'message' => 'Transient keys must not match.',
                false,
            ],
            'different keys' => [
                'parameters_1' => [
                    'parameter_1' => 1,
                ],
                'parameters_2' => [
                    'parameter_2' => 1,
                ],
                'message' => 'Transient keys must depend on array keys.',
                false,
            ],
        ];
    }

    /**
     * Simulate a successful outbound HTTP requests, to keep tests pure and performant.
     *
     * @param string $action Pass a case from `get_raw_response()` to determine returned data.
     * @param bool $expects_results Pass `true` to get results, or `false` to get 0 results.
     *
     * @since 5.8.0
     */
    private static function mock_successful_response($action, $expects_results)
    {
        add_filter(
            'pre_http_request',
            static function ($response, $parsed_args, $url) use ($action, $expects_results) {
                if ('api.aspirecloud.org' !== wp_parse_url($url, PHP_URL_HOST)) {
                    return $response;
                }

                $response = [
                    'headers' => [],
                    'response' => [
                        'code' => 200,
                        'message' => 'OK',
                    ],
                    'body' => $expects_results ? self::get_raw_response($action) : '[]',
                    'cookies' => [],
                    'filename' => null,
                ];

                return $response;
            },
            10,
            3,
        );
    }

    /**
     * Simulate a network failure on outbound http requests to a given hostname.
     *
     * @param string $blocked_host The host to block connections to.
     * @since 5.8.0
     *
     */
    private static function prevent_requests_to_host($blocked_host = 'api.aspirecloud.org')
    {
        add_filter(
            'pre_http_request',
            static function ($response, $parsed_args, $url) use ($blocked_host) {
                if (wp_parse_url($url, PHP_URL_HOST) === $blocked_host) {
                    return new WP_Error(
                        'patterns_api_failed',
                        "An expected error occurred connecting to $blocked_host because of a unit test.",
                        "cURL error 7: Failed to connect to $blocked_host port 80: Connection refused",
                    );
                }

                return $response;
            },
            10,
            3,
        );
    }

    /**
     * Mock the request to wp.org URL to capture the URLs.
     *
     * @return array faux/mocked response.
     * @since 6.2.0
     *
     */
    public function mock_request_to_apiwporg_url($response, $args, $url)
    {
        if ('api.aspirecloud.org' !== wp_parse_url($url, PHP_URL_HOST)) {
            return $response;
        }

        self::$http_request_urls[] = $url;

        // Return a response to prevent external API request.
        $response = [
            'headers' => [],
            'response' => [
                'code' => 200,
                'message' => 'OK',
            ],
            'body' => '[]',
            'cookies' => [],
            'filename' => null,
        ];

        return $response;
    }
}
