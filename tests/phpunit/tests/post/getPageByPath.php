<?php

/**
 * @group post
 */
class Tests_Post_GetPageByPath extends WP_UnitTestCase
{
    /**
     * @ticket 15665
     */
    public function test_get_page_by_path_priority()
    {
        global $wpdb;

        $attachment = self::factory()->post->create_and_get(
            [
                'post_title' => 'some-page',
                'post_type' => 'attachment',
            ],
        );
        $page = self::factory()->post->create_and_get(
            [
                'post_title' => 'some-page',
                'post_type' => 'page',
            ],
        );
        $other_att = self::factory()->post->create_and_get(
            [
                'post_title' => 'some-other-page',
                'post_type' => 'attachment',
            ],
        );

        $wpdb->update($wpdb->posts, ['post_name' => 'some-page'], ['ID' => $page->ID]);
        clean_post_cache($page->ID);

        $page = get_post($page->ID);

        $this->assertSame('some-page', $attachment->post_name);
        $this->assertSame('some-page', $page->post_name);

        // get_page_by_path() should return a post of the requested type before returning an attachment.
        $this->assertEquals($page, get_page_by_path('some-page'));

        // Make sure get_page_by_path() will still select an attachment when a post of the requested type doesn't exist.
        $this->assertEquals($other_att, get_page_by_path('some-other-page'));
    }

    public function test_should_match_top_level_page()
    {
        $page = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'foo',
            ],
        );

        $found = get_page_by_path('foo');

        $this->assertSame($page, $found->ID);
    }

    public function test_should_obey_post_type()
    {
        register_post_type('wptests_pt');

        $page = self::factory()->post->create(
            [
                'post_type' => 'wptests_pt',
                'post_name' => 'foo',
            ],
        );

        $found = get_page_by_path('foo');
        $this->assertNull($found);

        $found = get_page_by_path('foo', OBJECT, 'wptests_pt');
        $this->assertSame($page, $found->ID);
    }

    public function test_should_match_nested_page()
    {
        $p1 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'foo',
            ],
        );

        $p2 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'bar',
                'post_parent' => $p1,
            ],
        );

        $p3 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'baz',
                'post_parent' => $p2,
            ],
        );

        $found = get_page_by_path('foo/bar/baz');

        $this->assertSame($p3, $found->ID);
    }

    /**
     * @ticket 56689
     *
     * @covers ::get_page_by_path
     */
    public function test_should_match_nested_page_query_count()
    {
        $p1 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'foo',
            ],
        );

        $p2 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'bar',
                'post_parent' => $p1,
            ],
        );

        $p3 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'baz',
                'post_parent' => $p2,
            ],
        );

        $queries_before = get_num_queries();
        $found = get_page_by_path('foo/bar/baz');
        $queries_after = get_num_queries();
        $cached_post = wp_cache_get($p1, 'posts');

        $this->assertSame(1, $queries_after - $queries_before, 'Only one query should run');
        $this->assertSame($p3, $found->ID, 'Check to see if the result is correct');
        $this->assertIsObject($cached_post, 'The cached post is not an object');
    }

    /**
     * @ticket 56689
     *
     * @covers ::get_page_by_path
     */
    public function test_should_match_nested_page_query_count_status()
    {
        $p1 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'foo',
                'post_status' => 'draft',
            ],
        );

        $p2 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'bar',
                'post_parent' => $p1,
            ],
        );

        $p3 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'baz',
                'post_parent' => $p2,
            ],
        );

        $queries_before = get_num_queries();
        $found = get_page_by_path('foo/bar/baz');
        $queries_after = get_num_queries();
        $cached_post = wp_cache_get($p1, 'posts');

        $this->assertSame(1, $queries_after - $queries_before, 'Only one query should run');
        $this->assertSame($p3, $found->ID, 'Check to see if the result is correct');
        $this->assertIsObject($cached_post, 'The cached post is not an object');
    }

    /**
     * @ticket 56689
     *
     * @covers ::get_page_by_path
     */
    public function test_should_return_null_for_invalid_path()
    {
        $queries_before = get_num_queries();
        $get_1 = get_page_by_path('should/return/null/for/an/invalid/path');
        $get_2 = get_page_by_path('should/return/null/for/an/invalid/path');
        $queries_after = get_num_queries();

        $this->assertNull($get_1, 'Invalid path should return null.');
        $this->assertSame(1, $queries_after - $queries_before, 'Only one query should run.');
        $this->assertSame($get_1, $get_2, 'The cached result should be the same as the uncached result.');
    }

    public function test_should_not_make_partial_match()
    {
        $p1 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'foo',
            ],
        );

        $p2 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'bar',
                'post_parent' => $p1,
            ],
        );

        $p3 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'baz',
                'post_parent' => $p2,
            ],
        );

        $found = get_page_by_path('bar/baz');

        $this->assertNull($found);
    }

    public function test_should_not_match_parts_out_of_order()
    {
        $p1 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'foo',
            ],
        );

        $p2 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'bar',
                'post_parent' => $p1,
            ],
        );

        $p3 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'baz',
                'post_parent' => $p2,
            ],
        );

        $found = get_page_by_path('bar/foo/baz');

        $this->assertNull($found);
    }

    /**
     * @ticket 36711
     */
    public function test_should_hit_cache()
    {
        $page = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'foo',
            ],
        );

        // Prime cache.
        $found = get_page_by_path('foo');
        $this->assertSame($page, $found->ID);

        $num_queries = get_num_queries();

        $found = get_page_by_path('foo');
        $this->assertSame($page, $found->ID);
        $this->assertSame($num_queries, get_num_queries());
    }

    /**
     * @ticket 36711
     */
    public function test_bad_path_should_be_cached()
    {
        // Prime cache.
        $found = get_page_by_path('foo');
        $this->assertNull($found);

        $num_queries = get_num_queries();

        $found = get_page_by_path('foo');
        $this->assertNull($found);
        $this->assertSame($num_queries, get_num_queries());
    }

    /**
     * @ticket 36711
     */
    public function test_bad_path_served_from_cache_should_not_fall_back_on_current_post()
    {
        global $post;

        // Fake the global.
        $post = self::factory()->post->create_and_get();

        // Prime cache.
        $found = get_page_by_path('foo');
        $this->assertNull($found);

        $num_queries = get_num_queries();

        $found = get_page_by_path('foo');
        $this->assertNull($found);
        $this->assertSame($num_queries, get_num_queries());

        unset($post);
    }

    /**
     * @ticket 36711
     */
    public function test_cache_should_not_match_post_in_different_post_type_with_same_path()
    {
        register_post_type('wptests_pt');

        $p1 = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'foo',
            ],
        );

        $p2 = self::factory()->post->create(
            [
                'post_type' => 'wptests_pt',
                'post_name' => 'foo',
            ],
        );

        // Prime cache for the page.
        $found = get_page_by_path('foo');
        $this->assertSame($p1, $found->ID);

        $num_queries = get_num_queries();

        $found = get_page_by_path('foo', OBJECT, 'wptests_pt');
        $this->assertSame($p2, $found->ID);
        ++$num_queries;
        $this->assertSame($num_queries, get_num_queries());
    }

    /**
     * @ticket 36711
     */
    public function test_cache_should_be_invalidated_when_post_name_is_edited()
    {
        $page = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'foo',
            ],
        );

        // Prime cache.
        $found = get_page_by_path('foo');
        $this->assertSame($page, $found->ID);

        wp_update_post(
            [
                'ID' => $page,
                'post_name' => 'bar',
            ],
        );

        $num_queries = get_num_queries();

        $found = get_page_by_path('bar');
        $this->assertSame($page, $found->ID);
        ++$num_queries;
        $this->assertSame($num_queries, get_num_queries());
    }

    /**
     * @ticket 37611
     */
    public function test_output_param_should_be_obeyed_for_cached_value()
    {
        $page = self::factory()->post->create(
            [
                'post_type' => 'page',
                'post_name' => 'foo',
            ],
        );

        // Prime cache.
        $found = get_page_by_path('foo');
        $this->assertSame($page, $found->ID);

        $object = get_page_by_path('foo', OBJECT);
        $this->assertIsObject($object);
        $this->assertSame($page, $object->ID);

        $array_n = get_page_by_path('foo', ARRAY_N);
        $this->assertIsArray($array_n);
        $this->assertSame($page, $array_n[0]);

        $array_a = get_page_by_path('foo', ARRAY_A);
        $this->assertIsArray($array_a);
        $this->assertSame($page, $array_a['ID']);
    }
}
