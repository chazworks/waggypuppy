<?php

/**
 * @group bookmark
 */
class Tests_Bookmark_GetBookmarks extends WP_UnitTestCase
{
    public function test_should_hit_cache()
    {
        $bookmarks = self::factory()->bookmark->create_many(2);

        $found1 = get_bookmarks(
            [
                'orderby' => 'link_id',
            ],
        );

        $num_queries = get_num_queries();

        $found2 = get_bookmarks(
            [
                'orderby' => 'link_id',
            ],
        );

        $this->assertSameSets($found1, $found2);
        $this->assertSame($num_queries, get_num_queries());
    }

    public function test_adding_bookmark_should_bust_get_bookmarks_cache()
    {
        $bookmarks = self::factory()->bookmark->create_many(2);

        // Prime cache.
        $found1 = get_bookmarks(
            [
                'orderby' => 'link_id',
            ],
        );

        $num_queries = get_num_queries();

        $bookmarks[] = wp_insert_link(
            [
                'link_name' => 'foo',
                'link_url' => 'http://example.com',
            ],
        );

        $found2 = get_bookmarks(
            [
                'orderby' => 'link_id',
            ],
        );

        $this->assertEqualSets($bookmarks, wp_list_pluck($found2, 'link_id'));
        $this->assertGreaterThan($num_queries, get_num_queries());
    }

    /**
     * @ticket 18356
     */
    public function test_orderby_rand_should_not_be_cached()
    {
        $bookmarks = self::factory()->bookmark->create_many(2);

        $found1 = get_bookmarks(
            [
                'orderby' => 'rand',
            ],
        );

        $num_queries = get_num_queries();

        $found2 = get_bookmarks(
            [
                'orderby' => 'rand',
            ],
        );

        // Equal sets != same order.
        $this->assertEqualSets($found1, $found2);
        $this->assertGreaterThan($num_queries, get_num_queries());
    }

    public function test_exclude_param_gets_properly_parsed_as_list()
    {
        $bookmarks = self::factory()->bookmark->create_many(3);

        $found = get_bookmarks(
            [
                'exclude' => ',,',
            ],
        );

        $found_ids = [];
        foreach ($found as $bookmark) {
            $found_ids[] = $bookmark->link_id;
        }

        // Equal sets != same order.
        $this->assertEqualSets($bookmarks, $found_ids);
    }

    public function test_include_param_gets_properly_parsed_as_list()
    {
        $bookmarks = self::factory()->bookmark->create_many(3);

        $found = get_bookmarks(
            [
                'include' => ',,',
            ],
        );

        $found_ids = [];
        foreach ($found as $bookmark) {
            $found_ids[] = $bookmark->link_id;
        }

        // Equal sets != same order.
        $this->assertEqualSets($bookmarks, $found_ids);
    }

    public function test_category_param_properly_gets_parsed_as_list()
    {
        $bookmarks = self::factory()->bookmark->create_many(3);
        $categories = self::factory()->term->create_many(
            3,
            [
                'taxonomy' => 'link_category',
            ],
        );

        $add = wp_add_object_terms($bookmarks[0], $categories[0], 'link_category');
        $add = wp_add_object_terms($bookmarks[1], $categories[1], 'link_category');
        $add = wp_add_object_terms($bookmarks[2], $categories[2], 'link_category');

        $found = get_bookmarks(
            [
                'category' => ',,',
            ],
        );

        $found_ids = [];
        foreach ($found as $bookmark) {
            $found_ids[] = $bookmark->link_id;
        }

        // Equal sets != same order.
        $this->assertEqualSets($bookmarks, $found_ids);
    }
}
