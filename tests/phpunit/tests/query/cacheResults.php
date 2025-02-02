<?php

/**
 * @group query
 * @covers WP_Query::get_posts
 */
class Test_Query_CacheResults extends WP_UnitTestCase
{
    /**
     * Page IDs.
     *
     * @var int[]
     */
    public static $pages;

    /**
     * Post IDs.
     *
     * @var int[]
     */
    public static $posts;

    /**
     * Term ID.
     *
     * @var int
     */
    public static $t1;

    /**
     * Author's user ID.
     *
     * @var int
     */
    public static $author_id;

    /**
     * For testing test_generate_cache_key() includes a test containing the
     * placeholder within the generated SQL query.
     *
     * @var bool
     */
    public static $sql_placeholder_cache_key_tested = false;

    /**
     * For testing test_generate_cache_key() includes a test containing the
     * placeholder within the generated WP_Query variables.
     *
     * @var bool
     */
    public static $wp_query_placeholder_cache_key_tested = false;

    public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
    {
        // Make some post objects.
        self::$posts = $factory->post->create_many(5);
        self::$pages = $factory->post->create_many(5, ['post_type' => 'page']);

        self::$t1 = $factory->term->create(
            [
                'taxonomy' => 'category',
                'slug' => 'foo',
                'name' => 'Foo',
            ],
        );

        wp_set_post_terms(self::$posts[0], self::$t1, 'category');
        add_post_meta(self::$posts[0], 'color', '#000000');

        // Make a user.
        self::$author_id = $factory->user->create(
            [
                'role' => 'author',
            ],
        );
    }

    /**
     * Ensure cache keys are generated without WPDB placeholders.
     *
     * @ticket 56802
     *
     * @covers       WP_Query::generate_cache_key
     *
     * @dataProvider data_query_cache
     */
    public function test_generate_cache_key($args)
    {
        global $wpdb;
        $query1 = new WP_Query();
        $query1->query($args);

        $query_vars = $query1->query_vars;
        $request = $query1->request;
        $request_no_placeholder = $wpdb->remove_placeholder_escape($request);

        $this->assertStringNotContainsString($wpdb->placeholder_escape(), $request_no_placeholder,
            'Placeholder escape should be removed from the modified request.');

        if (str_contains($request, $wpdb->placeholder_escape())) {
            self::$sql_placeholder_cache_key_tested = true;
        }

        if (str_contains(serialize($query_vars), $wpdb->placeholder_escape())) {
            self::$wp_query_placeholder_cache_key_tested = true;
        }

        $reflection = new ReflectionMethod($query1, 'generate_cache_key');
        $reflection->setAccessible(true);

        $cache_key_1 = $reflection->invoke($query1, $query_vars, $request);
        $cache_key_2 = $reflection->invoke($query1, $query_vars, $request_no_placeholder);

        $this->assertSame($cache_key_1, $cache_key_2, 'Cache key differs when using wpdb placeholder.');
    }

    /**
     * Ensure cache keys tests include WPDB placeholder in SQL Query.
     *
     * @ticket 56802
     *
     * @covers  WP_Query::generate_cache_key
     *
     * @depends test_generate_cache_key
     */
    public function test_sql_placeholder_cache_key_tested()
    {
        $this->assertTrue(self::$sql_placeholder_cache_key_tested,
            'Cache key containing WPDB placeholder in SQL query was not tested.');
    }

    /**
     * Ensure cache keys tests include WPDB placeholder in WP_Query arguments.
     *
     * This test mainly covers the search query which generates the `search_orderby_title`
     * query_var in WP_Query.
     *
     * @ticket 56802
     *
     * @covers  WP_Query::generate_cache_key
     *
     * @depends test_generate_cache_key
     */
    public function test_wp_query_placeholder_cache_key_tested()
    {
        $this->assertTrue(self::$wp_query_placeholder_cache_key_tested,
            'Cache key containing WPDB placeholder in WP_Query arguments was not tested.');
    }

    /**
     * Ensure cache keys are generated without WPDB placeholders.
     *
     * @ticket 56802
     *
     * @covers WP_Query::generate_cache_key
     */
    public function test_generate_cache_key_placeholder()
    {
        global $wpdb;
        $query1 = new WP_Query();
        $query1->query([]);

        $query_vars = $query1->query_vars;
        $request = $query1->request;
        $query_vars['test']['nest'] = '%';
        $query_vars['test2']['nest']['nest']['nest'] = '%';
        $this->assertStringNotContainsString($wpdb->placeholder_escape(), serialize($query_vars),
            'Query vars should not contain the wpdb placeholder.');

        $reflection = new ReflectionMethod($query1, 'generate_cache_key');
        $reflection->setAccessible(true);

        $cache_key_1 = $reflection->invoke($query1, $query_vars, $request);

        $query_vars['test']['nest'] = $wpdb->placeholder_escape();
        $query_vars['test2']['nest']['nest']['nest'] = $wpdb->placeholder_escape();
        $this->assertStringContainsString($wpdb->placeholder_escape(), serialize($query_vars),
            'Query vars should not contain the wpdb placeholder.');

        $cache_key_2 = $reflection->invoke($query1, $query_vars, $request);

        $this->assertSame($cache_key_1, $cache_key_2, 'Cache key differs when using wpdb placeholder.');
    }

    /**
     * @covers WP_Query::generate_cache_key
     * @ticket 59442
     */
    public function test_generate_cache_key_unregister_post_type()
    {
        global $wpdb;
        register_post_type(
            'wptests_pt',
            [
                'exclude_from_search' => false,
            ],
        );
        $query_vars = [
            'post_type' => 'any',
        ];
        $fields = "{$wpdb->posts}.ID";
        $query1 = new WP_Query($query_vars);
        $request1 = str_replace($fields, "{$wpdb->posts}.*", $query1->request);

        $reflection = new ReflectionMethod($query1, 'generate_cache_key');
        $reflection->setAccessible(true);

        $cache_key_1 = $reflection->invoke($query1, $query_vars, $request1);
        unregister_post_type('wptests_pt');
        $cache_key_2 = $reflection->invoke($query1, $query_vars, $request1);

        $this->assertNotSame($cache_key_1, $cache_key_2, 'Cache key should differ after unregistering post type.');
    }

    /**
     * @ticket 59442
     *
     * @covers       WP_Query::generate_cache_key
     *
     * @dataProvider data_query_cache_duplicate
     */
    public function test_generate_cache_key_normalize($query_vars1, $query_vars2)
    {
        global $wpdb;

        $fields = "{$wpdb->posts}.ID";
        $query1 = new WP_Query($query_vars1);
        $request1 = str_replace($fields, "{$wpdb->posts}.*", $query1->request);

        $query2 = new WP_Query($query_vars2);
        $request2 = str_replace($fields, "{$wpdb->posts}.*", $query2->request);

        $reflection = new ReflectionMethod($query1, 'generate_cache_key');
        $reflection->setAccessible(true);

        $this->assertSame($request1, $request2, 'Queries should match');

        $cache_key_1 = $reflection->invoke($query1, $query_vars1, $request1);
        $cache_key_2 = $reflection->invoke($query1, $query_vars2, $request2);

        $this->assertSame($cache_key_1, $cache_key_2, 'Cache key differs the same paramters.');
    }

    /**
     * @dataProvider data_query_cache
     * @ticket 22176
     */
    public function test_query_cache($args)
    {
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        $queries_before = get_num_queries();
        $query2 = new WP_Query();
        $posts2 = $query2->query($args);
        $queries_after = get_num_queries();

        add_filter('split_the_query', '__return_false');
        $split_query = new WP_Query();
        $split_posts = $split_query->query($args);
        remove_filter('split_the_query', '__return_false');

        if (isset($args['fields'])) {
            if ('all' !== $args['fields']) {
                $this->assertSameSets($posts1, $posts2, 'Second query produces different set of posts to first.');
                $this->assertSameSets($posts1, $split_posts, 'Split query produces different set of posts to first.');
            }
            if ('id=>parent' !== $args['fields']) {
                $this->assertSame($queries_after, $queries_before, 'Second query produces unexpected DB queries.');
            }
        } else {
            $this->assertSame($queries_after, $queries_before, 'Second query produces unexpected DB queries.');
        }
        $this->assertSame($query1->found_posts, $query2->found_posts,
            'Second query has a different number of found posts to first.');
        $this->assertSame($query1->found_posts, $split_query->found_posts,
            'Split query has a different number of found posts to first.');
        $this->assertSame($query1->max_num_pages, $query2->max_num_pages,
            'Second query has a different number of total to first.');
        $this->assertSame($query1->max_num_pages, $split_query->max_num_pages,
            'Split query has a different number of total to first.');

        if (!$query1->query_vars['no_found_rows']) {
            wp_delete_post(self::$posts[0], true);
            wp_delete_post(self::$pages[0], true);
            $query3 = new WP_Query();
            $query3->query($args);

            $this->assertNotSame($query1->found_posts, $query3->found_posts);
            $this->assertNotSame($queries_after, get_num_queries());
        }
    }

    /**
     * Data provider for test_generate_cache_key_normalize().
     *
     * @return array[]
     */
    public function data_query_cache_duplicate()
    {
        return [
            'post type empty' => [
                'query_vars1' => ['post_type' => ''],
                'query_vars2' => ['post_type' => 'post'],
            ],
            'post type array' => [
                'query_vars1' => ['post_type' => ['page']],
                'query_vars2' => ['post_type' => 'page'],
            ],
            'orderby empty' => [
                'query_vars1' => ['orderby' => null],
                'query_vars2' => ['orderby' => 'date'],
            ],
            'different order parameter' => [
                'query_vars1' => [
                    'post_type' => 'post',
                    'posts_per_page' => 15,
                ],
                'query_vars2' => [
                    'posts_per_page' => 15,
                    'post_type' => 'post',
                ],
            ],
            'same args' => [
                'query_vars1' => ['post_type' => 'post'],
                'query_vars2' => ['post_type' => 'post'],
            ],
            'same args any' => [
                'query_vars1' => ['post_type' => 'any'],
                'query_vars2' => ['post_type' => 'any'],
            ],
            'any and post types' => [
                'query_vars1' => ['post_type' => 'any'],
                'query_vars2' => ['post_type' => ['post', 'page', 'attachment']],
            ],
            'different order post type' => [
                'query_vars1' => ['post_type' => ['post', 'page']],
                'query_vars2' => ['post_type' => ['page', 'post']],
            ],
            'post status array' => [
                'query_vars1' => ['post_status' => 'publish'],
                'query_vars2' => ['post_status' => ['publish']],
            ],
            'post status order' => [
                'query_vars1' => ['post_status' => ['draft', 'publish']],
                'query_vars2' => ['post_status' => ['publish', 'draft']],
            ],
            'cache parameters' => [
                'query_vars1' => [
                    'update_post_meta_cache' => true,
                    'update_post_term_cache' => true,
                    'update_menu_item_cache' => true,
                ],
                'query_vars2' => [
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                    'update_menu_item_cache' => false,
                ],
            ],
        ];
    }

    /**
     * Data provider.
     *
     * @return array[] Test parameters.
     */
    public function data_query_cache()
    {
        return [
            'cache true' => [
                'args' => [
                    'cache_results' => true,
                ],
            ],
            'cache true and pagination' => [
                'args' => [
                    'cache_results' => true,
                    'posts_per_page' => 3,
                    'page' => 2,
                ],
            ],
            'cache true and no pagination' => [
                'args' => [
                    'cache_results' => true,
                    'nopaging' => true,
                ],
            ],
            'cache true and post type any' => [
                'args' => [
                    'cache_results' => true,
                    'nopaging' => true,
                    'post_type' => 'any',
                ],
            ],
            'cache true and get all' => [
                'args' => [
                    'cache_results' => true,
                    'fields' => 'all',
                    'posts_per_page' => -1,
                    'post_status' => 'any',
                    'post_type' => 'any',
                ],
            ],
            'cache true and page' => [
                'args' => [
                    'cache_results' => true,
                    'post_type' => 'page',
                ],
            ],
            'cache true and empty post type' => [
                'args' => [
                    'cache_results' => true,
                    'post_type' => '',
                ],
            ],
            'cache true and orderby null' => [
                'args' => [
                    'cache_results' => true,
                    'orderby' => null,
                ],
            ],
            'cache true and ids' => [
                'args' => [
                    'cache_results' => true,
                    'fields' => 'ids',
                ],
            ],
            'cache true and id=>parent and no found rows' => [
                'args' => [
                    'cache_results' => true,
                    'fields' => 'id=>parent',
                ],
            ],
            'cache true and ids and no found rows' => [
                'args' => [
                    'no_found_rows' => true,
                    'cache_results' => true,
                    'fields' => 'ids',
                ],
            ],
            'cache true and id=>parent' => [
                'args' => [
                    'no_found_rows' => true,
                    'cache_results' => true,
                    'fields' => 'id=>parent',
                ],
            ],
            'cache and ignore_sticky_posts' => [
                'args' => [
                    'cache_results' => true,
                    'ignore_sticky_posts' => true,
                ],
            ],
            'cache meta query' => [
                'args' => [
                    'cache_results' => true,
                    'meta_query' => [
                        [
                            'key' => 'color',
                        ],
                    ],
                ],
            ],
            'cache meta query search' => [
                'args' => [
                    'cache_results' => true,
                    'meta_query' => [
                        [
                            'key' => 'color',
                            'value' => '00',
                            'compare' => 'LIKE',
                        ],
                    ],
                ],
            ],
            'cache nested meta query search' => [
                'args' => [
                    'cache_results' => true,
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key' => 'color',
                            'value' => '00',
                            'compare' => 'LIKE',
                        ],
                        [
                            'relation' => 'OR',
                            [
                                'key' => 'color',
                                'value' => '00',
                                'compare' => 'LIKE',
                            ],
                            [
                                'relation' => 'AND',
                                [
                                    'key' => 'wp_test_suite',
                                    'value' => '56802',
                                    'compare' => 'LIKE',
                                ],
                                [
                                    'key' => 'wp_test_suite_too',
                                    'value' => '56802',
                                    'compare' => 'LIKE',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'cache meta query not search' => [
                'args' => [
                    'cache_results' => true,
                    'meta_query' => [
                        [
                            'key' => 'color',
                            'value' => 'ff',
                            'compare' => 'NOT LIKE',
                        ],
                    ],
                ],
            ],
            'cache comment_count' => [
                'args' => [
                    'cache_results' => true,
                    'comment_count' => 0,
                ],
            ],
            'cache term query' => [
                'args' => [
                    'cache_results' => true,
                    'tax_query' => [
                        [
                            'taxonomy' => 'category',
                            'terms' => ['foo'],
                            'field' => 'slug',
                        ],
                    ],
                ],
            ],
            'cache search query' => [
                'args' => [
                    'cache_results' => true,
                    's' => 'title',
                ],
            ],
            'cache search query multiple terms' => [
                'args' => [
                    'cache_results' => true,
                    's' => 'Post title',
                ],
            ],
        ];
    }

    /**
     * @ticket 22176
     */
    public function test_seeded_random_queries_only_cache_post_objects()
    {
        $args = [
            'cache_results' => true,
            'fields' => 'ids',
            'orderby' => 'rand(6)',
        ];
        $query1 = new WP_Query();
        $query1->query($args);
        $queries_before = get_num_queries();

        $query2 = new WP_Query();
        $query2->query($args);

        $queries_after = get_num_queries();

        $this->assertNotSame($queries_before, $queries_after);
    }

    /**
     * @ticket 22176
     */
    public function test_unseeded_random_queries_only_cache_post_objects()
    {
        $args = [
            'cache_results' => true,
            'fields' => 'ids',
            'orderby' => 'rand',
        ];
        $query1 = new WP_Query();
        $query1->query($args);
        $queries_before = get_num_queries();

        $query2 = new WP_Query();
        $query2->query($args);

        $queries_after = get_num_queries();

        $this->assertNotSame($queries_before, $queries_after);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_filter_request()
    {
        $args = [
            'cache_results' => true,
            'fields' => 'ids',
        ];
        $query1 = new WP_Query();
        $query1->query($args);
        $queries_before = get_num_queries();

        add_filter('posts_request', [$this, 'filter_posts_request']);

        $query2 = new WP_Query();
        $query2->query($args);

        $queries_after = get_num_queries();

        $this->assertNotSame($queries_before, $queries_after);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_no_caching()
    {
        $args = [
            'cache_results' => true,
            'fields' => 'ids',
        ];
        $query1 = new WP_Query();
        $query1->query($args);
        $queries_before = get_num_queries();

        $query2 = new WP_Query();
        $args['cache_results'] = false;
        $query2->query($args);

        $queries_after = get_num_queries();

        $this->assertNotSame($queries_before, $queries_after);
    }

    public function filter_posts_request($request)
    {
        return $request . ' -- Add comment';
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_new_post()
    {
        $args = [
            'cache_results' => true,
            'fields' => 'ids',
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        $p1 = self::factory()->post->create();

        $query2 = new WP_Query();
        $posts2 = $query2->query($args);

        $this->assertNotSame($posts1, $posts2);
        $this->assertContains($p1, $posts2);
        $this->assertNotSame($query1->found_posts, $query2->found_posts);
    }

    /**
     * @ticket 22176
     */
    public function test_main_query_sticky_posts_change()
    {
        add_action('parse_query', [$this, 'set_cache_results']);
        update_option('posts_per_page', 5);

        $old_date = date_create('-25 hours');
        $old_post = self::factory()->post->create(['post_date' => $old_date->format('Y-m-d H:i:s')]);

        // Post is unstuck.
        $this->go_to('/');
        $unstuck = $GLOBALS['wp_query']->posts;
        $unstuck_ids = wp_list_pluck($unstuck, 'ID');

        $expected = array_reverse(self::$posts);
        $this->assertSame($expected, $unstuck_ids);

        // Stick the post.
        stick_post($old_post);

        $this->go_to('/');
        $stuck = $GLOBALS['wp_query']->posts;
        $stuck_ids = wp_list_pluck($stuck, 'ID');

        $expected = array_reverse(self::$posts);
        array_unshift($expected, $old_post);

        $this->assertSame($expected, $stuck_ids);
    }

    /**
     * @ticket 22176
     */
    public function test_main_query_in_query_sticky_posts_change()
    {
        add_action('parse_query', [$this, 'set_cache_results']);
        update_option('posts_per_page', 5);

        $middle_post = self::$posts[2];

        // Post is unstuck.
        $this->go_to('/');
        $unstuck = $GLOBALS['wp_query']->posts;
        $unstuck_ids = wp_list_pluck($unstuck, 'ID');

        $expected = array_reverse(self::$posts);
        $this->assertSame($expected, $unstuck_ids);

        // Stick the post.
        stick_post($middle_post);

        $this->go_to('/');
        $stuck = $GLOBALS['wp_query']->posts;
        $stuck_ids = wp_list_pluck($stuck, 'ID');

        $expected = array_diff(array_reverse(self::$posts), [$middle_post]);
        array_unshift($expected, $middle_post);

        $this->assertSame($expected, $stuck_ids);
    }

    /**
     * @ticket 22176
     */
    public function test_query_sticky_posts_change()
    {
        add_action('parse_query', [$this, 'set_cache_results']);

        $old_date = date_create('-25 hours');
        $old_post = self::factory()->post->create(['post_date' => $old_date->format('Y-m-d H:i:s')]);

        // Post is unstuck.
        $unstuck = new WP_Query(['posts_per_page' => 5]);
        $unstuck_ids = wp_list_pluck($unstuck->posts, 'ID');

        $expected = array_reverse(self::$posts);

        $this->assertSame($expected, $unstuck_ids);

        // Stick the post.
        stick_post($old_post);

        $stuck = new WP_Query(['posts_per_page' => 5]);
        $stuck_ids = wp_list_pluck($stuck->posts, 'ID');

        $expected = array_reverse(self::$posts);
        array_unshift($expected, $old_post);

        $this->assertSame($expected, $stuck_ids);

        // Ignore sticky posts.
        $ignore_stuck = new WP_Query(
            [
                'posts_per_page' => 5,
                'ignore_sticky_posts' => true,
            ],
        );
        $ignore_stuck_ids = wp_list_pluck($ignore_stuck->posts, 'ID');

        $expected = array_reverse(self::$posts);

        $this->assertSame($expected, $ignore_stuck_ids);

        // Just to make sure everything has changed.
        $this->assertNotSame($unstuck, $stuck);
    }

    /**
     * @ticket 22176
     */
    public function test_query_in_query_sticky_posts_change()
    {
        add_action('parse_query', [$this, 'set_cache_results']);

        $middle_post = self::$posts[2];

        // Post is unstuck.
        $unstuck = new WP_Query(['posts_per_page' => 5]);
        $unstuck_ids = wp_list_pluck($unstuck->posts, 'ID');

        $expected = array_reverse(self::$posts);

        $this->assertSame($expected, $unstuck_ids);

        // Stick the post.
        stick_post($middle_post);

        $stuck = new WP_Query(['posts_per_page' => 5]);
        $stuck_ids = wp_list_pluck($stuck->posts, 'ID');

        $expected = array_diff(array_reverse(self::$posts), [$middle_post]);
        array_unshift($expected, $middle_post);

        $this->assertSame($expected, $stuck_ids);

        // Ignore sticky posts.
        $ignore_stuck = new WP_Query(
            [
                'posts_per_page' => 5,
                'ignore_sticky_posts' => true,
            ],
        );
        $ignore_stuck_ids = wp_list_pluck($ignore_stuck->posts, 'ID');

        $expected = array_reverse(self::$posts);

        $this->assertSame($expected, $ignore_stuck_ids);

        // Just to make sure everything has changed.
        $this->assertNotSame($unstuck, $stuck);
    }

    public function set_cache_results($q)
    {
        $q->set('cache_results', true);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_different_args()
    {
        $args = [
            'cache_results' => true,
            'fields' => 'ids',
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        $args = [
            'cache_results' => true,
            'fields' => 'ids',
            'suppress_filters' => true,
            'cache_results' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'lazy_load_term_meta' => false,
        ];
        $queries_before = get_num_queries();
        $query2 = new WP_Query();
        $posts2 = $query2->query($args);
        $queries_after = get_num_queries();

        $this->assertSame($queries_before, $queries_after);
        $this->assertSame($posts1, $posts2);
        $this->assertSame($query1->found_posts, $query2->found_posts);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_different_fields()
    {
        $args = [
            'cache_results' => true,
            'fields' => 'all',
        ];
        $query1 = new WP_Query();
        $query1->query($args);

        $args = [
            'cache_results' => true,
            'fields' => 'id=>parent',
        ];
        $queries_before = get_num_queries();
        $query2 = new WP_Query();
        $query2->query($args);
        $queries_after = get_num_queries();

        $this->assertSame(1, $queries_after - $queries_before);
        $this->assertCount(5, $query1->posts);
        $this->assertCount(5, $query2->posts);
        $this->assertSame($query1->found_posts, $query2->found_posts);

        /*
         * Make sure the returned post objects differ due to the field argument.
         *
         * This uses assertNotEquals rather than assertNotSame as the former is
         * agnostic to the instance ID of objects, whereas the latter will take
         * it in to account. The test needs to discard the instance ID when
         * confirming inequality.
         */
        $this->assertNotEquals($query1->posts, $query2->posts);
    }


    /**
     * @ticket 59188
     */
    public function test_query_cache_unprimed_parents()
    {
        $args = [
            'cache_results' => true,
            'fields' => 'id=>parent',
        ];
        $query1 = new WP_Query();
        $query1->query($args);

        $post_ids = wp_list_pluck($query1->posts, 'ID');
        $cache_keys = array_map(
            function ($post_id) {
                return "post_parent:{$post_id}";
            },
            $post_ids,
        );

        wp_cache_delete_multiple($cache_keys, 'posts');

        $queries_before = get_num_queries();
        $query2 = new WP_Query();
        $query2->query($args);
        $queries_after = get_num_queries();

        $this->assertSame(1, $queries_after - $queries_before, 'There should be only one query to prime parents');
        $this->assertCount(5, $query1->posts, 'There should be only 5 posts returned on first query');
        $this->assertCount(5, $query2->posts, 'There should be only 5 posts returned on second query');
        $this->assertSame($query1->found_posts, $query2->found_posts, 'Found posts should match on second query');
    }

    /**
     * @ticket 59188
     */
    public function test_query_cache_update_parent()
    {
        $page_id = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_parent' => self::$pages[0],
            ],
        );
        $args = [
            'cache_results' => true,
            'post_type' => 'page',
            'fields' => 'id=>parent',
            'post__in' => [
                $page_id,
            ],
        ];
        $query1 = new WP_Query();
        $query1->query($args);

        wp_update_post(
            [
                'ID' => $page_id,
                'post_parent' => self::$pages[1],
            ],
        );

        $queries_before = get_num_queries();
        $query2 = new WP_Query();
        $query2->query($args);
        $queries_after = get_num_queries();

        $this->assertSame(self::$pages[0], $query1->posts[0]->post_parent, 'Check post parent on first query');
        $this->assertSame(self::$pages[1], $query2->posts[0]->post_parent, 'Check post parent on second query');
        $this->assertSame(2, $queries_after - $queries_before, 'There should be 2 queries, one for id=>parent');
        $this->assertSame($query1->found_posts, $query2->found_posts, 'Found posts should match on second query');
    }

    /**
     * @ticket 59188
     */
    public function test_query_cache_delete_parent()
    {
        $parent_page_id = self::factory()->post->create(
            [
                'post_type' => 'page',
            ],
        );
        $page_id = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_parent' => $parent_page_id,
            ],
        );
        $args = [
            'cache_results' => true,
            'post_type' => 'page',
            'fields' => 'id=>parent',
            'post__in' => [
                $page_id,
            ],
        ];
        $query1 = new WP_Query();
        $query1->query($args);

        wp_delete_post($parent_page_id, true);

        $queries_before = get_num_queries();
        $query2 = new WP_Query();
        $query2->query($args);
        $queries_after = get_num_queries();

        $this->assertSame($parent_page_id, $query1->posts[0]->post_parent, 'Check post parent on first query');
        $this->assertSame(0, $query2->posts[0]->post_parent, 'Check post parent on second query');
        $this->assertSame(2, $queries_after - $queries_before, 'There should be 2 queries, one for id=>parent');
        $this->assertSame($query1->found_posts, $query2->found_posts, 'Found posts should match on second query');
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_logged_in()
    {
        $user_id = self::$author_id;

        self::factory()->post->create(
            [
                'post_status' => 'private',
                'post_author' => $user_id,
            ],
        );

        $args = [
            'cache_results' => true,
            'author' => $user_id,
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        wp_set_current_user($user_id);

        $query2 = new WP_Query();
        $posts2 = $query2->query($args);
        $this->assertEmpty($posts1);
        $this->assertNotSame($posts1, $posts2);
        $this->assertNotSame($query1->found_posts, $query2->found_posts);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_logged_in_password()
    {
        $user_id = self::$author_id;
        self::factory()->post->create(
            [
                'post_title' => 'foo',
                'post_password' => 'password',
                'post_author' => $user_id,
            ],
        );

        $args = [
            'cache_results' => true,
            's' => 'foo',
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        wp_set_current_user($user_id);

        $query2 = new WP_Query();
        $posts2 = $query2->query($args);
        $this->assertEmpty($posts1);
        $this->assertNotSame($posts1, $posts2);
        $this->assertNotSame($query1->found_posts, $query2->found_posts);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_new_comment()
    {
        $args = [
            'cache_results' => true,
            'fields' => 'ids',
            'comment_count' => 1,
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        self::factory()->comment->create(['comment_post_ID' => self::$posts[0]]);

        $query2 = new WP_Query();
        $posts2 = $query2->query($args);

        $this->assertNotSame($posts1, $posts2);
        $this->assertContains(self::$posts[0], $posts2);
        $this->assertNotEmpty($posts2);
        $this->assertNotSame($query1->found_posts, $query2->found_posts);
    }

    /**
     * @ticket 22176
     */
    public function test_main_comments_feed_includes_attachment_comments()
    {
        $attachment_id = self::factory()->post->create(['post_type' => 'attachment']);
        $comment_id = self::factory()->comment->create(
            [
                'comment_post_ID' => $attachment_id,
                'comment_approved' => '1',
            ],
        );

        $args = [
            'cache_results' => true,
            'withcomments' => 1,
            'feed' => 'feed',
        ];
        $query1 = new WP_Query();
        $query1->query($args);

        $query2 = new WP_Query();
        $query2->query($args);

        $this->assertTrue($query1->have_comments());
        $this->assertTrue($query2->have_comments());

        $feed_comment = $query1->next_comment();
        $this->assertEquals($comment_id, $feed_comment->comment_ID);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_delete_comment()
    {
        $comment_id = self::factory()->comment->create(['comment_post_ID' => self::$posts[0]]);
        $args = [
            'cache_results' => true,
            'fields' => 'ids',
            'comment_count' => 1,
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        wp_delete_comment($comment_id, true);

        $query2 = new WP_Query();
        $posts2 = $query2->query($args);

        $this->assertNotSame($posts1, $posts2);
        $this->assertEmpty($posts2);
        $this->assertNotSame($query1->found_posts, $query2->found_posts);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_update_post()
    {
        $p1 = self::$posts[0];

        $args = [
            'cache_results' => true,
            'fields' => 'ids',
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        wp_update_post(
            [
                'ID' => $p1,
                'post_status' => 'draft',
            ],
        );

        $query2 = new WP_Query();
        $posts2 = $query2->query($args);

        $this->assertNotSame($posts1, $posts2);
        $this->assertContains($p1, $posts1);
        $this->assertNotContains($p1, $posts2);
        $this->assertNotSame($query1->found_posts, $query2->found_posts);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_new_meta()
    {
        $p1 = self::$posts[1]; // Post 0 already has a color meta value.

        $args = [
            'cache_results' => true,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'color',
                ],
            ],
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        add_post_meta($p1, 'color', 'black');

        $query2 = new WP_Query();
        $posts2 = $query2->query($args);

        $this->assertNotSame($posts1, $posts2);
        $this->assertContains($p1, $posts2);
        $this->assertNotSame($query1->found_posts, $query2->found_posts);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_update_meta()
    {
        // Posts[0] already has a color meta value set to #000000.
        $p1 = self::$posts[0];

        $args = [
            'cache_results' => true,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'color',
                    'value' => '#000000',
                ],
            ],
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        update_post_meta($p1, 'color', 'blue');

        $query2 = new WP_Query();
        $posts2 = $query2->query($args);

        $this->assertNotSame($posts1, $posts2);
        $this->assertContains($p1, $posts1);
        $this->assertEmpty($posts2);
        $this->assertNotSame($query1->found_posts, $query2->found_posts);
    }


    /**
     * @ticket 22176
     */
    public function test_query_cache_delete_attachment()
    {
        $p1 = self::factory()->post->create(
            [
                'post_type' => 'attachment',
                'post_status' => 'inherit',
            ],
        );

        $args = [
            'cache_results' => true,
            'fields' => 'ids',
            'post_type' => 'attachment',
            'post_status' => 'inherit',
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        wp_delete_attachment($p1);

        $query2 = new WP_Query();
        $posts2 = $query2->query($args);

        $this->assertNotSame($posts1, $posts2);
        $this->assertContains($p1, $posts1);
        $this->assertEmpty($posts2);
        $this->assertNotSame($query1->found_posts, $query2->found_posts);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_delete_meta()
    {
        // Post 0 already has a color meta value.
        $p1 = self::$posts[1];
        add_post_meta($p1, 'color', 'black');

        $args = [
            'cache_results' => true,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'color',
                ],
            ],
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        delete_post_meta($p1, 'color');

        $query2 = new WP_Query();
        $posts2 = $query2->query($args);

        $this->assertNotSame($posts1, $posts2);
        $this->assertContains($p1, $posts1);
        $this->assertNotEmpty($posts2);
        $this->assertNotSame($query1->found_posts, $query2->found_posts);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_new_term()
    {
        // Post 0 already has the category foo.
        $p1 = self::$posts[1];

        $args = [
            'cache_results' => true,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'category',
                    'terms' => ['foo'],
                    'field' => 'slug',
                ],
            ],
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        wp_set_post_terms($p1, [self::$t1], 'category');

        $query2 = new WP_Query();
        $posts2 = $query2->query($args);

        $this->assertNotSame($posts1, $posts2);
        $this->assertContains($p1, $posts2);
        $this->assertNotSame($query1->found_posts, $query2->found_posts);
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_delete_term()
    {
        // Post 0 already has the category foo.
        $p1 = self::$posts[1];
        register_taxonomy('wptests_tax1', 'post');

        $t1 = self::factory()->term->create(['taxonomy' => 'wptests_tax1']);

        wp_set_object_terms($p1, [$t1], 'wptests_tax1');

        $args = [
            'cache_results' => true,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'wptests_tax1',
                    'terms' => [$t1],
                    'field' => 'term_id',
                ],
            ],
        ];
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);

        wp_delete_term($t1, 'wptests_tax1');

        $query2 = new WP_Query();
        $posts2 = $query2->query($args);

        $this->assertNotSame($posts1, $posts2);
        $this->assertContains($p1, $posts1);
        $this->assertEmpty($posts2);
        $this->assertNotSame($query1->found_posts, $query2->found_posts);
    }

    /**
     * @ticket 58599
     */
    public function test_query_posts_fields_request()
    {
        global $wpdb;

        $args = [
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'no_found_rows' => true,
        ];

        add_filter('posts_fields_request', [$this, 'filter_posts_fields_request']);

        $before = get_num_queries();
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);
        $after = get_num_queries();

        foreach ($posts1 as $_post) {
            $this->assertNotSame(get_post($_post->ID)->post_content, $_post->post_content);
        }

        $this->assertSame(2, $after - $before,
            'There should only be 2 queries run, one for request and one prime post objects.');

        $this->assertStringContainsString(
            "SELECT $wpdb->posts.*",
            $wpdb->last_query,
            'Check that _prime_post_caches is called.',
        );
    }

    public function filter_posts_fields_request($fields)
    {
        global $wpdb;
        return "{$wpdb->posts}.ID";
    }

    /**
     * @ticket 58599
     * @dataProvider data_query_filter_posts_results
     */
    public function test_query_filter_posts_results($filter)
    {
        global $wpdb;

        $args = [
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'no_found_rows' => true,
        ];

        add_filter($filter, [$this, 'filter_posts_results']);

        $before = get_num_queries();
        $query1 = new WP_Query();
        $posts1 = $query1->query($args);
        $after = get_num_queries();

        $this->assertCount(1, $posts1);

        $this->assertSame(2, $after - $before,
            'There should only be 2 queries run, one for request and one prime post objects.');

        $this->assertStringContainsString(
            "SELECT $wpdb->posts.*",
            $wpdb->last_query,
            'Check that _prime_post_caches is called.',
        );
    }

    public function filter_posts_results()
    {
        return [get_post(self::$posts[0])];
    }

    public function data_query_filter_posts_results()
    {
        return [
            ['posts_results'],
            ['the_posts'],
        ];
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_should_exclude_post_with_excluded_term()
    {
        $term_id = self::$t1;
        // Post 0 has the term applied
        $post_id = self::$posts[0];

        $args = [
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'category',
                    'terms' => [$term_id],
                    'operator' => 'NOT IN',
                ],
            ],
        ];

        $post_ids_q1 = get_posts($args);
        $this->assertNotContains($post_id, $post_ids_q1, 'First query includes the post ID.');

        $num_queries = get_num_queries();
        $post_ids_q2 = get_posts($args);
        $this->assertNotContains($post_id, $post_ids_q2, 'Second query includes the post ID.');

        $this->assertSame($num_queries, get_num_queries(), 'Second query is not cached.');
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_should_exclude_post_when_excluded_term_is_added_after_caching()
    {
        $term_id = self::$t1;
        // Post 1 does not have the term applied.
        $post_id = self::$posts[1];

        $args = [
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'category',
                    'terms' => [$term_id],
                    'operator' => 'NOT IN',
                ],
            ],
        ];

        $post_ids_q1 = get_posts($args);
        $this->assertContains($post_id, $post_ids_q1, 'First query does not include the post ID.');

        wp_set_object_terms($post_id, [$term_id], 'category');

        $num_queries = get_num_queries();
        $post_ids_q2 = get_posts($args);
        $this->assertNotContains($post_id, $post_ids_q2, 'Second query includes the post ID.');
        $this->assertNotSame($num_queries, get_num_queries(), 'Applying term does not invalidate previous cache.');
    }

    /**
     * @ticket 22176
     */
    public function test_query_cache_should_not_exclude_post_when_excluded_term_is_removed_after_caching()
    {
        $term_id = self::$t1;
        // Post 0 has the term applied.
        $post_id = self::$posts[0];

        $args = [
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'category',
                    'terms' => [$term_id],
                    'operator' => 'NOT IN',
                ],
            ],
        ];

        $post_ids_q1 = get_posts($args);
        $this->assertNotContains($post_id, $post_ids_q1, 'First query includes the post ID.');

        // Clear the post of terms.
        wp_set_object_terms($post_id, [], 'category');

        $num_queries = get_num_queries();
        $post_ids_q2 = get_posts($args);
        $this->assertContains($post_id, $post_ids_q2, 'Second query does not include the post ID.');
        $this->assertNotSame($num_queries, get_num_queries(), 'Removing term does not invalidate previous cache.');
    }

    /**
     * @ticket 22176
     * @dataProvider data_query_cache_with_empty_result_set
     */
    public function test_query_cache_with_empty_result_set($fields_q1, $fields_q2)
    {
        _delete_all_posts();

        $args_q1 = [
            'fields' => $fields_q1,
        ];

        $query_1 = new WP_Query();
        $posts_q1 = $query_1->query($args_q1);
        $this->assertEmpty($posts_q1, 'First query does not return an empty result set.');

        $args_q2 = [
            'fields' => $fields_q2,
        ];

        $num_queries = get_num_queries();
        $query_2 = new WP_Query();
        $posts_q2 = $query_2->query($args_q2);
        $this->assertEmpty($posts_q2, 'Second query does not return an empty result set.');
        $this->assertSame($num_queries, get_num_queries(), 'Second query is not cached.');
    }

    public function data_query_cache_with_empty_result_set()
    {
        return [
            ['', ''],
            ['', 'ids'],
            ['', 'id=>parent'],

            ['ids', ''],
            ['ids', 'ids'],
            ['ids', 'id=>parent'],

            ['id=>parent', ''],
            ['id=>parent', 'ids'],
            ['id=>parent', 'id=>parent'],
        ];
    }

    /**
     * Ensure starting the loop warms the author cache.
     *
     * @param string $fields Query fields.
     * @since 6.1.1
     * @ticket 56948
     *
     * @covers       WP_Query::the_post
     *
     * @dataProvider data_author_cache_warmed_by_the_loop
     *
     */
    public function test_author_cache_warmed_by_the_loop($fields)
    {
        // Update post author for the parent post.
        self::factory()->post->update_object(self::$pages[0], ['post_author' => self::$author_id]);

        self::factory()->post->create(
            [
                'post_author' => self::$author_id,
                'post_parent' => self::$pages[0],
                'post_type' => 'page',
            ],
        );

        $query_1 = new WP_Query(
            [
                'post_type' => 'page',
                'fields' => $fields,
                'author' => self::$author_id,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ],
        );

        // Start the loop.
        $start_loop_queries = get_num_queries();
        $query_1->the_post();
        $num_loop_queries = get_num_queries() - $start_loop_queries;
        /*
         * Two expected queries:
         * 1: User meta data,
         * 2: User data.
         */
        $this->assertSame(2, $num_loop_queries, 'Unexpected number of queries while initializing the loop.');

        $start_author_queries = get_num_queries();
        get_user_by('ID', self::$author_id);
        $num_author_queries = get_num_queries() - $start_author_queries;
        $this->assertSame(0, $num_author_queries, 'Author cache is not warmed by the loop.');
    }

    /**
     * Data provider for test_author_cache_warmed_by_the_loop
     *
     * @return array[]
     */
    public function data_author_cache_warmed_by_the_loop()
    {
        return [
            'fields: empty' => [''],
            'fields: all' => ['all'],
            'fields: ids' => ['ids'],
            /*
             * `id=>parent` is untested pending the resolution of an existing bug.
             * See https://core.trac.wp.org/ticket/56992
             */
        ];
    }

    /**
     * Ensure lazy loading term meta queries all term meta in a single query.
     *
     * @since 6.2.0
     *
     * @ticket 57163
     * @ticket 22176
     */
    public function test_get_post_meta_lazy_loads_all_term_meta_data()
    {
        $query = new WP_Query();

        $t2 = $this->factory()->term->create(
            [
                'taxonomy' => 'category',
                'slug' => 'bar',
                'name' => 'Bar',
            ],
        );

        wp_set_post_terms(self::$posts[0], $t2, 'category', true);
        // Clean data added to cache by factory and setting terms.
        clean_term_cache([self::$t1, $t2], 'category');
        clean_post_cache(self::$posts[0]);

        $num_queries_start = get_num_queries();
        $query_posts = $query->query(
            [
                'lazy_load_term_meta' => true,
                'no_found_rows' => true,
            ],
        );
        $num_queries = get_num_queries() - $num_queries_start;

        /*
         * Four expected queries:
         * 1: Post IDs
         * 2: Post data
         * 3: Post meta data.
         * 4: Post term data.
         */
        $this->assertSame(4, $num_queries, 'Unexpected number of queries while querying posts.');
        $this->assertNotEmpty($query_posts, 'Query posts is empty.');

        $num_queries_start = get_num_queries();
        get_term_meta(self::$t1);
        $num_queries = get_num_queries() - $num_queries_start;

        /*
         * One expected query:
         * 1: Term meta data.
         */
        $this->assertSame(1, $num_queries, 'Unexpected number of queries during first query of term meta.');

        $num_queries_start = get_num_queries();
        get_term_meta($t2);
        $num_queries = get_num_queries() - $num_queries_start;

        // No additional queries expected.
        $this->assertSame(0, $num_queries, 'Unexpected number of queries during second query of term meta.');
    }
}
