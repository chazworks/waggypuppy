<?php

/**
 * @group general
 * @group template
 * @covers ::wp_get_archives
 */
class Tests_General_wpGetArchives extends WP_UnitTestCase
{
    public function set_up()
    {
        parent::set_up();

        wp_cache_delete('last_changed', 'posts');
    }

    /**
     * @ticket 23206
     */
    public function test_get_archives_cache()
    {
        self::factory()->post->create_many(3, ['post_type' => 'post']);
        wp_cache_delete('last_changed', 'posts');
        $this->assertFalse(wp_cache_get('last_changed', 'posts'));

        $num_queries = get_num_queries();

        // Cache is not primed, expect 1 query.
        $result = wp_get_archives(
            [
                'type' => 'monthly',
                'echo' => false,
            ],
        );
        $this->assertIsString($result);
        $time1 = wp_cache_get('last_changed', 'posts');
        $this->assertNotEmpty($time1);
        $this->assertSame($num_queries + 1, get_num_queries());

        $num_queries = get_num_queries();

        // Cache is primed, expect no queries.
        $result = wp_get_archives(
            [
                'type' => 'monthly',
                'echo' => false,
            ],
        );
        $this->assertIsString($result);
        $this->assertSame($time1, wp_cache_get('last_changed', 'posts'));
        $this->assertSame($num_queries, get_num_queries());

        // Change args, resulting in a different query string. Cache is not primed, expect 1 query.
        $result = wp_get_archives(
            [
                'type' => 'monthly',
                'echo' => false,
                'order' => 'ASC',
            ],
        );
        $this->assertIsString($result);
        $this->assertSame($time1, wp_cache_get('last_changed', 'posts'));
        $this->assertSame($num_queries + 1, get_num_queries());

        $num_queries = get_num_queries();

        // Cache is primed, expect no queries.
        $result = wp_get_archives(
            [
                'type' => 'monthly',
                'echo' => false,
                'order' => 'ASC',
            ],
        );
        $this->assertIsString($result);
        $this->assertSame($time1, wp_cache_get('last_changed', 'posts'));
        $this->assertSame($num_queries, get_num_queries());

        $num_queries = get_num_queries();

        // Change type. Cache is not primed, expect 1 query.
        $result = wp_get_archives(
            [
                'type' => 'yearly',
                'echo' => false,
            ],
        );
        $this->assertIsString($result);
        $this->assertSame($time1, wp_cache_get('last_changed', 'posts'));
        $this->assertSame($num_queries + 1, get_num_queries());

        $num_queries = get_num_queries();

        // Cache is primed, expect no queries.
        $result = wp_get_archives(
            [
                'type' => 'yearly',
                'echo' => false,
            ],
        );
        $this->assertIsString($result);
        $this->assertSame($time1, wp_cache_get('last_changed', 'posts'));
        $this->assertSame($num_queries, get_num_queries());

        // Change type. Cache is not primed, expect 1 query.
        $result = wp_get_archives(
            [
                'type' => 'daily',
                'echo' => false,
            ],
        );
        $this->assertIsString($result);
        $this->assertSame($time1, wp_cache_get('last_changed', 'posts'));
        $this->assertSame($num_queries + 1, get_num_queries());

        $num_queries = get_num_queries();

        // Cache is primed, expect no queries.
        $result = wp_get_archives(
            [
                'type' => 'daily',
                'echo' => false,
            ],
        );
        $this->assertIsString($result);
        $this->assertSame($time1, wp_cache_get('last_changed', 'posts'));
        $this->assertSame($num_queries, get_num_queries());

        // Change type. Cache is not primed, expect 1 query.
        $result = wp_get_archives(
            [
                'type' => 'weekly',
                'echo' => false,
            ],
        );
        $this->assertIsString($result);
        $this->assertSame($time1, wp_cache_get('last_changed', 'posts'));
        $this->assertSame($num_queries + 1, get_num_queries());

        $num_queries = get_num_queries();

        // Cache is primed, expect no queries.
        $result = wp_get_archives(
            [
                'type' => 'weekly',
                'echo' => false,
            ],
        );
        $this->assertIsString($result);
        $this->assertSame($time1, wp_cache_get('last_changed', 'posts'));
        $this->assertSame($num_queries, get_num_queries());

        // Change type. Cache is not primed, expect 1 query.
        $result = wp_get_archives(
            [
                'type' => 'postbypost',
                'echo' => false,
            ],
        );
        $this->assertIsString($result);
        $this->assertSame($time1, wp_cache_get('last_changed', 'posts'));
        $this->assertSame($num_queries + 1, get_num_queries());

        $num_queries = get_num_queries();

        // Cache is primed, expect no queries.
        $result = wp_get_archives(
            [
                'type' => 'postbypost',
                'echo' => false,
            ],
        );
        $this->assertIsString($result);
        $this->assertSame($time1, wp_cache_get('last_changed', 'posts'));
        $this->assertSame($num_queries, get_num_queries());
    }
}
