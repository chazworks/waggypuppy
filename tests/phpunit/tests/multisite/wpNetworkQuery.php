<?php

if (is_multisite()) :

    /**
     * Test network query functionality in multisite.
     *
     * @group ms-network
     * @group ms-network-query
     * @group multisite
     */
    class Tests_Multisite_wpNetworkQuery extends WP_UnitTestCase
    {
        protected static $network_ids;

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
                'www.wordpress.net/' => [
                    'domain' => 'www.wordpress.net',
                    'path' => '/',
                ],
                'www.w.org/foo/' => [
                    'domain' => 'www.w.org',
                    'path' => '/foo/',
                ],
            ];

            foreach (self::$network_ids as &$id) {
                $id = $factory->network->create($id);
            }
            unset($id);
        }

        public static function wpTearDownAfterClass()
        {
            global $wpdb;

            foreach (self::$network_ids as $id) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->sitemeta} WHERE site_id = %d", $id));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->site} WHERE id= %d", $id));
            }
        }

        public function test_wp_network_query_by_number()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'number' => 3,
                ],
            );

            $this->assertCount(3, $found);
        }

        public function test_wp_network_query_by_network__in_with_order()
        {
            $expected = [self::$network_ids['wp.org/'], self::$network_ids['make.wp.org/']];

            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'network__in' => $expected,
                    'order' => 'ASC',
                ],
            );

            $this->assertSame($expected, $found);

            $found = $q->query(
                [
                    'fields' => 'ids',
                    'network__in' => $expected,
                    'order' => 'DESC',
                ],
            );

            $this->assertSame(array_reverse($expected), $found);
        }

        public function test_wp_network_query_by_network__in_with_single_id()
        {
            $expected = [self::$network_ids['wp.org/']];

            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'network__in' => $expected,
                ],
            );

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_network__in_with_multiple_ids()
        {
            $expected = [self::$network_ids['wp.org/'], self::$network_ids['www.wordpress.net/']];

            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'network__in' => $expected,
                ],
            );

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_network__in_and_count_with_multiple_ids()
        {
            $expected = [self::$network_ids['wp.org/'], self::$network_ids['make.wp.org/']];

            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'count' => true,
                    'network__in' => $expected,
                ],
            );

            $this->assertSame(2, $found);
        }

        public function test_wp_network_query_by_network__not_in_with_single_id()
        {
            $excluded = [self::$network_ids['wp.org/']];
            $expected = array_diff(self::$network_ids, $excluded);

            // Exclude main network since we don't have control over it here.
            $excluded[] = 1;

            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'network__not_in' => $excluded,
                ],
            );

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_network__not_in_with_multiple_ids()
        {
            $excluded = [self::$network_ids['wp.org/'], self::$network_ids['www.w.org/foo/']];
            $expected = array_diff(self::$network_ids, $excluded);

            // Exclude main network since we don't have control over it here.
            $excluded[] = 1;

            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'network__not_in' => $excluded,
                ],
            );

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_domain()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'domain' => 'www.w.org',
                ],
            );

            $expected = [
                self::$network_ids['www.w.org/foo/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_domain__in_with_single_domain()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'domain__in' => ['make.wp.org'],
                ],
            );

            $expected = [
                self::$network_ids['make.wp.org/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_domain__in_with_multiple_domains()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'domain__in' => ['wp.org', 'make.wp.org'],
                ],
            );

            $expected = [
                self::$network_ids['wp.org/'],
                self::$network_ids['make.wp.org/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_domain__in_with_multiple_domains_and_number()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'number' => 1,
                    'domain__in' => ['wp.org', 'make.wp.org'],
                ],
            );

            $expected = [
                self::$network_ids['wp.org/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_domain__in_with_multiple_domains_and_number_and_offset()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'number' => 1,
                    'offset' => 1,
                    'domain__in' => ['wp.org', 'make.wp.org'],
                ],
            );

            $expected = [
                self::$network_ids['make.wp.org/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_domain__not_in_with_single_domain()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'domain__not_in' => ['www.w.org'],
                ],
            );

            $expected = [
                get_current_site()->id, // Account for the initial network added by the test suite.
                self::$network_ids['wp.org/'],
                self::$network_ids['make.wp.org/'],
                self::$network_ids['www.wordpress.net/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_domain__not_in_with_multiple_domains()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'domain__not_in' => ['wp.org', 'www.w.org'],
                ],
            );

            $expected = [
                get_current_site()->id, // Account for the initial network added by the test suite.
                self::$network_ids['make.wp.org/'],
                self::$network_ids['www.wordpress.net/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_domain__not_in_with_multiple_domains_and_number()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'number' => 2,
                    'domain__not_in' => ['wp.org', 'www.w.org'],
                ],
            );

            $expected = [
                get_current_site()->id, // Account for the initial network added by the test suite.
                self::$network_ids['make.wp.org/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_domain__not_in_with_multiple_domains_and_number_and_offset()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'number' => 2,
                    'offset' => 1,
                    'domain__not_in' => ['wp.org', 'www.w.org'],
                ],
            );

            $expected = [
                self::$network_ids['make.wp.org/'],
                self::$network_ids['www.wordpress.net/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_path_with_expected_results()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'path' => '/',
                    'network__not_in' => get_current_site()->id, // Exclude the initial network added by the test suite.
                ],
            );

            $expected = [
                self::$network_ids['wp.org/'],
                self::$network_ids['make.wp.org/'],
                self::$network_ids['www.wordpress.net/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_path_and_number_and_offset_with_expected_results()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'number' => 1,
                    'offset' => 2,
                    'path' => '/',
                    'network__not_in' => get_current_site()->id, // Exclude the initial network added by the test suite.
                ],
            );

            $expected = [
                self::$network_ids['www.wordpress.net/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_path_with_no_expected_results()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'path' => '/bar/',
                ],
            );

            $this->assertEmpty($found);
        }

        public function test_wp_network_query_by_search_with_text_in_domain()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'search' => 'ww.word',
                ],
            );

            $expected = [
                self::$network_ids['www.wordpress.net/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_search_with_text_in_path()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'search' => 'foo',
                ],
            );

            $expected = [
                self::$network_ids['www.w.org/foo/'],
            ];

            $this->assertSameSets($expected, $found);
        }

        public function test_wp_network_query_by_path_order_by_domain_desc()
        {
            $q = new WP_Network_Query();
            $found = $q->query(
                [
                    'fields' => 'ids',
                    'path' => '/',
                    'network__not_in' => get_current_site()->id, // Exclude the initial network added by the test suite.
                    'order' => 'DESC',
                    'orderby' => 'domain',
                ],
            );

            $expected = [
                self::$network_ids['www.wordpress.net/'],
                self::$network_ids['wp.org/'],
                self::$network_ids['make.wp.org/'],
            ];

            $this->assertSame($expected, $found);
        }

        /**
         * @ticket 41347
         */
        public function test_wp_network_query_cache_with_different_fields_no_count()
        {
            $q = new WP_Network_Query();
            $query_1 = $q->query(
                [
                    'fields' => 'all',
                    'number' => 3,
                    'order' => 'ASC',
                ],
            );
            $number_of_queries = get_num_queries();

            $query_2 = $q->query(
                [
                    'fields' => 'ids',
                    'number' => 3,
                    'order' => 'ASC',
                ],
            );

            $this->assertSame($number_of_queries, get_num_queries());
        }

        /**
         * @ticket 41347
         */
        public function test_wp_network_query_cache_with_different_fields_active_count()
        {
            $q = new WP_Network_Query();

            $query_1 = $q->query(
                [
                    'fields' => 'all',
                    'number' => 3,
                    'order' => 'ASC',
                    'count' => true,
                ],
            );
            $number_of_queries = get_num_queries();

            $query_2 = $q->query(
                [
                    'fields' => 'ids',
                    'number' => 3,
                    'order' => 'ASC',
                    'count' => true,
                ],
            );
            $this->assertSame($number_of_queries, get_num_queries());
        }

        /**
         * @ticket 41347
         */
        public function test_wp_network_query_cache_with_same_fields_different_count()
        {
            $q = new WP_Network_Query();

            $query_1 = $q->query(
                [
                    'fields' => 'ids',
                    'number' => 3,
                    'order' => 'ASC',
                ],
            );

            $number_of_queries = get_num_queries();

            $query_2 = $q->query(
                [
                    'fields' => 'ids',
                    'number' => 3,
                    'order' => 'ASC',
                    'count' => true,
                ],
            );
            $this->assertSame($number_of_queries + 1, get_num_queries());
        }

        /**
         * @ticket 55461
         */
        public function test_wp_network_query_cache_with_same_fields_same_cache_field()
        {
            $q = new WP_Network_Query();
            $query_1 = $q->query(
                [
                    'fields' => 'all',
                    'number' => 3,
                    'order' => 'ASC',
                    'update_network_cache' => true,
                ],
            );
            $number_of_queries = get_num_queries();

            $query_2 = $q->query(
                [
                    'fields' => 'all',
                    'number' => 3,
                    'order' => 'ASC',
                    'update_network_cache' => true,
                ],
            );

            $this->assertSame($number_of_queries, get_num_queries());
        }

        /**
         * @ticket 55461
         */
        public function test_wp_network_query_cache_with_same_fields_different_cache_field()
        {
            $q = new WP_Network_Query();
            $query_1 = $q->query(
                [
                    'fields' => 'all',
                    'number' => 3,
                    'order' => 'ASC',
                    'update_network_cache' => true,
                ],
            );
            $number_of_queries = get_num_queries();

            $query_2 = $q->query(
                [
                    'fields' => 'all',
                    'number' => 3,
                    'order' => 'ASC',
                    'update_network_cache' => false,
                ],
            );

            $this->assertSame($number_of_queries, get_num_queries());
        }

        /**
         * @ticket 45749
         * @ticket 47599
         */
        public function test_networks_pre_query_filter_should_bypass_database_query()
        {
            add_filter('networks_pre_query', [__CLASS__, 'filter_networks_pre_query'], 10, 2);

            $num_queries = get_num_queries();

            $q = new WP_Network_Query();
            $results = $q->query([]);

            remove_filter('networks_pre_query', [__CLASS__, 'filter_networks_pre_query'], 10, 2);

            // Make sure no queries were executed.
            $this->assertSame($num_queries, get_num_queries());

            // We manually inserted a non-existing site and overrode the results with it.
            $this->assertSame([555], $results);

            // Make sure manually setting found_networks doesn't get overwritten.
            $this->assertSame(1, $q->found_networks);
        }

        public static function filter_networks_pre_query($networks, $query)
        {
            $query->found_networks = 1;

            return [555];
        }

        /**
         * @ticket 51333
         */
        public function test_networks_pre_query_filter_should_set_networks_property()
        {
            add_filter('networks_pre_query', [__CLASS__, 'filter_networks_pre_query_and_set_networks'], 10, 2);

            $q = new WP_Network_Query();
            $results = $q->query([]);

            remove_filter('networks_pre_query', [__CLASS__, 'filter_networks_pre_query_and_set_networks'], 10);

            // Make sure the networks property is the same as the results.
            $this->assertSame($results, $q->networks);

            // Make sure the network domain is `wp.org`.
            $this->assertSame('wp.org', $q->networks[0]->domain);
        }

        public static function filter_networks_pre_query_and_set_networks($networks, $query)
        {
            return [get_network(self::$network_ids['wp.org/'])];
        }

        /**
         * @ticket 56841
         */
        public function test_wp_network_query_does_not_have_leading_whitespace()
        {
            $q = new WP_Network_Query();
            $q->query(
                [
                    'fields' => 'all',
                    'number' => 3,
                    'order' => 'ASC',
                    'update_network_cache' => true,
                ],
            );

            $this->assertSame(ltrim($q->request), $q->request, 'The query has leading whitespace');
        }
    }

endif;
