<?php

/**
 * @group canonical
 * @group rewrite
 * @group query
 */
class Tests_Canonical_PostStatus extends WP_Canonical_UnitTestCase
{

    /**
     * User IDs.
     *
     * @var array
     */
    public static $users;

    /**
     * Post Objects.
     *
     * @var array
     */
    public static $posts;

    public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
    {
        self::setup_custom_types();
        self::$users = [
            'anon' => 0,
            'subscriber' => $factory->user->create(['role' => 'subscriber']),
            'content_author' => $factory->user->create(['role' => 'author']),
            'editor' => $factory->user->create(['role' => 'editor']),
        ];

        $post_statuses = ['publish', 'future', 'draft', 'pending', 'private', 'auto-draft', 'a-private-status'];
        foreach ($post_statuses as $post_status) {
            $post_date = '';
            if ('future' === $post_status) {
                $post_date = date_format(date_create('+1 year'), 'Y-m-d H:i:s');
            }

            self::$posts[$post_status] = $factory->post->create_and_get(
                [
                    'post_type' => 'post',
                    'post_title' => "$post_status post",
                    'post_name' => "$post_status-post",
                    'post_status' => $post_status,
                    'post_content' => "Prevent canonical redirect exposing post slugs.\n\n<!--nextpage-->Page 2",
                    'post_author' => self::$users['content_author'],
                    'post_date' => $post_date,
                ],
            );

            // Add fake attachment to the post (file upload not needed).
            self::$posts["$post_status-attachment"] = $factory->post->create_and_get(
                [
                    'post_type' => 'attachment',
                    'post_title' => "$post_status inherited attachment",
                    'post_name' => "$post_status-inherited-attachment",
                    'post_status' => 'inherit',
                    'post_content' => "Prevent canonical redirect exposing post via attachments.\n\n<!--nextpage-->Page 2",
                    'post_author' => self::$users['content_author'],
                    'post_parent' => self::$posts[$post_status]->ID,
                    'post_date' => $post_date,
                ],
            );

            // Set up a page with same.
            self::$posts["$post_status-page"] = $factory->post->create_and_get(
                [
                    'post_type' => 'page',
                    'post_title' => "$post_status page",
                    'post_name' => "$post_status-page",
                    'post_status' => $post_status,
                    'post_content' => "Prevent canonical redirect exposing page slugs.\n\n<!--nextpage-->Page 2",
                    'post_author' => self::$users['content_author'],
                    'post_date' => $post_date,
                ],
            );
        }

        // Create a public CPT using a private status.
        self::$posts['a-public-cpt'] = $factory->post->create_and_get(
            [
                'post_type' => 'a-public-cpt',
                'post_title' => 'a-public-cpt',
                'post_name' => 'a-public-cpt',
                'post_status' => 'private',
                'post_content' => 'Prevent canonical redirect exposing a-public-cpt titles.',
                'post_author' => self::$users['content_author'],
            ],
        );

        // Add fake attachment to the public cpt (file upload not needed).
        self::$posts['a-public-cpt-attachment'] = $factory->post->create_and_get(
            [
                'post_type' => 'attachment',
                'post_title' => 'a-public-cpt post inherited attachment',
                'post_name' => 'a-public-cpt-inherited-attachment',
                'post_status' => 'inherit',
                'post_content' => "Prevent canonical redirect exposing post via attachments.\n\n<!--nextpage-->Page 2",
                'post_author' => self::$users['content_author'],
                'post_parent' => self::$posts['a-public-cpt']->ID,
            ],
        );

        // Create a private CPT with a public status.
        self::$posts['a-private-cpt'] = $factory->post->create_and_get(
            [
                'post_type' => 'a-private-cpt',
                'post_title' => 'a-private-cpt',
                'post_name' => 'a-private-cpt',
                'post_status' => 'publish',
                'post_content' => 'Prevent canonical redirect exposing a-private-cpt titles.',
                'post_author' => self::$users['content_author'],
            ],
        );

        // Add fake attachment to the private cpt (file upload not needed).
        self::$posts['a-private-cpt-attachment'] = $factory->post->create_and_get(
            [
                'post_type' => 'attachment',
                'post_title' => 'a-private-cpt post inherited attachment',
                'post_name' => 'a-private-cpt-inherited-attachment',
                'post_status' => 'inherit',
                'post_content' => "Prevent canonical redirect exposing post via attachments.\n\n<!--nextpage-->Page 2",
                'post_author' => self::$users['content_author'],
                'post_parent' => self::$posts['a-private-cpt']->ID,
            ],
        );

        // Post for trashing.
        self::$posts['trash'] = $factory->post->create_and_get(
            [
                'post_type' => 'post',
                'post_title' => 'trash post',
                'post_name' => 'trash-post',
                'post_status' => 'publish',
                'post_content' => "Prevent canonical redirect exposing post slugs.\n\n<!--nextpage-->Page 2",
                'post_author' => self::$users['content_author'],
            ],
        );

        self::$posts['trash-attachment'] = $factory->post->create_and_get(
            [
                'post_type' => 'attachment',
                'post_title' => 'trash post inherited attachment',
                'post_name' => 'trash-post-inherited-attachment',
                'post_status' => 'inherit',
                'post_content' => "Prevent canonical redirect exposing post via attachments.\n\n<!--nextpage-->Page 2",
                'post_author' => self::$users['content_author'],
                'post_parent' => self::$posts['trash']->ID,
            ],
        );

        // Page for trashing.
        self::$posts['trash-page'] = $factory->post->create_and_get(
            [
                'post_type' => 'page',
                'post_title' => 'trash page',
                'post_name' => 'trash-page',
                'post_status' => 'publish',
                'post_content' => "Prevent canonical redirect exposing page slugs.\n\n<!--nextpage-->Page 2",
                'post_author' => self::$users['content_author'],
            ],
        );
        wp_trash_post(self::$posts['trash']->ID);
        wp_trash_post(self::$posts['trash-page']->ID);
    }

    public function set_up()
    {
        parent::set_up();
        self::setup_custom_types();
    }

    /**
     * Set up a custom post type and private status.
     *
     * This needs to be called both in the class setup and
     * test setup.
     */
    public static function setup_custom_types()
    {
        // Register public custom post type.
        register_post_type(
            'a-public-cpt',
            [
                'public' => true,
                'rewrite' => [
                    'slug' => 'a-public-cpt',
                ],
            ],
        );

        // Register private custom post type.
        register_post_type(
            'a-private-cpt',
            [
                'public' => false,
                'publicly_queryable' => false,
                'rewrite' => [
                    'slug' => 'a-private-cpt',
                ],
                'map_meta_cap' => true,
            ],
        );

        // Register custom private post status.
        register_post_status(
            'a-private-status',
            [
                'private' => true,
            ],
        );
    }

    /**
     * Test canonical redirect does not reveal private posts presence.
     *
     * @ticket 5272
     * @dataProvider data_canonical_redirects_to_plain_permalinks
     *
     * @param string $post_key Post key used for creating fixtures.
     * @param string $user_role User role.
     * @param string $requested Requested URL.
     * @param string $expected Expected URL.
     * @param string $enable_attachment_pages Whether to enable attachment pages. Default true.
     */
    public function test_canonical_redirects_to_plain_permalinks(
        $post_key,
        $user_role,
        $requested,
        $expected,
        $enable_attachment_pages = true,
    ) {
        if ($enable_attachment_pages) {
            update_option('wp_attachment_pages_enabled', 1);
        } else {
            update_option('wp_attachment_pages_enabled', 0);
        }

        wp_set_current_user(self::$users[$user_role]);
        $this->set_permalink_structure('');
        $post = self::$posts[$post_key];
        clean_post_cache($post->ID);

        /*
         * The dataProvider runs before the fixures are set up, therefore the
         * post object IDs are placeholders that needs to be replaced.
         */
        $requested = str_replace('%ID%', $post->ID, $requested);
        $expected = str_replace('%ID%', $post->ID, $expected);

        $this->assertCanonical($requested, $expected);
    }

    /**
     * Data provider for test_canonical_redirects_to_plain_permalinks.
     *
     * @return array[]
     */
    public function data_canonical_redirects_to_plain_permalinks()
    {
        $data = [];
        $all_user_list = ['anon', 'subscriber', 'content_author', 'editor'];
        $select_allow_list = ['content_author', 'editor'];
        $select_block_list = ['anon', 'subscriber'];
        // All post/page keys
        $all_user_post_status_keys = ['publish'];
        $select_user_post_status_keys = ['private', 'a-private-status'];
        $no_user_post_status_keys = [
            'future',
            'draft',
            'pending',
            'auto-draft',
        ]; // Excludes trash for attachment rules.
        $select_user_post_type_keys = ['a-public-cpt'];
        $no_user_post_type_keys = ['a-private-cpt'];

        foreach ($all_user_post_status_keys as $post_key) {
            foreach ($all_user_list as $user) {
                /*
                 * In the event `redirect_canonical()` is updated to redirect plain permalinks
                 * to a canonical plain version, these expected values can be changed.
                 */
                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    false,
                ];

                // Ensure rss redirects to rss2.
                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss2&p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss2&p=%ID%',
                    false,
                ];

                // Ensure rss redirects to rss2.
                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss2&page_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss2&page_id=%ID%',
                    false,
                ];
            }
        }

        foreach ($select_user_post_status_keys as $post_key) {
            foreach ($select_allow_list as $user) {
                /*
                 * In the event `redirect_canonical()` is updated to redirect plain permalinks
                 * to a canonical plain version, these expected values can be changed.
                 */
                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    false,
                ];

                // Ensure rss redirects to rss2.
                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss2&p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss2&p=%ID%',
                    false,
                ];

                // Ensure rss redirects to rss2.
                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss2&page_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss2&page_id=%ID%',
                    false,
                ];
            }

            foreach ($select_block_list as $user) {
                /*
                 * In the event `redirect_canonical()` is updated to redirect plain permalinks
                 * to a canonical plain version, these expected values MUST NOT be changed.
                 */
                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    false,
                ];

                // Ensure post's existence is not demonstrated by changing rss to rss2.
                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    false,
                ];

                // Ensure post's existence is not demonstrated by changing rss to rss2.
                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss&page_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss&page_id=%ID%',
                    false,
                ];
            }
        }

        foreach ($no_user_post_status_keys as $post_key) {
            foreach ($all_user_list as $user) {
                /*
                 * In the event `redirect_canonical()` is updated to redirect plain permalinks
                 * to a canonical plain version, these expected values MUST NOT be changed.
                 */
                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    false,
                ];

                // Ensure post's existence is not demonstrated by changing rss to rss2.
                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    false,
                ];

                // Ensure post's existence is not demonstrated by changing rss to rss2.
                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss&page_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss&page_id=%ID%',
                    false,
                ];
            }
        }

        foreach (['trash'] as $post_key) {
            foreach ($all_user_list as $user) {
                /*
                 * In the event `redirect_canonical()` is updated to redirect plain permalinks
                 * to a canonical plain version, these expected values MUST NOT be changed.
                 */
                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    false,
                ];

                // Ensure post's existence is not demonstrated by changing rss to rss2.
                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    false,
                ];

                // Ensure post's existence is not demonstrated by changing rss to rss2.
                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss&page_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss&page_id=%ID%',
                    false,
                ];
            }
        }

        foreach ($select_user_post_type_keys as $post_key) {
            foreach ($select_allow_list as $user) {
                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?a-public-cpt=a-public-cpt',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?a-public-cpt=a-public-cpt',
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key&post_type=$post_key",
                    "/?name=$post_key&post_type=$post_key",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key&post_type=$post_key",
                    "/?name=$post_key&post_type=$post_key",
                    false,
                ];

                // Ensure rss is replaced by rss2.
                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?a-public-cpt=a-public-cpt&feed=rss2',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?a-public-cpt=a-public-cpt&feed=rss2',
                    false,
                ];
            }

            foreach ($select_block_list as $user) {
                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key&post_type=$post_key",
                    "/?name=$post_key&post_type=$post_key",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key&post_type=$post_key",
                    "/?name=$post_key&post_type=$post_key",
                    false,
                ];

                // Ensure rss is not replaced with rss2.
                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    false,
                ];
            }
        }

        foreach ($no_user_post_type_keys as $post_key) {
            foreach ($all_user_list as $user) {
                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key&post_type=$post_key",
                    "/?name=$post_key&post_type=$post_key",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key&post_type=$post_key",
                    "/?name=$post_key&post_type=$post_key",
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    false,
                ];
            }
        }

        return $data;
    }

    /**
     * Test canonical redirect does not reveal private slugs.
     *
     * @ticket 5272
     * @dataProvider data_canonical_redirects_to_pretty_permalinks
     *
     * @param string $post_key Post key used for creating fixtures.
     * @param string $user_role User role.
     * @param string $requested Requested URL.
     * @param string $expected Expected URL.
     * @param string $enable_attachment_pages Whether to enable attachment pages. Default true.
     */
    public function test_canonical_redirects_to_pretty_permalinks(
        $post_key,
        $user_role,
        $requested,
        $expected,
        $enable_attachment_pages = true,
    ) {
        if ($enable_attachment_pages) {
            update_option('wp_attachment_pages_enabled', 1);
        } else {
            update_option('wp_attachment_pages_enabled', 0);
        }

        wp_set_current_user(self::$users[$user_role]);
        $this->set_permalink_structure('/%postname%/');
        $post = self::$posts[$post_key];
        clean_post_cache($post->ID);

        /*
         * The dataProvider runs before the fixures are set up, therefore the
         * post object IDs are placeholders that needs to be replaced.
         */
        $requested = str_replace('%ID%', $post->ID, $requested);
        $expected = str_replace('%ID%', $post->ID, $expected);

        $this->assertCanonical($requested, $expected);
    }

    /**
     * Data provider for test_canonical_redirects_to_pretty_permalinks.
     *
     * @return array[] Array of arguments for tests {
     * @type string $post_key Post key used for creating fixtures.
     * @type string $user_role User role.
     * @type string $requested Requested URL.
     * @type string $expected Expected URL.
     * }
     */
    public function data_canonical_redirects_to_pretty_permalinks()
    {
        $data = [];
        $all_user_list = ['anon', 'subscriber', 'content_author', 'editor'];
        $select_allow_list = ['content_author', 'editor'];
        $select_block_list = ['anon', 'subscriber'];
        // All post/page keys
        $all_user_post_status_keys = ['publish'];
        $select_user_post_status_keys = ['private', 'a-private-status'];
        $no_user_post_status_keys = [
            'future',
            'draft',
            'pending',
            'auto-draft',
        ]; // Excludes trash for attachment rules.
        $select_user_post_type_keys = ['a-public-cpt'];
        $no_user_post_type_keys = ['a-private-cpt'];

        foreach ($all_user_post_status_keys as $post_key) {
            foreach ($all_user_list as $user) {
                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    "/$post_key-post/",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    "/$post_key-post/",
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    "/$post_key-post/$post_key-inherited-attachment/",
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    "/$post_key-page/",
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    "/$post_key-page/",
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?page_id=%ID%',
                    "/$post_key-page/",
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?page_id=%ID%',
                    "/$post_key-page/",
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/$post_key-post/",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/$post_key-post/",
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    "/$post_key-post/feed/",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    "/$post_key-post/feed/",
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    "/$post_key-page/feed/",
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    "/$post_key-page/feed/",
                    false,
                ];
            }
        }

        foreach ($select_user_post_status_keys as $post_key) {
            foreach ($select_allow_list as $user) {
                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    "/$post_key-post/",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    "/$post_key-post/",
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    "/$post_key-post/$post_key-inherited-attachment/",
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    "/$post_key-page/",
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    "/$post_key-page/",
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?page_id=%ID%',
                    "/$post_key-page/",
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?page_id=%ID%',
                    "/$post_key-page/",
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/$post_key-post/",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/$post_key-post/",
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    "/$post_key-post/feed/",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    "/$post_key-post/feed/",
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    "/$post_key-page/feed/",
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    "/$post_key-page/feed/",
                    false,
                ];
            }

            foreach ($select_block_list as $user) {
                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?page_id=%ID%',
                    '/?page_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?page_id=%ID%',
                    '/?page_id=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss&page_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss&page_id=%ID%',
                    false,
                ];
            }
        }

        foreach ($select_user_post_type_keys as $post_key) {
            foreach ($select_allow_list as $user) {
                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    "/$post_key/$post_key/",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    "/$post_key/$post_key/",
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    "/$post_key/$post_key/$post_key-inherited-attachment/",
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key&post_type=$post_key",
                    "/$post_key/$post_key/?post_type=$post_key",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key&post_type=$post_key",
                    "/$post_key/$post_key/?post_type=$post_key",
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    "/$post_key/$post_key/feed/",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    "/$post_key/$post_key/feed/",
                    false,
                ];
            }

            foreach ($select_block_list as $user) {
                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key&post_type=$post_key",
                    "/?name=$post_key&post_type=$post_key",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key&post_type=$post_key",
                    "/?name=$post_key&post_type=$post_key",
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    false,
                ];
            }
        }

        foreach ($no_user_post_type_keys as $post_key) {
            foreach ($all_user_list as $user) {
                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key&post_type=$post_key",
                    "/?name=$post_key&post_type=$post_key",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key&post_type=$post_key",
                    "/?name=$post_key&post_type=$post_key",
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    false,
                ];
            }
        }

        foreach ($no_user_post_status_keys as $post_key) {
            foreach ($all_user_list as $user) {
                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?page_id=%ID%',
                    '/?page_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?page_id=%ID%',
                    '/?page_id=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss&page_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss&page_id=%ID%',
                    false,
                ];
            }
        }

        foreach (['trash'] as $post_key) {
            foreach ($all_user_list as $user) {
                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?p=%ID%',
                    '/?p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/?attachment_id=%ID%',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/trash-post/trash-post-inherited-attachment/',
                    '/?attachment_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/trash-post/trash-post-inherited-attachment/',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/trash-post__trashed/trash-post-inherited-attachment/',
                    '/?attachment_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-attachment",
                    $user,
                    '/trash-post__trashed/trash-post-inherited-attachment/',
                    '/?attachment_id=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?post_type=page&p=%ID%',
                    '/?post_type=page&p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?page_id=%ID%',
                    '/?page_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?page_id=%ID%',
                    '/?page_id=%ID%',
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    "/?name=$post_key-post",
                    "/?name=$post_key-post",
                    false,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    true,
                ];

                $data[] = [
                    $post_key,
                    $user,
                    '/?feed=rss&p=%ID%',
                    '/?feed=rss&p=%ID%',
                    false,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss&page_id=%ID%',
                    true,
                ];

                $data[] = [
                    "$post_key-page",
                    $user,
                    '/?feed=rss&page_id=%ID%',
                    '/?feed=rss&page_id=%ID%',
                    false,
                ];
            }
        }

        return $data;
    }
}
