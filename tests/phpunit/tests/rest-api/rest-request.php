<?php

/**
 * Unit tests covering WP_REST_Request functionality.
 *
 * @package WP
 * @subpackage REST API
 *
 * @group restapi
 */
class Tests_REST_Request extends WP_UnitTestCase
{
    public $request;

    public function set_up()
    {
        parent::set_up();

        $this->request = new WP_REST_Request();
    }

    /**
     * Called before setting up all tests.
     */
    public static function set_up_before_class()
    {
        parent::set_up_before_class();

        // Require files that need to load once.
        require_once DIR_TESTROOT . '/includes/mock-invokable.php';
    }

    public function test_header()
    {
        $value = 'application/x-wp-example';

        $this->request->set_header('Content-Type', $value);

        $this->assertSame($value, $this->request->get_header('Content-Type'));
    }

    public function test_header_missing()
    {
        $this->assertNull($this->request->get_header('missing'));
        $this->assertNull($this->request->get_header_as_array('missing'));
    }

    public function test_remove_header()
    {
        $this->request->add_header('Test-Header', 'value');
        $this->assertSame('value', $this->request->get_header('Test-Header'));

        $this->request->remove_header('Test-Header');
        $this->assertNull($this->request->get_header('Test-Header'));
    }

    public function test_header_multiple()
    {
        $value1 = 'application/x-wp-example-1';
        $value2 = 'application/x-wp-example-2';
        $this->request->add_header('Accept', $value1);
        $this->request->add_header('Accept', $value2);

        $this->assertSame($value1 . ',' . $value2, $this->request->get_header('Accept'));
        $this->assertSame([$value1, $value2], $this->request->get_header_as_array('Accept'));
    }

    /**
     * @dataProvider data_header_canonicalization
     * @param string $original Original header key.
     * @param string $expected Expected canonicalized version.
     */
    public function test_header_canonicalization($original, $expected)
    {
        $this->assertSame($expected, $this->request->canonicalize_header_name($original));
    }

    public static function data_header_canonicalization()
    {
        return [
            ['Test', 'test'],
            ['TEST', 'test'],
            ['Test-Header', 'test_header'],
            ['test-header', 'test_header'],
            ['Test_Header', 'test_header'],
            ['test_header', 'test_header'],
        ];
    }

    /**
     * @dataProvider data_content_type_parsing
     *
     * @param string $header Header value.
     * @param string $value Full type value.
     * @param string $type Main type (application, text, etc).
     * @param string $subtype Subtype (json, etc).
     * @param string $parameters Parameters (charset=utf-8, etc).
     */
    public function test_content_type_parsing($header, $value, $type, $subtype, $parameters)
    {
        // Check we start with nothing.
        $this->assertEmpty($this->request->get_content_type());

        $this->request->set_header('Content-Type', $header);
        $parsed = $this->request->get_content_type();

        $this->assertSame($value, $parsed['value']);
        $this->assertSame($type, $parsed['type']);
        $this->assertSame($subtype, $parsed['subtype']);
        $this->assertSame($parameters, $parsed['parameters']);
    }

    public static function data_content_type_parsing()
    {
        return [
            // Check basic parsing.
            ['application/x-wp-example', 'application/x-wp-example', 'application', 'x-wp-example', ''],
            [
                'application/x-wp-example; charset=utf-8',
                'application/x-wp-example',
                'application',
                'x-wp-example',
                'charset=utf-8',
            ],

            // Check case insensitivity.
            ['APPLICATION/x-WP-Example', 'application/x-wp-example', 'application', 'x-wp-example', ''],
        ];
    }

    protected function request_with_parameters()
    {
        $this->request->set_url_params(
            [
                'source' => 'url',
                'has_url_params' => true,
            ],
        );
        $this->request->set_query_params(
            [
                'source' => 'query',
                'has_query_params' => true,
            ],
        );
        $this->request->set_body_params(
            [
                'source' => 'body',
                'has_body_params' => true,
            ],
        );

        $json_data = wp_json_encode(
            [
                'source' => 'json',
                'has_json_params' => true,
            ],
        );
        $this->request->set_body($json_data);

        $this->request->set_default_params(
            [
                'source' => 'defaults',
                'has_default_params' => true,
            ],
        );
    }

    public function test_parameter_order()
    {
        $this->request_with_parameters();

        $this->request->set_method('GET');

        // Check that query takes precedence.
        $this->assertSame('query', $this->request->get_param('source'));

        // Check that the correct arguments are parsed (and that falling through
        // the stack works).
        $this->assertTrue($this->request->get_param('has_url_params'));
        $this->assertTrue($this->request->get_param('has_query_params'));
        $this->assertTrue($this->request->get_param('has_default_params'));

        // POST and JSON parameters shouldn't be parsed.
        $this->assertEmpty($this->request->get_param('has_body_params'));
        $this->assertEmpty($this->request->get_param('has_json_params'));
    }

    public function test_parameter_order_post()
    {
        $this->request_with_parameters();

        $this->request->set_method('POST');
        $this->request->set_header('Content-Type', 'application/x-www-form-urlencoded');
        $this->request->set_attributes(['accept_json' => true]);

        // Check that POST takes precedence.
        $this->assertSame('body', $this->request->get_param('source'));

        // Check that the correct arguments are parsed (and that falling through
        // the stack works).
        $this->assertTrue($this->request->get_param('has_url_params'));
        $this->assertTrue($this->request->get_param('has_query_params'));
        $this->assertTrue($this->request->get_param('has_body_params'));
        $this->assertTrue($this->request->get_param('has_default_params'));

        // JSON shouldn't be parsed.
        $this->assertEmpty($this->request->get_param('has_json_params'));
    }

    /**
     * @ticket 49404
     * @dataProvider data_alternate_json_content_type
     *
     * @param string $content_type The Content-Type header.
     * @param string $source The source value.
     * @param bool $accept_json The accept_json value.
     */
    public function test_alternate_json_content_type($content_type, $source, $accept_json)
    {
        $this->request_with_parameters();

        $this->request->set_method('POST');
        $this->request->set_header('Content-Type', $content_type);
        $this->request->set_attributes(['accept_json' => true]);

        // Check that JSON takes precedence.
        $this->assertSame($source, $this->request->get_param('source'));
        $this->assertEquals($accept_json, $this->request->get_param('has_json_params'));
    }

    public static function data_alternate_json_content_type()
    {
        return [
            ['application/ld+json', 'json', true],
            ['application/ld+json; profile="https://www.w3.org/ns/activitystreams"', 'json', true],
            ['application/activity+json', 'json', true],
            ['application/json+oembed', 'json', true],
            ['application/nojson', 'body', false],
            ['application/no.json', 'body', false],
        ];
    }

    /**
     * @ticket 49404
     * @dataProvider data_is_json_content_type
     *
     * @param string $content_type The Content-Type header.
     * @param bool $is_json The is_json value.
     */
    public function test_is_json_content_type($content_type, $is_json)
    {
        $this->request_with_parameters();

        $this->request->set_header('Content-Type', $content_type);

        // Check for JSON Content-Type.
        $this->assertSame($is_json, $this->request->is_json_content_type());
    }

    public static function data_is_json_content_type()
    {
        return [
            ['application/ld+json', true],
            ['application/ld+json; profile="https://www.w3.org/ns/activitystreams"', true],
            ['application/activity+json', true],
            ['application/json+oembed', true],
            ['application/nojson', false],
            ['application/no.json', false],
        ];
    }

    /**
     * @ticket 49404
     */
    public function test_content_type_cache()
    {
        $this->request_with_parameters();
        $this->assertFalse($this->request->is_json_content_type());

        $this->request->set_header('Content-Type', 'application/json');
        $this->assertTrue($this->request->is_json_content_type());

        $this->request->set_header('Content-Type', 'application/activity+json');
        $this->assertTrue($this->request->is_json_content_type());

        $this->request->set_header('Content-Type', 'application/nojson');
        $this->assertFalse($this->request->is_json_content_type());

        $this->request->set_header('Content-Type', 'application/json');
        $this->assertTrue($this->request->is_json_content_type());

        $this->request->remove_header('Content-Type');
        $this->assertFalse($this->request->is_json_content_type());
    }

    public function test_parameter_order_json()
    {
        $this->request_with_parameters();

        $this->request->set_method('POST');
        $this->request->set_header('Content-Type', 'application/json');
        $this->request->set_attributes(['accept_json' => true]);

        // Check that JSON takes precedence.
        $this->assertSame('json', $this->request->get_param('source'));

        // Check that the correct arguments are parsed (and that falling through
        // the stack works).
        $this->assertTrue($this->request->get_param('has_url_params'));
        $this->assertTrue($this->request->get_param('has_query_params'));
        $this->assertTrue($this->request->get_param('has_body_params'));
        $this->assertTrue($this->request->get_param('has_json_params'));
        $this->assertTrue($this->request->get_param('has_default_params'));
    }

    public function test_parameter_order_json_invalid()
    {
        $this->request_with_parameters();

        $this->request->set_method('POST');
        $this->request->set_header('Content-Type', 'application/json');
        $this->request->set_attributes(['accept_json' => true]);

        // Use invalid JSON data.
        $this->request->set_body('{ this is not json }');

        // Check that JSON is ignored.
        $this->assertSame('body', $this->request->get_param('source'));

        // Check that the correct arguments are parsed (and that falling through
        // the stack works).
        $this->assertTrue($this->request->get_param('has_url_params'));
        $this->assertTrue($this->request->get_param('has_query_params'));
        $this->assertTrue($this->request->get_param('has_body_params'));
        $this->assertTrue($this->request->get_param('has_default_params'));

        // JSON should be ignored.
        $this->assertEmpty($this->request->get_param('has_json_params'));
    }

    /**
     * Tests that methods supporting request bodies have access to the
     * request's body.  For POST this is straightforward via `$_POST`; for
     * other methods `WP_REST_Request` needs to parse the body for us.
     *
     * @dataProvider data_non_post_body_parameters
     */
    public function test_non_post_body_parameters($request_method)
    {
        $data = [
            'foo' => 'bar',
            'alot' => [
                'of' => 'parameters',
            ],
            'list' => [
                'of',
                'cool',
                'stuff',
            ],
        ];
        $this->request->set_method($request_method);
        $this->request->set_body_params([]);
        $this->request->set_body(http_build_query($data));
        foreach ($data as $key => $expected_value) {
            $this->assertSame($expected_value, $this->request->get_param($key));
        }
    }

    public function data_non_post_body_parameters()
    {
        return [
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
        ];
    }

    public function test_parameters_for_json_put()
    {
        $data = [
            'foo' => 'bar',
            'alot' => [
                'of' => 'parameters',
            ],
            'list' => [
                'of',
                'cool',
                'stuff',
            ],
        ];

        $this->request->set_method('PUT');
        $this->request->add_header('Content-Type', 'application/json');
        $this->request->set_body(wp_json_encode($data));

        foreach ($data as $key => $expected_value) {
            $this->assertSame($expected_value, $this->request->get_param($key));
        }
    }

    public function test_parameters_for_json_post()
    {
        $data = [
            'foo' => 'bar',
            'alot' => [
                'of' => 'parameters',
            ],
            'list' => [
                'of',
                'cool',
                'stuff',
            ],
        ];

        $this->request->set_method('POST');
        $this->request->add_header('Content-Type', 'application/json');
        $this->request->set_body(wp_json_encode($data));

        foreach ($data as $key => $expected_value) {
            $this->assertSame($expected_value, $this->request->get_param($key));
        }
    }

    public function test_parameter_merging()
    {
        $this->request_with_parameters();

        $this->request->set_method('POST');

        $expected = [
            'source' => 'body',
            'has_default_params' => true,
            'has_url_params' => true,
            'has_query_params' => true,
            'has_body_params' => true,
        ];
        $this->assertSame($expected, $this->request->get_params());
    }

    public function test_parameter_merging_with_numeric_keys()
    {
        $this->request->set_query_params(
            [
                '1' => 'hello',
                '2' => 'goodbye',
            ],
        );
        $expected = [
            '1' => 'hello',
            '2' => 'goodbye',
        ];
        $this->assertSame($expected, $this->request->get_params());
    }

    public function test_sanitize_params()
    {
        $this->request->set_url_params(
            [
                'someinteger' => '123',
                'somestring' => 'hello',
            ],
        );

        $this->request->set_attributes(
            [
                'args' => [
                    'someinteger' => [
                        'sanitize_callback' => 'absint',
                    ],
                    'somestring' => [
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        );

        $this->request->sanitize_params();

        $this->assertSame(123, $this->request->get_param('someinteger'));
        $this->assertSame(0, $this->request->get_param('somestring'));
    }

    public function test_sanitize_params_error()
    {
        $this->request->set_url_params(
            [
                'successparam' => '123',
                'failparam' => '123',
            ],
        );
        $this->request->set_attributes(
            [
                'args' => [
                    'successparam' => [
                        'sanitize_callback' => 'absint',
                    ],
                    'failparam' => [
                        'sanitize_callback' => [$this, '_return_wp_error_on_validate_callback'],
                    ],
                ],
            ],
        );

        $valid = $this->request->sanitize_params();
        $this->assertWPError($valid);
        $this->assertSame('rest_invalid_param', $valid->get_error_code());
    }

    /**
     * @ticket 46191
     */
    public function test_sanitize_params_error_multiple_messages()
    {
        $this->request->set_url_params(
            [
                'failparam' => '123',
            ],
        );
        $this->request->set_attributes(
            [
                'args' => [
                    'failparam' => [
                        'sanitize_callback' => static function () {
                            $error = new WP_Error('invalid', 'Invalid.');
                            $error->add('invalid', 'Super Invalid.');
                            $error->add('broken', 'Broken.');

                            return $error;
                        },
                    ],
                ],
            ],
        );

        $valid = $this->request->sanitize_params();
        $this->assertWPError($valid);
        $data = $valid->get_error_data();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('params', $data);
        $this->assertArrayHasKey('failparam', $data['params']);
        $this->assertSame('Invalid. Super Invalid. Broken.', $data['params']['failparam']);
    }

    /**
     * @ticket 46191
     */
    public function test_sanitize_params_provides_detailed_errors()
    {
        $this->request->set_url_params(
            [
                'failparam' => '123',
            ],
        );
        $this->request->set_attributes(
            [
                'args' => [
                    'failparam' => [
                        'sanitize_callback' => static function () {
                            return new WP_Error('invalid', 'Invalid.', 'mydata');
                        },
                    ],
                ],
            ],
        );

        $valid = $this->request->sanitize_params();
        $this->assertWPError($valid);

        $data = $valid->get_error_data();
        $this->assertArrayHasKey('details', $data);
        $this->assertArrayHasKey('failparam', $data['details']);
        $this->assertSame(
            [
                'code' => 'invalid',
                'message' => 'Invalid.',
                'data' => 'mydata',
            ],
            $data['details']['failparam'],
        );
    }

    public function test_sanitize_params_with_null_callback()
    {
        $this->request->set_url_params(
            [
                'some_email' => '',
            ],
        );

        $this->request->set_attributes(
            [
                'args' => [
                    'some_email' => [
                        'type' => 'string',
                        'format' => 'email',
                        'sanitize_callback' => null,
                    ],
                ],
            ],
        );

        $this->assertTrue($this->request->sanitize_params());
    }

    public function test_sanitize_params_with_false_callback()
    {
        $this->request->set_url_params(
            [
                'some_uri' => 1.23422,
            ],
        );

        $this->request->set_attributes(
            [
                'args' => [
                    'some_uri' => [
                        'type' => 'string',
                        'format' => 'uri',
                        'sanitize_callback' => false,
                    ],
                ],
            ],
        );

        $this->assertTrue($this->request->sanitize_params());
    }

    public function test_has_valid_params_required_flag()
    {
        $this->request->set_attributes(
            [
                'args' => [
                    'someinteger' => [
                        'required' => true,
                    ],
                ],
            ],
        );

        $valid = $this->request->has_valid_params();

        $this->assertWPError($valid);
        $this->assertSame('rest_missing_callback_param', $valid->get_error_code());
    }

    public function test_has_valid_params_required_flag_multiple()
    {
        $this->request->set_attributes(
            [
                'args' => [
                    'someinteger' => [
                        'required' => true,
                    ],
                    'someotherinteger' => [
                        'required' => true,
                    ],
                ],
            ],
        );

        $valid = $this->request->has_valid_params();

        $this->assertWPError($valid);
        $this->assertSame('rest_missing_callback_param', $valid->get_error_code());

        $data = $valid->get_error_data('rest_missing_callback_param');

        $this->assertContains('someinteger', $data['params']);
        $this->assertContains('someotherinteger', $data['params']);
    }

    public function test_has_valid_params_validate_callback()
    {
        $this->request->set_url_params(
            [
                'someinteger' => '123',
            ],
        );

        $this->request->set_attributes(
            [
                'args' => [
                    'someinteger' => [
                        'validate_callback' => '__return_false',
                    ],
                ],
            ],
        );

        $valid = $this->request->has_valid_params();

        $this->assertWPError($valid);
        $this->assertSame('rest_invalid_param', $valid->get_error_code());
    }

    public function test_has_valid_params_json_error()
    {
        $this->request->set_header('Content-Type', 'application/json');
        $this->request->set_body('{"invalid": JSON}');

        $valid = $this->request->has_valid_params();
        $this->assertWPError($valid);
        $this->assertSame('rest_invalid_json', $valid->get_error_code());
        $data = $valid->get_error_data();
        $this->assertSame(JSON_ERROR_SYNTAX, $data['json_error_code']);
    }


    public function test_has_valid_params_empty_json_no_error()
    {
        $this->request->set_header('Content-Type', 'application/json');
        $this->request->set_body('');

        $valid = $this->request->has_valid_params();
        $this->assertNotWPError($valid);
    }

    public function test_has_multiple_invalid_params_validate_callback()
    {
        $this->request->set_url_params(
            [
                'someinteger' => '123',
                'someotherinteger' => '123',
            ],
        );

        $this->request->set_attributes(
            [
                'args' => [
                    'someinteger' => [
                        'validate_callback' => '__return_false',
                    ],
                    'someotherinteger' => [
                        'validate_callback' => '__return_false',
                    ],
                ],
            ],
        );

        $valid = $this->request->has_valid_params();

        $this->assertWPError($valid);
        $this->assertSame('rest_invalid_param', $valid->get_error_code());

        $data = $valid->get_error_data('rest_invalid_param');

        $this->assertArrayHasKey('someinteger', $data['params']);
        $this->assertArrayHasKey('someotherinteger', $data['params']);
    }

    public function test_invalid_params_error_response_format()
    {
        $this->request->set_url_params(
            [
                'someinteger' => '123',
                'someotherparams' => '123',
            ],
        );

        $this->request->set_attributes(
            [
                'args' => [
                    'someinteger' => [
                        'validate_callback' => '__return_false',
                    ],
                    'someotherparams' => [
                        'validate_callback' => [$this, '_return_wp_error_on_validate_callback'],
                    ],
                ],
            ],
        );

        $valid = $this->request->has_valid_params();
        $this->assertWPError($valid);
        $error_data = $valid->get_error_data();

        $this->assertSame(['someinteger', 'someotherparams'], array_keys($error_data['params']));
        $this->assertSame('This is not valid!', $error_data['params']['someotherparams']);
    }


    /**
     * @ticket 46191
     */
    public function test_invalid_params_error_multiple_messages()
    {
        $this->request->set_url_params(
            [
                'failparam' => '123',
            ],
        );
        $this->request->set_attributes(
            [
                'args' => [
                    'failparam' => [
                        'validate_callback' => static function () {
                            $error = new WP_Error('invalid', 'Invalid.');
                            $error->add('invalid', 'Super Invalid.');
                            $error->add('broken', 'Broken.');

                            return $error;
                        },
                    ],
                ],
            ],
        );

        $valid = $this->request->has_valid_params();
        $this->assertWPError($valid);
        $data = $valid->get_error_data();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('params', $data);
        $this->assertArrayHasKey('failparam', $data['params']);
        $this->assertSame('Invalid. Super Invalid. Broken.', $data['params']['failparam']);
    }

    /**
     * @ticket 46191
     */
    public function test_invalid_params_provides_detailed_errors()
    {
        $this->request->set_url_params(
            [
                'failparam' => '123',
            ],
        );
        $this->request->set_attributes(
            [
                'args' => [
                    'failparam' => [
                        'validate_callback' => static function () {
                            return new WP_Error('invalid', 'Invalid.', 'mydata');
                        },
                    ],
                ],
            ],
        );

        $valid = $this->request->has_valid_params();
        $this->assertWPError($valid);

        $data = $valid->get_error_data();
        $this->assertArrayHasKey('details', $data);
        $this->assertArrayHasKey('failparam', $data['details']);
        $this->assertSame(
            [
                'code' => 'invalid',
                'message' => 'Invalid.',
                'data' => 'mydata',
            ],
            $data['details']['failparam'],
        );
    }

    public function _return_wp_error_on_validate_callback()
    {
        return new WP_Error('some-error', 'This is not valid!');
    }

    public function data_from_url()
    {
        return [
            [
                'permalink_structure' => '/%post_name%/',
                'original_url' => 'http://' . WP_TESTS_DOMAIN . '/wp-json/wp/v2/posts/1?foo=bar',
            ],
            [
                'permalink_structure' => '',
                'original_url' => 'http://' . WP_TESTS_DOMAIN . '/index.php?rest_route=%2Fwp%2Fv2%2Fposts%2F1&foo=bar',
            ],
        ];
    }

    /**
     * @dataProvider data_from_url
     */
    public function test_from_url($permalink_structure, $original_url)
    {
        update_option('permalink_structure', $permalink_structure);
        $url = add_query_arg('foo', 'bar', rest_url('/wp/v2/posts/1'));
        $this->assertSame($original_url, $url);
        $request = WP_REST_Request::from_url($url);
        $this->assertInstanceOf('WP_REST_Request', $request);
        $this->assertSame('/wp/v2/posts/1', $request->get_route());
        $this->assertSameSets(
            [
                'foo' => 'bar',
            ],
            $request->get_query_params(),
        );
    }

    /**
     * @dataProvider data_from_url
     */
    public function test_from_url_invalid($permalink_structure)
    {
        update_option('permalink_structure', $permalink_structure);
        $using_site = site_url('/wp/v2/posts/1');
        $request = WP_REST_Request::from_url($using_site);
        $this->assertFalse($request);

        $using_home = home_url('/wp/v2/posts/1');
        $request = WP_REST_Request::from_url($using_home);
        $this->assertFalse($request);
    }

    public function test_set_param()
    {
        $request = new WP_REST_Request();
        $request->set_param('param', 'value');
        $this->assertSame('value', $request->get_param('param'));
    }

    public function test_set_param_follows_parameter_order()
    {
        $request = new WP_REST_Request();
        $request->add_header('Content-Type', 'application/json');
        $request->set_method('POST');
        $request->set_body(
            wp_json_encode(
                [
                    'param' => 'value',
                ],
            ),
        );
        $this->assertSame('value', $request->get_param('param'));
        $this->assertSame(
            ['param' => 'value'],
            $request->get_json_params(),
        );

        $request->set_param('param', 'new_value');
        $this->assertSame('new_value', $request->get_param('param'));
        $this->assertSame(
            ['param' => 'new_value'],
            $request->get_json_params(),
        );
    }

    /**
     * @ticket 40838
     */
    public function test_set_param_updates_param_in_json_and_query()
    {
        $request = new WP_REST_Request();
        $request->add_header('Content-Type', 'application/json');
        $request->set_method('POST');
        $request->set_body(
            wp_json_encode(
                [
                    'param' => 'value_body',
                ],
            ),
        );
        $request->set_query_params(
            [
                'param' => 'value_query',
            ],
        );
        $request->set_param('param', 'new_value');

        $this->assertSame('new_value', $request->get_param('param'));
        $this->assertSame([], $request->get_body_params());
        $this->assertSame(['param' => 'new_value'], $request->get_json_params());
        $this->assertSame(['param' => 'new_value'], $request->get_query_params());
    }

    /**
     * @ticket 40838
     */
    public function test_set_param_updates_param_if_already_exists_in_query()
    {
        $request = new WP_REST_Request();
        $request->add_header('Content-Type', 'application/json');
        $request->set_method('POST');
        $request->set_body(
            wp_json_encode(
                [
                    'param_body' => 'value_body',
                ],
            ),
        );
        $original_defaults = [
            'param_query' => 'default_query_value',
            'param_body' => 'default_body_value',
        ];
        $request->set_default_params($original_defaults);
        $request->set_query_params(
            [
                'param_query' => 'value_query',
            ],
        );
        $request->set_param('param_query', 'new_value');

        $this->assertSame('new_value', $request->get_param('param_query'));
        $this->assertSame([], $request->get_body_params());
        $this->assertSame(['param_body' => 'value_body'], $request->get_json_params());
        $this->assertSame(['param_query' => 'new_value'], $request->get_query_params());
        // Verify the default wasn't overwritten.
        $this->assertSame($original_defaults, $request->get_default_params());
    }

    /**
     * @ticket 40838
     */
    public function test_set_param_to_null_updates_param_in_json_and_query()
    {
        $request = new WP_REST_Request();
        $request->add_header('Content-Type', 'application/json');
        $request->set_method('POST');
        $request->set_body(
            wp_json_encode(
                [
                    'param' => 'value_body',
                ],
            ),
        );
        $request->set_query_params(
            [
                'param' => 'value_query',
            ],
        );
        $request->set_param('param', null);

        $this->assertNull($request->get_param('param'));
        $this->assertSame([], $request->get_body_params());
        $this->assertSame(['param' => null], $request->get_json_params());
        $this->assertSame(['param' => null], $request->get_query_params());
    }

    /**
     * @ticket 40838
     */
    public function test_set_param_from_null_updates_param_in_json_and_query_with_null()
    {
        $request = new WP_REST_Request();
        $request->add_header('Content-Type', 'application/json');
        $request->set_method('POST');
        $request->set_body(
            wp_json_encode(
                [
                    'param' => null,
                ],
            ),
        );
        $request->set_query_params(
            [
                'param' => null,
            ],
        );
        $request->set_param('param', 'new_value');

        $this->assertSame('new_value', $request->get_param('param'));
        $this->assertSame([], $request->get_body_params());
        $this->assertSame(['param' => 'new_value'], $request->get_json_params());
        $this->assertSame(['param' => 'new_value'], $request->get_query_params());
    }

    /**
     * @ticket 50786
     */
    public function test_set_param_with_invalid_json()
    {
        $request = new WP_REST_Request();
        $request->add_header('Content-Type', 'application/json');
        $request->set_method('POST');
        $request->set_body('');
        $request->set_param('param', 'value');

        $this->assertTrue($request->has_param('param'));
        $this->assertSame('value', $request->get_param('param'));
    }

    /**
     * @ticket 51255
     */
    public function test_route_level_validate_callback()
    {
        $request = new WP_REST_Request();
        $request->set_query_params(['test' => 'value']);

        $error = new WP_Error('error_code', __('Error Message'), ['status' => 400]);
        $callback = $this->createPartialMock('Mock_Invokable', ['__invoke']);
        $callback->expects(self::once())->method('__invoke')->with(self::identicalTo($request))->willReturn($error);
        $request->set_attributes(
            [
                'args' => [
                    'test' => [
                        'validate_callback' => '__return_true',
                    ],
                ],
                'validate_callback' => $callback,
            ],
        );

        $this->assertSame($error, $request->has_valid_params());
    }

    /**
     * @ticket 51255
     */
    public function test_route_level_validate_callback_no_parameter_callbacks()
    {
        $request = new WP_REST_Request();
        $request->set_query_params(['test' => 'value']);

        $error = new WP_Error('error_code', __('Error Message'), ['status' => 400]);
        $callback = $this->createPartialMock('Mock_Invokable', ['__invoke']);
        $callback->expects(self::once())->method('__invoke')->with(self::identicalTo($request))->willReturn($error);
        $request->set_attributes(
            [
                'validate_callback' => $callback,
            ],
        );

        $this->assertSame($error, $request->has_valid_params());
    }

    /**
     * @ticket 51255
     */
    public function test_route_level_validate_callback_is_not_executed_if_parameter_validation_fails()
    {
        $request = new WP_REST_Request();
        $request->set_query_params(['test' => 'value']);

        $callback = $this->createPartialMock('Mock_Invokable', ['__invoke']);
        $callback->expects(self::never())->method('__invoke');
        $request->set_attributes(
            [
                'validate_callback' => $callback,
                'args' => [
                    'test' => [
                        'validate_callback' => '__return_false',
                    ],
                ],
            ],
        );

        $valid = $request->has_valid_params();
        $this->assertWPError($valid);
        $this->assertSame('rest_invalid_param', $valid->get_error_code());
    }
}
