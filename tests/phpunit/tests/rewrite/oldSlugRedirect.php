<?php

/**
 * @group rewrite
 * @ticket 33920
 * @covers wp_old_slug_redirect
 */
class Tests_Rewrite_OldSlugRedirect extends WP_UnitTestCase
{
    protected $old_slug_redirect_url;

    protected static $post_id;

    public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
    {
        self::$post_id = $factory->post->create(
            [
                'post_title' => 'Foo Bar',
                'post_name' => 'foo-bar',
            ],
        );
    }

    public function set_up()
    {
        parent::set_up();

        add_filter('old_slug_redirect_url', [$this, 'filter_old_slug_redirect_url'], 10, 1);

        $this->set_permalink_structure('/%postname%/');

        add_rewrite_endpoint('custom-endpoint', EP_PERMALINK);
        add_rewrite_endpoint('second-endpoint', EP_PERMALINK, 'custom');

        flush_rewrite_rules();
    }

    public function tear_down()
    {
        $this->old_slug_redirect_url = null;

        parent::tear_down();
    }

    public function test_old_slug_redirect()
    {
        $old_permalink = user_trailingslashit(get_permalink(self::$post_id));

        wp_update_post(
            [
                'ID' => self::$post_id,
                'post_name' => 'bar-baz',
            ],
        );

        $permalink = user_trailingslashit(get_permalink(self::$post_id));

        $this->go_to($old_permalink);
        wp_old_slug_redirect();
        $this->assertSame($permalink, $this->old_slug_redirect_url);
    }

    /**
     * @ticket 36723
     */
    public function test_old_slug_redirect_cache()
    {
        $old_permalink = user_trailingslashit(get_permalink(self::$post_id));

        wp_update_post(
            [
                'ID' => self::$post_id,
                'post_name' => 'bar-baz',
            ],
        );

        $permalink = user_trailingslashit(get_permalink(self::$post_id));

        $this->go_to($old_permalink);

        wp_old_slug_redirect();
        $num_queries = get_num_queries();
        $this->assertSame($permalink, $this->old_slug_redirect_url);

        wp_old_slug_redirect();
        $this->assertSame($permalink, $this->old_slug_redirect_url);
        $this->assertSame($num_queries, get_num_queries());
    }

    /**
     * @ticket 36723
     */
    public function test_old_slug_redirect_cache_invalidation()
    {
        $old_permalink = user_trailingslashit(get_permalink(self::$post_id));

        wp_update_post(
            [
                'ID' => self::$post_id,
                'post_name' => 'bar-baz',
            ],
        );

        $permalink = user_trailingslashit(get_permalink(self::$post_id));

        $this->go_to($old_permalink);

        wp_old_slug_redirect();
        $this->assertSame($permalink, $this->old_slug_redirect_url);

        wp_update_post(
            [
                'ID' => self::$post_id,
                'post_name' => 'foo-bar-baz',
            ],
        );

        $permalink = user_trailingslashit(get_permalink(self::$post_id));

        $num_queries = get_num_queries();
        wp_old_slug_redirect();
        $this->assertSame($permalink, $this->old_slug_redirect_url);
        $this->assertSame($num_queries + 1, get_num_queries());
    }

    public function test_old_slug_redirect_attachment()
    {
        $file = DIR_TESTDATA . '/images/canola.jpg';
        $attachment_id = self::factory()->attachment->create_object(
            $file,
            self::$post_id,
            [
                'post_mime_type' => 'image/jpeg',
                'post_name' => 'my-attachment',
            ],
        );

        $old_permalink = get_attachment_link($attachment_id);

        wp_update_post(
            [
                'ID' => self::$post_id,
                'post_name' => 'bar-baz',
            ],
        );

        $this->go_to($old_permalink);
        wp_old_slug_redirect();
        $this->assertNull($this->old_slug_redirect_url);
        $this->assertQueryTrue('is_attachment', 'is_singular', 'is_single');

        $old_permalink = get_attachment_link($attachment_id);

        wp_update_post(
            [
                'ID' => $attachment_id,
                'post_name' => 'the-attachment',
            ],
        );

        $permalink = user_trailingslashit(trailingslashit(get_permalink(self::$post_id)) . 'the-attachment');

        $this->go_to($old_permalink);
        wp_old_slug_redirect();
        $this->assertSame($permalink, $this->old_slug_redirect_url);
    }

    public function test_old_slug_redirect_paged()
    {
        wp_update_post(
            [
                'ID' => self::$post_id,
                'post_content' => 'Test<!--nextpage-->Test',
            ],
        );

        $old_permalink = user_trailingslashit(trailingslashit(get_permalink(self::$post_id)) . 'page/2');

        wp_update_post(
            [
                'ID' => self::$post_id,
                'post_name' => 'bar-baz',
            ],
        );

        $permalink = user_trailingslashit(trailingslashit(get_permalink(self::$post_id)) . 'page/2');

        $this->go_to($old_permalink);
        wp_old_slug_redirect();
        $this->assertSame($permalink, $this->old_slug_redirect_url);
    }

    /**
     * @ticket 35031
     */
    public function test_old_slug_doesnt_redirect_when_reused()
    {
        $old_permalink = user_trailingslashit(get_permalink(self::$post_id));

        wp_update_post(
            [
                'ID' => self::$post_id,
                'post_name' => 'bar-baz',
            ],
        );

        $new_post_id = self::factory()->post->create(
            [
                'post_title' => 'Foo Bar',
                'post_name' => 'foo-bar',
            ],
        );

        $permalink = user_trailingslashit(get_permalink($new_post_id));

        $this->assertSame($old_permalink, $permalink);

        $this->go_to($old_permalink);
        wp_old_slug_redirect();
        $this->assertNull($this->old_slug_redirect_url);
    }

    public function filter_old_slug_redirect_url($url)
    {
        $this->old_slug_redirect_url = $url;
        return false;
    }
}
