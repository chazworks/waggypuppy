<?php

/**
 * @group link
 * @group canonical
 * @covers ::wp_get_canonical_url
 */
class Tests_Link_wpGetCanonicalUrl extends WP_UnitTestCase
{
    public static $post_id;

    public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
    {
        self::$post_id = $factory->post->create(
            [
                'post_content' => 'Page 1 <!--nextpage--> Page 2 <!--nextpage--> Page 3',
                'post_status' => 'publish',
            ],
        );
    }

    /**
     * Test for a non existing post.
     */
    public function test_non_existing_post()
    {
        $this->assertFalse(wp_get_canonical_url(-1));
    }

    /**
     * Test for a post that is not published.
     */
    public function test_post_status()
    {
        $post_id = self::factory()->post->create(
            [
                'post_status' => 'draft',
            ],
        );

        $this->assertFalse(wp_get_canonical_url($post_id));
    }

    /**
     * Test for a page that is not the queried object.
     */
    public function test_non_current_page()
    {
        $this->assertSame(get_permalink(self::$post_id), wp_get_canonical_url(self::$post_id));
    }

    /**
     * Test non permalink structure page usage.
     */
    public function test_paged_with_plain_permalink_structure()
    {
        $link = add_query_arg(
            [
                'page' => 2,
                'foo' => 'bar',
            ],
            get_permalink(self::$post_id),
        );

        $this->go_to($link);

        $expected = add_query_arg(
            [
                'page' => 2,
            ],
            get_permalink(self::$post_id),
        );

        $this->assertSame($expected, wp_get_canonical_url(self::$post_id));
    }

    /**
     * Test permalink structure page usage.
     */
    public function test_paged_with_custom_permalink_structure()
    {
        $this->set_permalink_structure('/%postname%/');
        $page = 2;

        $link = add_query_arg(
            [
                'page' => $page,
                'foo' => 'bar',
            ],
            get_permalink(self::$post_id),
        );

        $this->go_to($link);

        $expected = trailingslashit(get_permalink(self::$post_id)) . user_trailingslashit($page, 'single_paged');

        $this->assertSame($expected, wp_get_canonical_url(self::$post_id));
    }

    /**
     *  Test non permalink structure comment page usage.
     */
    public function test_comments_paged_with_plain_permalink_structure()
    {
        $cpage = 2;

        $link = add_query_arg(
            [
                'cpage' => $cpage,
                'foo' => 'bar',
            ],
            get_permalink(self::$post_id),
        );

        $this->go_to($link);

        $expected = add_query_arg(
            [
                'cpage' => $cpage,
            ],
            get_permalink(self::$post_id) . '#comments',
        );

        $this->assertSame($expected, wp_get_canonical_url(self::$post_id));
    }

    /**
     * Test permalink structure comment page usage.
     */
    public function test_comments_paged_with_pretty_permalink_structure()
    {
        global $wp_rewrite;

        $this->set_permalink_structure('/%postname%/');
        $cpage = 2;

        $link = add_query_arg(
            [
                'cpage' => $cpage,
                'foo' => 'bar',
            ],
            get_permalink(self::$post_id),
        );

        $this->go_to($link);

        $expected = user_trailingslashit(trailingslashit(get_permalink(self::$post_id))
                . $wp_rewrite->comments_pagination_base
                . '-'
                . $cpage, 'commentpaged') . '#comments';

        $this->assertSame($expected, wp_get_canonical_url(self::$post_id));
    }

    /**
     * Test calling of filter.
     */
    public function test_get_canonical_url_filter()
    {
        add_filter('get_canonical_url', [$this, 'canonical_url_filter']);
        $canonical_url = wp_get_canonical_url(self::$post_id);
        remove_filter('get_canonical_url', [$this, 'canonical_url_filter']);

        $this->assertSame($this->canonical_url_filter(), $canonical_url);
    }

    /**
     * Filter callback for testing of filter usage.
     *
     * @return string
     */
    public function canonical_url_filter()
    {
        return 'http://canonical.example.org/';
    }
}
