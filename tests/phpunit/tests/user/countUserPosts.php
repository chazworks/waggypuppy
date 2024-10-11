<?php

/**
 * @group user
 * @group post
 */
class Tests_User_CountUserPosts extends WP_UnitTestCase
{
    public static $user_id;
    public static $post_ids = [];

    public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
    {
        self::$user_id = $factory->user->create(
            [
                'role' => 'author',
                'user_login' => 'count_user_posts_user',
                'user_email' => 'count_user_posts_user@example.com',
            ],
        );

        self::$post_ids = $factory->post->create_many(
            4,
            [
                'post_author' => self::$user_id,
                'post_type' => 'post',
            ],
        );
        self::$post_ids = array_merge(
            self::$post_ids,
            $factory->post->create_many(
                3,
                [
                    'post_author' => self::$user_id,
                    'post_type' => 'wptests_pt',
                ],
            ),
        );
        self::$post_ids = array_merge(
            self::$post_ids,
            $factory->post->create_many(
                2,
                [
                    'post_author' => 12345,
                    'post_type' => 'wptests_pt',
                ],
            ),
        );

        self::$post_ids[] = $factory->post->create(
            [
                'post_author' => 12345,
                'post_type' => 'wptests_pt',
            ],
        );
    }

    public function set_up()
    {
        parent::set_up();
        register_post_type('wptests_pt');
    }

    public function test_count_user_posts_post_type_should_default_to_post()
    {
        $this->assertSame('4', count_user_posts(self::$user_id));
    }

    /**
     * @ticket 21364
     */
    public function test_count_user_posts_post_type_post()
    {
        $this->assertSame('4', count_user_posts(self::$user_id, 'post'));
    }

    /**
     * @ticket 21364
     */
    public function test_count_user_posts_post_type_cpt()
    {
        $this->assertSame('3', count_user_posts(self::$user_id, 'wptests_pt'));
    }

    /**
     * @ticket 32243
     */
    public function test_count_user_posts_with_multiple_post_types()
    {
        $this->assertSame('7', count_user_posts(self::$user_id, ['wptests_pt', 'post']));
    }

    /**
     * @ticket 32243
     */
    public function test_count_user_posts_should_ignore_non_existent_post_types()
    {
        $this->assertSame('4', count_user_posts(self::$user_id, ['foo', 'post']));
    }
}
