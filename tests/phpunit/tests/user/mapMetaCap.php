<?php

/**
 * @group user
 * @group capabilities
 * @covers ::map_meta_cap
 */
class Tests_User_MapMetaCap extends WP_UnitTestCase
{

    protected static $post_type    = 'mapmetacap';
    protected static $super_admins = null;
    protected static $user_id      = null;
    protected static $author_id    = null;
    protected static $post_id      = null;

    public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
    {
        self::$user_id   = $factory->user->create(['role' => 'administrator']);
        self::$author_id = $factory->user->create(['role' => 'administrator']);

        if (isset($GLOBALS['super_admins'])) {
            self::$super_admins = $GLOBALS['super_admins'];
        }
        $user                    = new WP_User(self::$user_id);
        $GLOBALS['super_admins'] = [$user->user_login];

        register_post_type(self::$post_type);

        self::$post_id = $factory->post->create(
            [
                'post_type'   => self::$post_type,
                'post_status' => 'private',
                'post_author' => self::$author_id,
            ]
        );
    }

    public static function wpTearDownAfterClass()
    {
        $GLOBALS['super_admins'] = self::$super_admins;
        unset($GLOBALS['wp_post_types'][self::$post_type]);
    }

    /**
     * @ticket 13905
     */
    public function test_capability_type_post_with_invalid_id()
    {
        $this->assertSame(
            ['do_not_allow'],
            map_meta_cap('edit_post', self::$user_id, self::$post_id + 1)
        );
    }

    public function test_capability_type_post_with_no_extra_caps()
    {

        register_post_type(
            self::$post_type,
            [
                'capability_type' => 'post',
            ]
        );
        $post_type_object = get_post_type_object(self::$post_type);

        $this->assertTrue($post_type_object->map_meta_cap);

        $this->assertSame(
            ['edit_others_posts', 'edit_private_posts'],
            map_meta_cap('edit_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['edit_others_posts', 'edit_private_posts'],
            map_meta_cap($post_type_object->cap->edit_post, self::$user_id, self::$post_id)
        );

        $this->assertSame(
            ['read_private_posts'],
            map_meta_cap('read_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['read_private_posts'],
            map_meta_cap($post_type_object->cap->read_post, self::$user_id, self::$post_id)
        );

        $this->assertSame(
            ['delete_others_posts', 'delete_private_posts'],
            map_meta_cap('delete_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['delete_others_posts', 'delete_private_posts'],
            map_meta_cap($post_type_object->cap->delete_post, self::$user_id, self::$post_id)
        );
    }

    public function test_custom_capability_type_with_map_meta_cap()
    {
        register_post_type(
            self::$post_type,
            [
                'capability_type' => 'book',
                'map_meta_cap'    => true,
            ]
        );

        $post_type_object = get_post_type_object(self::$post_type);

        $this->assertSame(
            ['edit_others_books', 'edit_private_books'],
            map_meta_cap('edit_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['edit_others_books', 'edit_private_books'],
            map_meta_cap($post_type_object->cap->edit_post, self::$user_id, self::$post_id)
        );

        $this->assertSame(
            ['read_private_books'],
            map_meta_cap('read_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['read_private_books'],
            map_meta_cap($post_type_object->cap->read_post, self::$user_id, self::$post_id)
        );

        $this->assertSame(
            ['delete_others_books', 'delete_private_books'],
            map_meta_cap('delete_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['delete_others_books', 'delete_private_books'],
            map_meta_cap($post_type_object->cap->delete_post, self::$user_id, self::$post_id)
        );
    }

    public function test_capability_type_post_with_one_renamed_cap()
    {
        register_post_type(
            self::$post_type,
            [
                'capability_type' => 'post',
                'capabilities'    => ['edit_posts' => 'edit_books'],
            ]
        );

        $post_type_object = get_post_type_object(self::$post_type);

        $this->assertFalse($post_type_object->map_meta_cap);

        $this->assertSame(
            ['edit_post'],
            map_meta_cap('edit_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['edit_post'],
            map_meta_cap($post_type_object->cap->edit_post, self::$user_id, self::$post_id)
        );

        $this->assertSame(
            ['read_post'],
            map_meta_cap('read_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['read_post'],
            map_meta_cap($post_type_object->cap->read_post, self::$user_id, self::$post_id)
        );

        $this->assertSame(
            ['delete_post'],
            map_meta_cap('delete_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['delete_post'],
            map_meta_cap($post_type_object->cap->delete_post, self::$user_id, self::$post_id)
        );
    }

    public function test_capability_type_post_map_meta_cap_true_with_renamed_cap()
    {
        register_post_type(
            self::$post_type,
            [
                'capability_type' => 'post',
                'map_meta_cap'    => true,
                'capabilities'    => [
                    'edit_post'         => 'edit_book', // maps back to itself.
                    'edit_others_posts' => 'edit_others_books',
                ],
            ]
        );

        $post_type_object = get_post_type_object(self::$post_type);

        $this->assertTrue($post_type_object->map_meta_cap);

        $this->assertSame(
            ['edit_others_books', 'edit_private_posts'],
            map_meta_cap('edit_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['edit_others_books', 'edit_private_posts'],
            map_meta_cap($post_type_object->cap->edit_post, self::$user_id, self::$post_id)
        );

        $this->assertSame(
            ['read_private_posts'],
            map_meta_cap('read_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['read_private_posts'],
            map_meta_cap($post_type_object->cap->read_post, self::$user_id, self::$post_id)
        );

        $this->assertSame(
            ['delete_others_posts', 'delete_private_posts'],
            map_meta_cap('delete_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['delete_others_posts', 'delete_private_posts'],
            map_meta_cap($post_type_object->cap->delete_post, self::$user_id, self::$post_id)
        );
    }

    public function test_capability_type_post_with_all_meta_caps_renamed()
    {
        register_post_type(
            self::$post_type,
            [
                'capability_type' => 'post',
                'capabilities'    => [
                    'edit_post'   => 'edit_book',
                    'read_post'   => 'read_book',
                    'delete_post' => 'delete_book',
                ],
            ]
        );

        $post_type_object = get_post_type_object(self::$post_type);

        $this->assertFalse($post_type_object->map_meta_cap);

        $this->assertSame(
            ['edit_book'],
            map_meta_cap('edit_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['edit_book'],
            map_meta_cap($post_type_object->cap->edit_post, self::$user_id, self::$post_id)
        );

        $this->assertSame(
            ['read_book'],
            map_meta_cap('read_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['read_book'],
            map_meta_cap($post_type_object->cap->read_post, self::$user_id, self::$post_id)
        );

        $this->assertSame(
            ['delete_book'],
            map_meta_cap('delete_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['delete_book'],
            map_meta_cap($post_type_object->cap->delete_post, self::$user_id, self::$post_id)
        );
    }

    public function test_capability_type_post_with_all_meta_caps_renamed_mapped()
    {
        register_post_type(
            self::$post_type,
            [
                'capability_type' => 'post',
                'map_meta_cap'    => true,
                'capabilities'    => [
                    'edit_post'   => 'edit_book',
                    'read_post'   => 'read_book',
                    'delete_post' => 'delete_book',
                ],
            ]
        );

        $post_type_object = get_post_type_object(self::$post_type);

        $this->assertTrue($post_type_object->map_meta_cap);

        $this->assertSame(
            ['edit_others_posts', 'edit_private_posts'],
            map_meta_cap('edit_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['edit_others_posts', 'edit_private_posts'],
            map_meta_cap($post_type_object->cap->edit_post, self::$user_id, self::$post_id)
        );

        $this->assertSame(
            ['read_private_posts'],
            map_meta_cap('read_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['read_private_posts'],
            map_meta_cap($post_type_object->cap->read_post, self::$user_id, self::$post_id)
        );

        $this->assertSame(
            ['delete_others_posts', 'delete_private_posts'],
            map_meta_cap('delete_post', self::$user_id, self::$post_id)
        );
        $this->assertSame(
            ['delete_others_posts', 'delete_private_posts'],
            map_meta_cap($post_type_object->cap->delete_post, self::$user_id, self::$post_id)
        );
    }

    /**
     * @ticket 30991
     */
    public function test_delete_posts_cap_without_map_meta_cap()
    {
        register_post_type(
            self::$post_type,
            [
                'capability_type' => 'post',
                'map_meta_cap'    => false,
            ]
        );

        $post_type_object = get_post_type_object(self::$post_type);

        $this->assertFalse($post_type_object->map_meta_cap);
        $this->assertSame('delete_posts', $post_type_object->cap->delete_posts);
    }

    public function test_unfiltered_html_cap()
    {
        if (defined('DISALLOW_UNFILTERED_HTML')) {
            $this->assertFalse(DISALLOW_UNFILTERED_HTML);
        }

        if (is_multisite()) {
            $this->assertSame(['do_not_allow'], map_meta_cap('unfiltered_html', 0));
            $this->assertSame(['unfiltered_html'], map_meta_cap('unfiltered_html', self::$user_id));
        } else {
            $this->assertSame(['unfiltered_html'], map_meta_cap('unfiltered_html', self::$user_id));
        }
    }

    /**
     * @ticket 20488
     */
    public function test_file_edit_caps_not_reliant_on_unfiltered_html_constant()
    {
        $this->assertFalse(defined('DISALLOW_FILE_MODS'));
        $this->assertFalse(defined('DISALLOW_FILE_EDIT'));

        if (! defined('DISALLOW_UNFILTERED_HTML')) {
            define('DISALLOW_UNFILTERED_HTML', true);
        }

        $this->assertTrue(DISALLOW_UNFILTERED_HTML);
        $this->assertSame(['update_core'], map_meta_cap('update_core', self::$user_id));
        $this->assertSame(['edit_plugins'], map_meta_cap('edit_plugins', self::$user_id));
    }

    /**
     * Test a post without an author.
     *
     * @ticket 27020
     */
    public function test_authorless_posts_capabilities()
    {
        $post_id = self::factory()->post->create(
            [
                'post_author' => 0,
                'post_type'   => 'post',
                'post_status' => 'publish',
            ]
        );
        $editor  = self::factory()->user->create(['role' => 'editor']);

        $this->assertSame(['edit_others_posts', 'edit_published_posts'], map_meta_cap('edit_post', $editor, $post_id));
        $this->assertSame(['delete_others_posts', 'delete_published_posts'], map_meta_cap('delete_post', $editor, $post_id));
    }

    /**
     * Test deleting front page.
     *
     * @ticket 37580
     */
    public function test_only_users_who_can_manage_options_can_delete_page_on_front()
    {
        $post_id = self::factory()->post->create(
            [
                'post_type'   => 'page',
                'post_status' => 'publish',
            ]
        );

        update_option('page_on_front', $post_id);
        $caps = map_meta_cap('delete_page', self::$user_id, $post_id);
        delete_option('page_on_front');

        $this->assertSame(['manage_options'], $caps);
    }

    /**
     * Test deleting posts page.
     *
     * @ticket 37580
     */
    public function test_only_users_who_can_manage_options_can_delete_page_for_posts()
    {
        $post_id = self::factory()->post->create(
            [
                'post_type'   => 'page',
                'post_status' => 'publish',
            ]
        );

        update_option('page_for_posts', $post_id);
        $caps = map_meta_cap('delete_page', self::$user_id, $post_id);
        delete_option('page_for_posts');

        $this->assertSame(['manage_options'], $caps);
    }

    /**
     * @dataProvider data_meta_caps_throw_doing_it_wrong_without_required_argument_provided
     * @ticket 44591
     *
     * @param string $cap The meta capability requiring an argument.
     */
    public function test_meta_caps_throw_doing_it_wrong_without_required_argument_provided($cap)
    {
        $admin_user = self::$user_id;
        $this->setExpectedIncorrectUsage('map_meta_cap');
        $this->assertContains('do_not_allow', map_meta_cap($cap, $admin_user));
    }

    /**
     * Data provider.
     *
     * @return array[] Test parameters {
     *     @type string $cap The meta capability requiring an argument.
     * }
     */
    public function data_meta_caps_throw_doing_it_wrong_without_required_argument_provided()
    {
        return [
            ['delete_post'],
            ['delete_page'],
            ['edit_post'],
            ['edit_page'],
            ['read_post'],
            ['read_page'],
            ['publish_post'],
            ['edit_post_meta'],
            ['delete_post_meta'],
            ['add_post_meta'],
            ['edit_comment_meta'],
            ['delete_comment_meta'],
            ['add_comment_meta'],
            ['edit_term_meta'],
            ['delete_term_meta'],
            ['add_term_meta'],
            ['edit_user_meta'],
            ['delete_user_meta'],
            ['add_user_meta'],
            ['edit_comment'],
            ['edit_term'],
            ['delete_term'],
            ['assign_term'],
        ];
    }
}
