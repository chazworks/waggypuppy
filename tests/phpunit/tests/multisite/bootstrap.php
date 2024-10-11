<?php

if (is_multisite()) :

    /**
     * Tests specific to the bootstrap process of Multisite.
     *
     * @group ms-bootstrap
     * @group multisite
     */
    class Tests_Multisite_Bootstrap extends WP_UnitTestCase
    {
        protected static $network_ids;
        protected static $site_ids;

        public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
        {
            self::$network_ids = [
                'wp.org/' => [
                    'domain' => 'wp.org',
                    'path' => '/',
                ],
                'make.wp.org/' => [
                    'domain' => 'make.wp.org',
                    'path' => '/',
                ],
                'wp.org/one/' => [
                    'domain' => 'wp.org',
                    'path' => '/one/',
                ],
                'wp.org/one/b/' => [
                    'domain' => 'wp.org',
                    'path' => '/one/b/',
                ],
                'wordpress.net/' => [
                    'domain' => 'wordpress.net',
                    'path' => '/',
                ],
                'www.wordpress.net/' => [
                    'domain' => 'www.wordpress.net',
                    'path' => '/',
                ],
                'www.wordpress.net/two/' => [
                    'domain' => 'www.wordpress.net',
                    'path' => '/two/',
                ],
                'wordpress.net/three/' => [
                    'domain' => 'wordpress.net',
                    'path' => '/three/',
                ],
            ];

            foreach (self::$network_ids as &$id) {
                $id = $factory->network->create($id);
            }
            unset($id);

            self::$site_ids = [
                'wp.org/' => [
                    'domain' => 'wp.org',
                    'path' => '/',
                    'network_id' => self::$network_ids['wp.org/'],
                ],
                'wp.org/foo/' => [
                    'domain' => 'wp.org',
                    'path' => '/foo/',
                    'network_id' => self::$network_ids['wp.org/'],
                ],
                'wp.org/foo/bar/' => [
                    'domain' => 'wp.org',
                    'path' => '/foo/bar/',
                    'network_id' => self::$network_ids['wp.org/'],
                ],
                'make.wp.org/' => [
                    'domain' => 'make.wp.org',
                    'path' => '/',
                    'network_id' => self::$network_ids['make.wp.org/'],
                ],
                'make.wp.org/foo/' => [
                    'domain' => 'make.wp.org',
                    'path' => '/foo/',
                    'network_id' => self::$network_ids['make.wp.org/'],
                ],
                'www.w.org/' => [
                    'domain' => 'www.w.org',
                    'path' => '/',
                ],
                'www.w.org/foo/' => [
                    'domain' => 'www.w.org',
                    'path' => '/foo/',
                ],
                'www.w.org/foo/bar/' => [
                    'domain' => 'www.w.org',
                    'path' => '/foo/bar/',
                ],
            ];

            foreach (self::$site_ids as &$id) {
                $id = $factory->blog->create($id);
            }
            unset($id);
        }

        public static function wpTearDownAfterClass()
        {
            global $wpdb;

            foreach (self::$site_ids as $id) {
                wp_delete_site($id);
            }

            foreach (self::$network_ids as $id) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->sitemeta} WHERE site_id = %d", $id));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->site} WHERE id= %d", $id));
            }

            wp_update_network_site_counts();
        }

        /**
         * @ticket 27003
         * @dataProvider data_get_network_by_path
         *
         * @param string $expected_key The array key associated with expected data for the test.
         * @param string $domain The requested domain.
         * @param string $path The requested path.
         * @param string $message The message to pass for failed tests.
         */
        public function test_get_network_by_path($expected_key, $domain, $path, $message)
        {
            $network = get_network_by_path($domain, $path);
            $this->assertSame(self::$network_ids[$expected_key], $network->id, $message);
        }

        public function data_get_network_by_path()
        {
            return [
                ['wp.org/', 'wp.org', '/', 'A standard domain and path request should work.'],
                [
                    'wordpress.net/',
                    'wordpress.net',
                    '/notapath/',
                    'A missing path on a top level domain should find the correct network.',
                ],
                [
                    'www.wordpress.net/',
                    'www.wordpress.net',
                    '/notapath/',
                    'A missing path should find the correct network.',
                ],
                ['wp.org/one/', 'www.wp.org', '/one/', 'Should find the path despite the www.'],
                [
                    'wp.org/one/',
                    'wp.org',
                    '/one/page/',
                    'A request with two path segments should find the correct network.',
                ],
                [
                    'wp.org/one/b/',
                    'wp.org',
                    '/one/b/',
                    'A request with two valid path segments should find the correct network.',
                ],
                ['wp.org/', 'site1.wp.org', '/one/', 'Should not find path because domains do not match.'],
                ['wordpress.net/three/', 'wordpress.net', '/three/', 'A network can have a path.'],
                [
                    'www.wordpress.net/two/',
                    'www.wordpress.net',
                    '/two/',
                    'A www network with a path can coexist with a non-www network.',
                ],
                [
                    'wordpress.net/',
                    'site1.wordpress.net',
                    '/notapath/',
                    'An invalid subdomain should find the top level network domain.',
                ],
                [
                    'wordpress.net/',
                    'site1.wordpress.net',
                    '/three/',
                    'An invalid subdomain and path should find the top level network domain.',
                ],
                [
                    'wordpress.net/',
                    'x.y.wordpress.net',
                    '/',
                    'An invalid two level subdomain should find the top level network domain.',
                ],
            ];
        }

        /**
         * @ticket 37217
         * @dataProvider data_get_network_by_path_with_zero_path_segments
         *
         * @param string $expected_key The array key associated with expected data for the test.
         * @param string $domain The requested domain.
         * @param string $path The requested path.
         * @param string $message The message to pass for failed tests.
         */
        public function test_get_network_by_path_with_zero_path_segments($expected_key, $domain, $path, $message)
        {
            add_filter('network_by_path_segments_count', '__return_zero');

            $network = get_network_by_path($domain, $path);

            remove_filter('network_by_path_segments_count', '__return_zero');

            $this->assertSame(self::$network_ids[$expected_key], $network->id, $message);
        }

        public function data_get_network_by_path_with_zero_path_segments()
        {
            return [
                ['wp.org/', 'wp.org', '/', 'A standard domain and path request should work.'],
                [
                    'wordpress.net/',
                    'wordpress.net',
                    '/notapath/',
                    'A network matching a top level domain should be found regardless of path.',
                ],
                [
                    'www.wordpress.net/',
                    'www.wordpress.net',
                    '/notapath/',
                    'A network matching a domain should be found regardless of path.',
                ],
                ['wp.org/', 'www.wp.org', '/one/', 'Should find the network despite the www and regardless of path.'],
                [
                    'wp.org/',
                    'site1.wp.org',
                    '/one/',
                    'Should find the network with the corresponding top level domain regardless of path.',
                ],
                [
                    'www.wordpress.net/',
                    'www.wordpress.net',
                    '/two/',
                    'A www network can coexist with a non-www network.',
                ],
                [
                    'make.wp.org/',
                    'make.wp.org',
                    '/notapath/',
                    'A subdomain network should be found regardless of path.',
                ],
                [
                    'wordpress.net/',
                    'x.y.wordpress.net',
                    '/',
                    'An invalid two level subdomain should find the top level network domain.',
                ],
            ];
        }

        /**
         * Even if a matching network is available, it should not match if the the filtered
         * value for network path segments is fewer than the number of paths passed.
         */
        public function test_get_network_by_path_with_forced_single_path_segment_returns_single_path_network()
        {
            add_filter('network_by_path_segments_count', [$this, 'filter_network_path_segments']);
            $network = get_network_by_path('wp.org', '/one/b/');
            remove_filter('network_by_path_segments_count', [$this, 'filter_network_path_segments']);

            $this->assertSame(self::$network_ids['wp.org/one/'], $network->id);
        }

        public function filter_network_path_segments()
        {
            return 1;
        }

        /**
         * @ticket 27003
         * @ticket 27927
         * @dataProvider data_get_site_by_path
         *
         * @param string $expected_key The array key associated with expected data for the test.
         * @param string $domain The requested domain.
         * @param string $path The requested path.
         * @param int $segments Optional. Number of segments to use in `get_site_by_path()`.
         */
        public function test_get_site_by_path($expected_key, $domain, $path, $segments = null)
        {
            $site = get_site_by_path($domain, $path, $segments);

            if ($expected_key) {
                $this->assertEquals(self::$site_ids[$expected_key], $site->blog_id);
            } else {
                $this->assertFalse($site);
            }
        }

        public function data_get_site_by_path()
        {
            return [
                ['wp.org/', 'wp.org', '/notapath/'],
                ['wp.org/', 'www.wp.org', '/notapath/'],
                ['wp.org/foo/bar/', 'wp.org', '/foo/bar/baz/'],
                ['wp.org/foo/bar/', 'www.wp.org', '/foo/bar/baz/'],
                ['wp.org/foo/bar/', 'wp.org', '/foo/bar/baz/', 3],
                ['wp.org/foo/bar/', 'www.wp.org', '/foo/bar/baz/', 3],
                ['wp.org/foo/bar/', 'wp.org', '/foo/bar/baz/', 2],
                ['wp.org/foo/bar/', 'www.wp.org', '/foo/bar/baz/', 2],
                ['wp.org/foo/', 'wp.org', '/foo/bar/baz/', 1],
                ['wp.org/foo/', 'www.wp.org', '/foo/bar/baz/', 1],
                ['wp.org/', 'wp.org', '/', 0],
                ['wp.org/', 'www.wp.org', '/', 0],
                ['make.wp.org/foo/', 'make.wp.org', '/foo/bar/baz/quz/', 4],
                ['make.wp.org/foo/', 'www.make.wp.org', '/foo/bar/baz/quz/', 4],
                ['www.w.org/', 'www.w.org', '/', 0],
                ['www.w.org/', 'www.w.org', '/notapath'],
                ['www.w.org/foo/bar/', 'www.w.org', '/foo/bar/baz/'],
                ['www.w.org/foo/', 'www.w.org', '/foo/bar/baz/', 1],

                // A site installed with www will not be found by the root domain.
                [false, 'w.org', '/'],
                [false, 'w.org', '/notapath/'],
                [false, 'w.org', '/foo/bar/baz/'],
                [false, 'w.org', '/foo/bar/baz/', 1],

                // A site will not be found by its root domain when an invalid subdomain is requested.
                [false, 'invalid.wp.org', '/'],
                [false, 'invalid.wp.org', '/foo/bar/'],
            ];
        }

        /**
         * @ticket 27884
         * @dataProvider data_multisite_bootstrap
         *
         * @param string $site_key The array key associated with the expected site for the test.
         * @param string $network_key The array key associated with the expected network for the test.
         * @param string $domain The requested domain.
         * @param string $path The requested path.
         */
        public function test_multisite_bootstrap($site_key, $network_key, $domain, $path)
        {
            global $current_blog;

            $expected = [
                'network_id' => self::$network_ids[$network_key],
                'site_id' => self::$site_ids[$site_key],
            ];

            ms_load_current_site_and_network($domain, $path);
            $actual = [
                'network_id' => $current_blog->site_id,
                'site_id' => $current_blog->blog_id,
            ];
            ms_load_current_site_and_network(WP_TESTS_DOMAIN, '/');

            $this->assertEqualSetsWithIndex($expected, $actual);
        }

        public function data_multisite_bootstrap()
        {
            return [
                ['wp.org/', 'wp.org/', 'wp.org', '/'],
                ['wp.org/', 'wp.org/', 'wp.org', '/2014/04/23/hello-world/'],
                ['wp.org/', 'wp.org/', 'wp.org', '/sample-page/'],
                ['wp.org/', 'wp.org/', 'wp.org', '/?p=1'],
                ['wp.org/', 'wp.org/', 'wp.org', '/wp-admin/'],
                ['wp.org/foo/', 'wp.org/', 'wp.org', '/foo/'],
                ['wp.org/foo/', 'wp.org/', 'wp.org', '/FOO/'],
                ['wp.org/foo/', 'wp.org/', 'wp.org', '/foo/2014/04/23/hello-world/'],
                ['wp.org/foo/', 'wp.org/', 'wp.org', '/foo/sample-page/'],
                ['wp.org/foo/', 'wp.org/', 'wp.org', '/foo/?p=1'],
                ['wp.org/foo/', 'wp.org/', 'wp.org', '/foo/wp-admin/'],
                ['make.wp.org/', 'make.wp.org/', 'make.wp.org', '/'],
                ['make.wp.org/foo/', 'make.wp.org/', 'make.wp.org', '/foo/'],
            ];
        }

        /**
         * @ticket 27884
         */
        public function test_multisite_bootstrap_additional_path_segments()
        {
            global $current_blog;

            $expected = [
                'network_id' => self::$network_ids['wp.org/'],
                'site_id' => self::$site_ids['wp.org/foo/bar/'],
            ];
            add_filter('site_by_path_segments_count', [$this, 'filter_path_segments_to_two']);
            ms_load_current_site_and_network('wp.org', '/foo/bar/');
            $actual = [
                'network_id' => $current_blog->site_id,
                'site_id' => $current_blog->blog_id,
            ];
            remove_filter('site_by_path_segments_count', [$this, 'filter_path_segments_to_two']);
            ms_load_current_site_and_network(WP_TESTS_DOMAIN, '/');

            $this->assertEqualSetsWithIndex($expected, $actual);
        }

        /**
         * @ticket 37053
         */
        public function test_get_site_by_path_returns_wp_site()
        {
            add_filter('pre_get_site_by_path', [$this, 'filter_pre_get_site_by_path'], 10, 3);

            $site = get_site_by_path('example.com', '/foo/');

            remove_filter('pre_get_site_by_path', [$this, 'filter_pre_get_site_by_path'], 10);

            $this->assertInstanceOf('WP_Site', $site);
        }

        public function filter_path_segments_to_two()
        {
            return 2;
        }

        public function filter_pre_get_site_by_path($site, $domain, $path)
        {
            $site = new stdClass();
            $site->blog_id = 100;
            $site->domain = $domain;
            $site->path = $path;
            $site->site_id = 1;

            return $site;
        }
    }

endif;
