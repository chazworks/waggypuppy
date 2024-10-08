<?php
/**
 * @group link
 * @covers ::get_adjacent_post
 */
class Tests_Link_GetAdjacentPost extends WP_UnitTestCase
{
    protected $exclude_term;

    /**
     * @ticket 17807
     */
    public function test_get_adjacent_post()
    {
        // Need some sample posts to test adjacency.
        $post_one = self::factory()->post->create_and_get(
            [
                'post_title' => 'First',
                'post_date'  => '2012-01-01 12:00:00',
            ]
        );

        $post_two = self::factory()->post->create_and_get(
            [
                'post_title' => 'Second',
                'post_date'  => '2012-02-01 12:00:00',
            ]
        );

        $post_three = self::factory()->post->create_and_get(
            [
                'post_title' => 'Third',
                'post_date'  => '2012-03-01 12:00:00',
            ]
        );

        $post_four = self::factory()->post->create_and_get(
            [
                'post_title' => 'Fourth',
                'post_date'  => '2012-04-01 12:00:00',
            ]
        );

        // Assign some terms.
        wp_set_object_terms($post_one->ID, 'WordPress', 'category', false);
        wp_set_object_terms($post_three->ID, 'WordPress', 'category', false);

        wp_set_object_terms($post_two->ID, 'plugins', 'post_tag', false);
        wp_set_object_terms($post_four->ID, 'plugins', 'post_tag', false);

        // Test normal post adjacency.
        $this->go_to(get_permalink($post_two->ID));

        $this->assertEquals($post_one, get_adjacent_post(false, '', true));
        $this->assertEquals($post_three, get_adjacent_post(false, '', false));

        $this->assertNotEquals($post_two, get_adjacent_post(false, '', true));
        $this->assertNotEquals($post_two, get_adjacent_post(false, '', false));

        // Test category adjacency.
        $this->go_to(get_permalink($post_one->ID));

        $this->assertSame('', get_adjacent_post(true, '', true, 'category'));
        $this->assertEquals($post_three, get_adjacent_post(true, '', false, 'category'));

        // Test tag adjacency.
        $this->go_to(get_permalink($post_two->ID));

        $this->assertSame('', get_adjacent_post(true, '', true, 'post_tag'));
        $this->assertEquals($post_four, get_adjacent_post(true, '', false, 'post_tag'));

        // Test normal boundary post.
        $this->go_to(get_permalink($post_two->ID));

        $this->assertEquals([$post_one], get_boundary_post(false, '', true));
        $this->assertEquals([$post_four], get_boundary_post(false, '', false));

        // Test category boundary post.
        $this->go_to(get_permalink($post_one->ID));

        $this->assertEquals([$post_one], get_boundary_post(true, '', true, 'category'));
        $this->assertEquals([$post_three], get_boundary_post(true, '', false, 'category'));

        // Test tag boundary post.
        $this->go_to(get_permalink($post_two->ID));

        $this->assertEquals([$post_two], get_boundary_post(true, '', true, 'post_tag'));
        $this->assertEquals([$post_four], get_boundary_post(true, '', false, 'post_tag'));
    }

    /**
     * @ticket 22112
     */
    public function test_get_adjacent_post_exclude_self_term()
    {
        // Bump term_taxonomy to mimic shared term offsets.
        global $wpdb;
        $wpdb->insert(
            $wpdb->term_taxonomy,
            [
                'taxonomy'    => 'foo',
                'term_id'     => 12345,
                'description' => '',
            ]
        );

        $include = self::factory()->term->create(
            [
                'taxonomy' => 'category',
                'name'     => 'Include',
            ]
        );
        $exclude = self::factory()->category->create();

        $one = self::factory()->post->create_and_get(
            [
                'post_date'     => '2012-01-01 12:00:00',
                'post_category' => [$include, $exclude],
            ]
        );

        $two = self::factory()->post->create_and_get(
            [
                'post_date'     => '2012-01-02 12:00:00',
                'post_category' => [],
            ]
        );

        $three = self::factory()->post->create_and_get(
            [
                'post_date'     => '2012-01-03 12:00:00',
                'post_category' => [$include, $exclude],
            ]
        );

        $four = self::factory()->post->create_and_get(
            [
                'post_date'     => '2012-01-04 12:00:00',
                'post_category' => [$include],
            ]
        );

        $five = self::factory()->post->create_and_get(
            [
                'post_date'     => '2012-01-05 12:00:00',
                'post_category' => [$include, $exclude],
            ]
        );

        // First post.
        $this->go_to(get_permalink($one));
        $this->assertEquals($two, get_adjacent_post(false, [], false));
        $this->assertEquals($three, get_adjacent_post(true, [], false));
        $this->assertEquals($two, get_adjacent_post(false, [$exclude], false));
        $this->assertEquals($four, get_adjacent_post(true, [$exclude], false));
        $this->assertEmpty(get_adjacent_post(false, [], true));

        // Fourth post.
        $this->go_to(get_permalink($four));
        $this->assertEquals($five, get_adjacent_post(false, [], false));
        $this->assertEquals($five, get_adjacent_post(true, [], false));
        $this->assertEmpty(get_adjacent_post(false, [$exclude], false));
        $this->assertEmpty(get_adjacent_post(true, [$exclude], false));

        $this->assertEquals($three, get_adjacent_post(false, [], true));
        $this->assertEquals($three, get_adjacent_post(true, [], true));
        $this->assertEquals($two, get_adjacent_post(false, [$exclude], true));
        $this->assertEmpty(get_adjacent_post(true, [$exclude], true));

        // Last post.
        $this->go_to(get_permalink($five));
        $this->assertEquals($four, get_adjacent_post(false, [], true));
        $this->assertEquals($four, get_adjacent_post(true, [], true));
        $this->assertEquals($four, get_adjacent_post(false, [$exclude], true));
        $this->assertEquals($four, get_adjacent_post(true, [$exclude], true));
        $this->assertEmpty(get_adjacent_post(false, [], false));
    }

    /**
     * @ticket 32833
     */
    public function test_get_adjacent_post_excluded_terms()
    {
        register_taxonomy('wptests_tax', 'post');

        $t = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ]
        );

        $p1 = self::factory()->post->create(['post_date' => '2015-08-27 12:00:00']);
        $p2 = self::factory()->post->create(['post_date' => '2015-08-26 12:00:00']);
        $p3 = self::factory()->post->create(['post_date' => '2015-08-25 12:00:00']);

        wp_set_post_terms($p2, [$t], 'wptests_tax');

        // Fake current page.
        $_post           = isset($GLOBALS['post']) ? $GLOBALS['post'] : null;
        $GLOBALS['post'] = get_post($p1);

        $found = get_adjacent_post(false, [$t], true, 'wptests_tax');

        if (! is_null($_post)) {
            $GLOBALS['post'] = $_post;
        } else {
            unset($GLOBALS['post']);
        }

        // Should skip $p2, which belongs to $t.
        $this->assertSame($p3, $found->ID);
    }

    /**
     * @ticket 32833
     */
    public function test_get_adjacent_post_excluded_terms_should_not_require_posts_to_have_terms_in_any_taxonomy()
    {
        register_taxonomy('wptests_tax', 'post');

        $t = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ]
        );

        $p1 = self::factory()->post->create(['post_date' => '2015-08-27 12:00:00']);
        $p2 = self::factory()->post->create(['post_date' => '2015-08-26 12:00:00']);
        $p3 = self::factory()->post->create(['post_date' => '2015-08-25 12:00:00']);

        wp_set_post_terms($p2, [$t], 'wptests_tax');

        // Make sure that $p3 doesn't have the 'Uncategorized' category.
        wp_delete_object_term_relationships($p3, 'category');

        // Fake current page.
        $_post           = isset($GLOBALS['post']) ? $GLOBALS['post'] : null;
        $GLOBALS['post'] = get_post($p1);

        $found = get_adjacent_post(false, [$t], true, 'wptests_tax');

        if (! is_null($_post)) {
            $GLOBALS['post'] = $_post;
        } else {
            unset($GLOBALS['post']);
        }

        // Should skip $p2, which belongs to $t.
        $this->assertSame($p3, $found->ID);
    }

    /**
     * @ticket 35211
     */
    public function test_get_adjacent_post_excluded_terms_filter()
    {
        register_taxonomy('wptests_tax', 'post');

        $terms = self::factory()->term->create_many(
            2,
            [
                'taxonomy' => 'wptests_tax',
            ]
        );

        $p1 = self::factory()->post->create(['post_date' => '2015-08-27 12:00:00']);
        $p2 = self::factory()->post->create(['post_date' => '2015-08-26 12:00:00']);
        $p3 = self::factory()->post->create(['post_date' => '2015-08-25 12:00:00']);

        wp_set_post_terms($p1, [$terms[0], $terms[1]], 'wptests_tax');
        wp_set_post_terms($p2, [$terms[1]], 'wptests_tax');
        wp_set_post_terms($p3, [$terms[0]], 'wptests_tax');

        $this->go_to(get_permalink($p1));

        $this->exclude_term = $terms[1];
        add_filter('get_previous_post_excluded_terms', [$this, 'filter_excluded_terms']);

        $found = get_adjacent_post(true, [], true, 'wptests_tax');

        remove_filter('get_previous_post_excluded_terms', [$this, 'filter_excluded_terms']);
        unset($this->exclude_term);

        $this->assertSame($p3, $found->ID);
    }

    /**
     * @ticket 43521
     */
    public function test_get_adjacent_post_excluded_terms_filter_should_apply_to_empty_excluded_terms_parameter()
    {
        register_taxonomy('wptests_tax', 'post');

        $terms = self::factory()->term->create_many(
            2,
            [
                'taxonomy' => 'wptests_tax',
            ]
        );

        $p1 = self::factory()->post->create(['post_date' => '2015-08-27 12:00:00']);
        $p2 = self::factory()->post->create(['post_date' => '2015-08-26 12:00:00']);
        $p3 = self::factory()->post->create(['post_date' => '2015-08-25 12:00:00']);

        wp_set_post_terms($p1, [$terms[0], $terms[1]], 'wptests_tax');
        wp_set_post_terms($p2, [$terms[1]], 'wptests_tax');
        wp_set_post_terms($p3, [$terms[0]], 'wptests_tax');

        $this->go_to(get_permalink($p1));

        $this->exclude_term = $terms[1];
        add_filter('get_previous_post_excluded_terms', [$this, 'filter_excluded_terms']);

        $found = get_adjacent_post(false, [], true, 'wptests_tax');

        remove_filter('get_previous_post_excluded_terms', [$this, 'filter_excluded_terms']);
        unset($this->exclude_term);

        $this->assertSame($p3, $found->ID);
    }

    /**
     * @ticket 43521
     */
    public function test_excluded_terms_filter_empty()
    {
        register_taxonomy('wptests_tax', 'post');

        $terms = self::factory()->term->create_many(
            2,
            [
                'taxonomy' => 'wptests_tax',
            ]
        );

        $p1 = self::factory()->post->create(['post_date' => '2015-08-27 12:00:00']);
        $p2 = self::factory()->post->create(['post_date' => '2015-08-26 12:00:00']);
        $p3 = self::factory()->post->create(['post_date' => '2015-08-25 12:00:00']);

        wp_set_post_terms($p1, [$terms[0], $terms[1]], 'wptests_tax');
        wp_set_post_terms($p2, [$terms[1]], 'wptests_tax');
        wp_set_post_terms($p3, [$terms[0]], 'wptests_tax');

        $this->go_to(get_permalink($p1));

        $this->exclude_term = $terms[1];
        add_filter('get_previous_post_excluded_terms', [$this, 'filter_excluded_terms']);

        $found = get_adjacent_post(false, [], true, 'wptests_tax');

        remove_filter('get_previous_post_excluded_terms', [$this, 'filter_excluded_terms']);
        unset($this->exclude_term);

        $this->assertSame($p3, $found->ID);
    }

    public function filter_excluded_terms($excluded_terms)
    {
        $excluded_terms[] = $this->exclude_term;
        return $excluded_terms;
    }

    /**
     * @ticket 41131
     */
    public function test_get_adjacent_post_cache()
    {
        // Need some sample posts to test adjacency.
        $post_one = self::factory()->post->create_and_get(
            [
                'post_title' => 'First',
                'post_date'  => '2012-01-01 12:00:00',
            ]
        );

        $post_two = self::factory()->post->create_and_get(
            [
                'post_title' => 'Second',
                'post_date'  => '2012-02-01 12:00:00',
            ]
        );

        $post_three = self::factory()->post->create_and_get(
            [
                'post_title' => 'Third',
                'post_date'  => '2012-03-01 12:00:00',
            ]
        );

        $post_four = self::factory()->post->create_and_get(
            [
                'post_title' => 'Fourth',
                'post_date'  => '2012-04-01 12:00:00',
            ]
        );

        // Assign some terms.
        wp_set_object_terms($post_one->ID, 'WordPress', 'category', false);
        wp_set_object_terms($post_three->ID, 'WordPress', 'category', false);

        wp_set_object_terms($post_two->ID, 'plugins', 'post_tag', false);
        wp_set_object_terms($post_four->ID, 'plugins', 'post_tag', false);

        // Test normal post adjacency.
        $this->go_to(get_permalink($post_two->ID));

        // Test getting the right result.
        $first_run = get_adjacent_post(false, '', true);
        $this->assertEquals($post_one, $first_run, 'Did not get first post when on second post');
        $this->assertNotEquals($post_two, $first_run, 'Got second post when on second post');

        // Query count to test caching.
        $num_queries = get_num_queries();
        $second_run  = get_adjacent_post(false, '', true);
        $this->assertNotEquals($post_two, $second_run, 'Got second post when on second post on second run');
        $this->assertEquals($post_one, $second_run, 'Did not get first post when on second post on second run');
        $this->assertSame($num_queries, get_num_queries());

        // Test creating new post busts cache.
        $post_five   = self::factory()->post->create_and_get(
            [
                'post_title' => 'Five',
                'post_date'  => '2012-04-01 12:00:00',
            ]
        );
        $num_queries = get_num_queries();

        $this->assertEquals($post_one, get_adjacent_post(false, '', true), 'Did not get first post after new post is added');
        $this->assertSame(get_num_queries() - $num_queries, 1, 'Number of queries run was not one after new post is added');

        $this->assertEquals($post_four, get_adjacent_post(true, '', false), 'Did not get forth post after new post is added');
        $num_queries = get_num_queries();
        $this->assertEquals($post_four, get_adjacent_post(true, '', false), 'Did not get forth post after new post is added');
        $this->assertSame($num_queries, get_num_queries());
        wp_set_object_terms($post_four->ID, 'themes', 'post_tag', false);

        $num_queries = get_num_queries();
        $this->assertEquals($post_four, get_adjacent_post(true, '', false), 'Result of function call is wrong after after adding new term');
        $this->assertSame(get_num_queries() - $num_queries, 2, 'Number of queries run was not two after adding new term');
    }
}
