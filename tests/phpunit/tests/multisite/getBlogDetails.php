<?php

if (is_multisite()) :

    /**
     * @ticket 29845
     * @group ms-site
     * @group multisite
     */
    class Tests_Multisite_GetBlogDetails extends WP_UnitTestCase
    {
        protected static $network_ids;
        protected static $site_ids;

        public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
        {
            self::$site_ids = [
                WP_TESTS_DOMAIN . '/foo/' => [
                    'domain' => WP_TESTS_DOMAIN,
                    'path' => '/foo/',
                ],
                'foo.' . WP_TESTS_DOMAIN . '/' => [
                    'domain' => 'foo.' . WP_TESTS_DOMAIN,
                    'path' => '/',
                ],
                'wp.org/' => [
                    'domain' => 'wp.org',
                    'path' => '/',
                ],
            ];

            foreach (self::$site_ids as &$id) {
                $id = $factory->blog->create($id);
            }
            unset($id);
        }

        public static function wpTearDownAfterClass()
        {
            foreach (self::$site_ids as $id) {
                wp_delete_site($id);
            }

            wp_update_network_site_counts();
        }

        public function test_get_blog_details_with_no_arguments_returns_current_site()
        {
            $site = get_blog_details();
            $this->assertEquals(get_current_blog_id(), $site->blog_id);
        }

        public function test_get_blog_details_with_site_name_string_subdirectory()
        {
            if (is_subdomain_install()) {
                $this->markTestSkipped('This test is only valid in a subdirectory configuration.');
            }

            $site = get_blog_details('foo');
            $this->assertEquals(self::$site_ids[WP_TESTS_DOMAIN . '/foo/'], $site->blog_id);
        }

        public function test_get_blog_details_with_site_name_string_subdomain()
        {
            if (!is_subdomain_install()) {
                $this->markTestSkipped('This test is only valid in a subdomain configuration.');
            }

            $site = get_blog_details('foo');
            $this->assertEquals(self::$site_ids['foo.' . WP_TESTS_DOMAIN . '/'], $site->blog_id);
        }

        public function test_get_blog_details_with_invalid_site_name_string()
        {
            $site = get_blog_details('invalid');
            $this->assertFalse($site);
        }

        public function test_get_blog_details_with_site_id_int()
        {
            $site = get_blog_details(self::$site_ids['wp.org/']);
            $this->assertEquals(self::$site_ids['wp.org/'], $site->blog_id);
        }

        public function test_get_blog_details_with_invalid_site_id_int()
        {
            $site = get_blog_details(99999);
            $this->assertFalse($site);
        }

        public function test_get_blog_details_with_blog_id_in_fields()
        {
            $site = get_blog_details(['blog_id' => self::$site_ids['wp.org/']]);
            $this->assertEquals(self::$site_ids['wp.org/'], $site->blog_id);
        }

        public function test_get_blog_details_with_invalid_blog_id_in_fields()
        {
            $site = get_blog_details(['blog_id' => 88888]);
            $this->assertFalse($site);
        }

        public function test_get_blog_details_with_domain_and_path_in_fields()
        {
            $site = get_blog_details(
                [
                    'domain' => 'wp.org',
                    'path' => '/',
                ],
            );
            $this->assertEquals(self::$site_ids['wp.org/'], $site->blog_id);
        }

        public function test_get_blog_details_with_domain_and_invalid_path_in_fields()
        {
            $site = get_blog_details(
                [
                    'domain' => 'wp.org',
                    'path' => '/zxy/',
                ],
            );
            $this->assertFalse($site);
        }

        public function test_get_blog_details_with_path_and_invalid_domain_in_fields()
        {
            $site = get_blog_details(
                [
                    'domain' => 'invalid.org',
                    'path' => '/foo/',
                ],
            );
            $this->assertFalse($site);
        }

        public function test_get_blog_details_with_only_domain_in_fields_subdomain()
        {
            if (!is_subdomain_install()) {
                $this->markTestSkipped('This test is only valid in a subdomain configuration.');
            }

            $site = get_blog_details(['domain' => 'wp.org']);
            $this->assertSame(self::$site_ids['wp.org/'], $site->blog_id);
        }

        public function test_get_blog_details_with_only_domain_in_fields_subdirectory()
        {
            if (is_subdomain_install()) {
                $this->markTestSkipped('This test is only valid in a subdirectory configuration.');
            }

            $site = get_blog_details(['domain' => 'wp.org']);
            $this->assertFalse($site);
        }

        public function test_get_blog_details_with_only_path_in_fields()
        {
            $site = get_blog_details(['path' => '/foo/']);
            $this->assertFalse($site);
        }

        /**
         * @ticket 50391
         */
        public function test_get_blog_details_does_not_switch_to_current_blog()
        {
            $count = did_action('switch_blog');

            get_blog_details();
            $this->assertSame($count, did_action('switch_blog'));
        }

        /**
         * @dataProvider data_get_all
         *
         * @ticket 40228
         */
        public function test_get_blog_details_get_object_vars($get_all)
        {
            $site = get_blog_details(
                [
                    'domain' => 'wp.org',
                    'path' => '/',
                ],
                $get_all,
            );

            $result = array_keys(get_object_vars($site));

            $this->assertSameSets($this->get_fields($get_all), $result);
        }

        /**
         * @dataProvider data_get_all
         *
         * @ticket 40228
         */
        public function test_get_blog_details_iterate_over_result($get_all)
        {
            $site = get_blog_details(
                [
                    'domain' => 'wp.org',
                    'path' => '/',
                ],
                $get_all,
            );

            $result = [];
            foreach ($site as $key => $value) {
                $result[] = $key;
            }

            $this->assertSameSets($this->get_fields($get_all), $result);
        }

        public function data_get_all()
        {
            return [
                [false],
                [true],
            ];
        }

        protected function get_fields($all = false)
        {
            $fields = [
                'blog_id',
                'domain',
                'path',
                'site_id',
                'registered',
                'last_updated',
                'public',
                'archived',
                'mature',
                'spam',
                'deleted',
                'lang_id',
            ];

            if ($all) {
                $fields = array_merge(
                    $fields,
                    [
                        'blogname',
                        'siteurl',
                        'post_count',
                        'home',
                    ],
                );
            }

            return $fields;
        }
    }

endif;
