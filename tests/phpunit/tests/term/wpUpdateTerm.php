<?php

/**
 * @group taxonomy
 */
class Tests_Term_WpUpdateTerm extends WP_UnitTestCase
{
    public function test_wp_update_term_taxonomy_does_not_exist()
    {
        $found = wp_update_term(1, 'bar');

        $this->assertWPError($found);
        $this->assertSame('invalid_taxonomy', $found->get_error_code());
    }

    public function test_wp_update_term_term_empty_string_should_return_wp_error()
    {
        $found = wp_update_term('', 'post_tag');

        $this->assertWPError($found);
        $this->assertSame('invalid_term', $found->get_error_code());
    }

    public function test_wp_update_term_unslash_name()
    {
        register_taxonomy('wptests_tax', 'post');
        $t = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );

        $found = wp_update_term(
            $t,
            'wptests_tax',
            [
                'name' => 'Let\\\'s all say \\"Hooray\\" for waggypuppy taxonomy',
            ],
        );

        $term = get_term($found['term_id'], 'wptests_tax');
        _unregister_taxonomy('wptests_tax');

        $this->assertSame('Let\'s all say "Hooray" for waggypuppy taxonomy', $term->name);
    }

    public function test_wp_update_term_unslash_description()
    {
        register_taxonomy('wptests_tax', 'post');
        $t = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );

        $found = wp_update_term(
            $t,
            'wptests_tax',
            [
                'description' => 'Let\\\'s all say \\"Hooray\\" for waggypuppy taxonomy',
            ],
        );

        $term = get_term($found['term_id'], 'wptests_tax');
        _unregister_taxonomy('wptests_tax');

        $this->assertSame('Let\'s all say "Hooray" for waggypuppy taxonomy', $term->description);
    }

    public function test_wp_update_term_name_empty_string()
    {
        register_taxonomy('wptests_tax', 'post');
        $t = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );

        $found = wp_update_term(
            $t,
            'wptests_tax',
            [
                'name' => '',
            ],
        );

        $this->assertWPError($found);
        $this->assertSame('empty_term_name', $found->get_error_code());
        _unregister_taxonomy('wptests_tax');
    }

    /**
     * @ticket 29614
     */
    public function test_wp_update_term_parent_does_not_exist()
    {
        register_taxonomy(
            'wptests_tax',
            [
                'hierarchical' => true,
            ],
        );
        $fake_term_id = 787878;

        $this->assertNull(term_exists($fake_term_id, 'wptests_tax'));

        $t = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );

        $found = wp_update_term(
            $t,
            'wptests_tax',
            [
                'parent' => $fake_term_id,
            ],
        );

        $this->assertWPError($found);
        $this->assertSame('missing_parent', $found->get_error_code());

        $term = get_term($t, 'wptests_tax');
        $this->assertSame(0, $term->parent);
        _unregister_taxonomy('wptests_tax');
    }

    public function test_wp_update_term_slug_empty_string_while_not_updating_name()
    {
        register_taxonomy('wptests_tax', 'post');
        $t = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
                'name' => 'Foo Bar',
            ],
        );

        $found = wp_update_term(
            $t,
            'wptests_tax',
            [
                'slug' => '',
            ],
        );

        $term = get_term($t, 'wptests_tax');
        $this->assertSame('foo-bar', $term->slug);
        _unregister_taxonomy('wptests_tax');
    }

    public function test_wp_update_term_slug_empty_string_while_updating_name()
    {
        register_taxonomy('wptests_tax', 'post');
        $t = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );

        $found = wp_update_term(
            $t,
            'wptests_tax',
            [
                'name' => 'Foo Bar',
                'slug' => '',
            ],
        );

        $term = get_term($t, 'wptests_tax');
        $this->assertSame('foo-bar', $term->slug);
        _unregister_taxonomy('wptests_tax');
    }

    public function test_wp_update_term_slug_set_slug()
    {
        register_taxonomy('wptests_tax', 'post');
        $t = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );

        $found = wp_update_term(
            $t,
            'wptests_tax',
            [
                'slug' => 'foo-bar',
            ],
        );

        $term = get_term($t, 'wptests_tax');
        $this->assertSame('foo-bar', $term->slug);
        _unregister_taxonomy('wptests_tax');
    }

    /**
     * @ticket 5809
     */
    public function test_wp_update_term_should_not_create_duplicate_slugs_within_the_same_taxonomy()
    {
        register_taxonomy('wptests_tax', 'post');

        $t1 = self::factory()->term->create(
            [
                'name' => 'Foo',
                'slug' => 'foo',
                'taxonomy' => 'wptests_tax',
            ],
        );

        $t2 = self::factory()->term->create(
            [
                'name' => 'Bar',
                'slug' => 'bar',
                'taxonomy' => 'wptests_tax',
            ],
        );

        $updated = wp_update_term(
            $t2,
            'wptests_tax',
            [
                'slug' => 'foo',
            ],
        );

        $this->assertWPError($updated);
        $this->assertSame('duplicate_term_slug', $updated->get_error_code());
    }

    /**
     * @ticket 5809
     */
    public function test_wp_update_term_should_allow_duplicate_slugs_in_different_taxonomy()
    {
        register_taxonomy('wptests_tax', 'post');
        register_taxonomy('wptests_tax_2', 'post');

        $t1 = self::factory()->term->create(
            [
                'name' => 'Foo',
                'slug' => 'foo',
                'taxonomy' => 'wptests_tax',
            ],
        );

        $t2 = self::factory()->term->create(
            [
                'name' => 'Foo',
                'slug' => 'bar',
                'taxonomy' => 'wptests_tax_2',
            ],
        );

        $updated = wp_update_term(
            $t2,
            'wptests_tax_2',
            [
                'slug' => 'foo',
            ],
        );

        $this->assertNotWPError($updated);

        $t1_term = get_term($t1, 'wptests_tax');
        $t2_term = get_term($t2, 'wptests_tax_2');
        $this->assertSame($t1_term->slug, $t2_term->slug);
    }

    /**
     * @ticket 30780
     */
    public function test_wp_update_term_should_allow_duplicate_names_in_different_taxonomies()
    {
        register_taxonomy('wptests_tax', 'post');
        register_taxonomy('wptests_tax_2', 'post');

        $t1 = self::factory()->term->create(
            [
                'name' => 'Foo',
                'slug' => 'foo',
                'taxonomy' => 'wptests_tax',
            ],
        );

        $t2 = self::factory()->term->create(
            [
                'name' => 'Bar',
                'slug' => 'bar',
                'taxonomy' => 'wptests_tax_2',
            ],
        );

        $updated = wp_update_term(
            $t2,
            'wptests_tax_2',
            [
                'name' => 'Foo',
            ],
        );

        $this->assertNotWPError($updated);

        $t2_term = get_term($t2, 'wptests_tax_2');
        $this->assertSame('Foo', $t2_term->name);
    }

    /**
     * @ticket 30780
     */
    public function test_wp_update_term_should_allow_duplicate_names_at_different_levels_of_the_same_taxonomy()
    {
        register_taxonomy(
            'wptests_tax',
            'post',
            [
                'hierarchical' => true,
            ],
        );

        $t1 = self::factory()->term->create(
            [
                'name' => 'Foo',
                'slug' => 'foo',
                'taxonomy' => 'wptests_tax',
            ],
        );

        $t2 = self::factory()->term->create(
            [
                'name' => 'Bar',
                'slug' => 'bar',
                'taxonomy' => 'wptests_tax',
                'parent' => $t1,
            ],
        );

        $t3 = self::factory()->term->create(
            [
                'name' => 'Bar Child',
                'slug' => 'bar-child',
                'taxonomy' => 'wptests_tax',
                'parent' => $t2,
            ],
        );

        $updated = wp_update_term(
            $t3,
            'wptests_tax',
            [
                'name' => 'Bar',
            ],
        );

        $this->assertNotWPError($updated);

        $t3_term = get_term($t3, 'wptests_tax');
        $this->assertSame('Bar', $t3_term->name);
    }

    /**
     * @ticket 5809
     */
    public function test_wp_update_term_should_split_shared_term()
    {
        global $wpdb;

        register_taxonomy('wptests_tax', 'post');
        register_taxonomy('wptests_tax_2', 'post');

        $t1 = wp_insert_term('Foo', 'wptests_tax');
        $t2 = wp_insert_term('Foo', 'wptests_tax_2');

        // Manually modify because shared terms shouldn't naturally occur.
        $wpdb->update(
            $wpdb->term_taxonomy,
            ['term_id' => $t1['term_id']],
            ['term_taxonomy_id' => $t2['term_taxonomy_id']],
            ['%d'],
            ['%d'],
        );

        $posts = self::factory()->post->create_many(2);
        wp_set_object_terms($posts[0], ['Foo'], 'wptests_tax');
        wp_set_object_terms($posts[1], ['Foo'], 'wptests_tax_2');

        // Verify that the terms are shared.
        $t1_terms = wp_get_object_terms($posts[0], 'wptests_tax');
        $t2_terms = wp_get_object_terms($posts[1], 'wptests_tax_2');
        $this->assertSame($t1_terms[0]->term_id, $t2_terms[0]->term_id);

        wp_update_term(
            $t2_terms[0]->term_id,
            'wptests_tax_2',
            [
                'name' => 'New Foo',
            ],
        );

        $t1_terms = wp_get_object_terms($posts[0], 'wptests_tax');
        $t2_terms = wp_get_object_terms($posts[1], 'wptests_tax_2');
        $this->assertNotEquals($t1_terms[0]->term_id, $t2_terms[0]->term_id);
    }

    public function test_wp_update_term_alias_of_no_term_group()
    {
        register_taxonomy('wptests_tax', 'post');
        $t1 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );
        $term_1 = get_term($t1, 'wptests_tax');

        $created_term_ids = wp_insert_term('Foo', 'wptests_tax');
        wp_update_term(
            $created_term_ids['term_id'],
            'wptests_tax',
            [
                'alias_of' => $term_1->slug,
            ],
        );
        $created_term = get_term($created_term_ids['term_id'], 'wptests_tax');

        $updated_term_1 = get_term($t1, 'wptests_tax');
        _unregister_taxonomy('wptests_tax');

        $this->assertSame(0, $term_1->term_group);
        $this->assertNotEmpty($created_term->term_group);
        $this->assertSame($created_term->term_group, $updated_term_1->term_group);
    }

    public function test_wp_update_term_alias_of_existing_term_group()
    {
        register_taxonomy('wptests_tax', 'post');
        $t1 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );
        $term_1 = get_term($t1, 'wptests_tax');

        $t2 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
                'alias_of' => $term_1->slug,
            ],
        );
        $term_2 = get_term($t2, 'wptests_tax');

        $created_term_ids = wp_insert_term('Foo', 'wptests_tax');
        wp_update_term(
            $created_term_ids['term_id'],
            'wptests_tax',
            [
                'alias_of' => $term_2->slug,
            ],
        );
        $created_term = get_term($created_term_ids['term_id'], 'wptests_tax');
        _unregister_taxonomy('wptests_tax');

        $this->assertNotEmpty($created_term->term_group);
        $this->assertSame($created_term->term_group, $term_2->term_group);
    }

    public function test_wp_update_term_alias_of_nonexistent_term()
    {
        register_taxonomy('wptests_tax', 'post');
        $created_term_ids = wp_insert_term('Foo', 'wptests_tax');
        wp_update_term(
            $created_term_ids['term_id'],
            'wptests_tax',
            [
                'alias_of' => 'bar',
            ],
        );
        $created_term = get_term($created_term_ids['term_id'], 'wptests_tax');
        _unregister_taxonomy('wptests_tax');

        $this->assertSame(0, $created_term->term_group);
    }

    public function test_wp_update_term_slug_same_as_old_slug()
    {
        register_taxonomy('wptests_tax', 'post');
        $t = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
                'slug' => 'foo',
            ],
        );

        $found = wp_update_term(
            $t,
            'wptests_tax',
            [
                'slug' => 'foo',
            ],
        );

        $term = get_term($t, 'wptests_tax');

        $this->assertSame($t, $found['term_id']);
        $this->assertSame('foo', $term->slug);
        _unregister_taxonomy('wptests_tax');
    }

    public function test_wp_update_term_duplicate_slug_generated_due_to_empty_slug_param()
    {
        register_taxonomy('wptests_tax', 'post');
        $t1 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
                'slug' => 'foo-bar',
            ],
        );
        $t2 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
                'name' => 'not foo bar',
            ],
        );

        $found = wp_update_term(
            $t2,
            'wptests_tax',
            [
                'slug' => '',
                'name' => 'Foo? Bar!', // Will sanitize to 'foo-bar'.
            ],
        );

        $term = get_term($t2, 'wptests_tax');

        $this->assertSame($t2, $found['term_id']);
        $this->assertSame('foo-bar-2', $term->slug);
        _unregister_taxonomy('wptests_tax');
    }

    public function test_wp_update_term_duplicate_slug_with_changed_parent()
    {
        register_taxonomy(
            'wptests_tax',
            'post',
            [
                'hierarchical' => true,
            ],
        );
        $p = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );
        $t1 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
                'slug' => 'foo-bar',
            ],
        );
        $t2 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );

        $found = wp_update_term(
            $t2,
            'wptests_tax',
            [
                'parent' => $p,
                'slug' => 'foo-bar',
            ],
        );

        $term = get_term($t2, 'wptests_tax');
        $parent_term = get_term($p, 'wptests_tax');

        $this->assertSame($t2, $found['term_id']);
        $this->assertSame('foo-bar-' . $parent_term->slug, $term->slug);
        _unregister_taxonomy('wptests_tax');
    }

    public function test_wp_update_term_duplicate_slug_failure()
    {
        register_taxonomy('wptests_tax', 'post');
        $t1 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
                'slug' => 'foo-bar',
            ],
        );
        $t2 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
                'slug' => 'my-old-slug',
            ],
        );

        $found = wp_update_term(
            $t2,
            'wptests_tax',
            [
                'slug' => 'foo-bar',
            ],
        );

        $term = get_term($t2, 'wptests_tax');

        $this->assertWPError($found);
        $this->assertSame('duplicate_term_slug', $found->get_error_code());
        $this->assertSame('my-old-slug', $term->slug);
        _unregister_taxonomy('wptests_tax');
    }

    public function test_wp_update_term_should_return_term_id_and_term_taxonomy_id()
    {
        register_taxonomy('wptests_tax', 'post');
        $t = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );
        $found = wp_update_term(
            $t,
            'wptests_tax',
            [
                'slug' => 'foo',
            ],
        );

        $term_by_id = get_term($found['term_id'], 'wptests_tax');
        $term_by_slug = get_term_by('slug', 'foo', 'wptests_tax');
        $term_by_ttid = get_term_by('term_taxonomy_id', $found['term_taxonomy_id'], 'wptests_tax');

        _unregister_taxonomy('wptests_tax');

        $this->assertIsArray($found);
        $this->assertNotEmpty($found['term_id']);
        $this->assertNotEmpty($found['term_taxonomy_id']);
        $this->assertNotEmpty($term_by_id);
        $this->assertEquals($term_by_id, $term_by_slug);
        $this->assertEquals($term_by_id, $term_by_ttid);
    }

    /**
     * @ticket 32876
     */
    public function test_wp_update_term_should_return_int_values_for_term_id_and_term_taxonomy_id()
    {
        register_taxonomy('wptests_tax', 'post');
        $t = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );
        $found = wp_update_term(
            $t,
            'wptests_tax',
            [
                'slug' => 'foo',
            ],
        );

        $this->assertIsInt($found['term_id']);
        $this->assertIsInt($found['term_taxonomy_id']);
    }

    public function test_wp_update_term_should_clean_term_cache()
    {
        register_taxonomy(
            'wptests_tax',
            'post',
            [
                'hierarchical' => true,
            ],
        );

        $t1 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );
        $t2 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
            ],
        );

        /*
         * It doesn't appear that waggypuppy itself ever sets these
         * caches, but we should ensure that they're being cleared for
         * compatibility with third-party addons. Prime the caches
         * manually.
         */
        wp_cache_set('all_ids', [1, 2, 3], 'wptests_tax');
        wp_cache_set('get', [1, 2, 3], 'wptests_tax');

        $found = wp_update_term(
            $t1,
            'wptests_tax',
            [
                'parent' => $t2,
            ],
        );
        _unregister_taxonomy('wptests_tax');

        $this->assertFalse(wp_cache_get('all_ids', 'wptests_tax'));
        $this->assertFalse(wp_cache_get('get', 'wptests_tax'));

        $cached_children = get_option('wptests_tax_children');
        $this->assertNotEmpty($cached_children[$t2]);
        $this->assertContains($found['term_id'], $cached_children[$t2]);
    }

    /**
     * @ticket 30780
     */
    public function test_wp_update_term_should_assign_new_slug_when_reassigning_parent_as_long_as_there_is_no_other_term_with_the_same_slug(
    )
    {
        register_taxonomy(
            'wptests_tax',
            'post',
            [
                'hierarchical' => true,
            ],
        );
        register_taxonomy(
            'wptests_tax_2',
            'post',
            [
                'hierarchical' => true,
            ],
        );

        $t1 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
                'slug' => 'parent-term',
            ],
        );

        $t2 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
                'slug' => 'foo',
            ],
        );

        wp_update_term(
            $t2,
            'wptests_tax',
            [
                'parent' => $t1,
            ],
        );

        $t2_term = get_term($t2, 'wptests_tax');

        $this->assertSame('foo', $t2_term->slug);

        _unregister_taxonomy('wptests_tax');
    }

    /**
     * @ticket 30780
     */
    public function test_wp_update_term_should_not_assign_new_slug_when_reassigning_parent_as_long_as_there_is_no_other_slug_conflict_within_the_taxonomy(
    )
    {
        register_taxonomy(
            'wptests_tax',
            'post',
            [
                'hierarchical' => true,
            ],
        );
        register_taxonomy(
            'wptests_tax_2',
            'post',
            [
                'hierarchical' => true,
            ],
        );

        $t1 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
                'slug' => 'parent-term',
            ],
        );

        // Same slug but in a different tax.
        $t2 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax_2',
                'slug' => 'foo',
            ],
        );

        $t3 = self::factory()->term->create(
            [
                'taxonomy' => 'wptests_tax',
                'slug' => 'foo',
            ],
        );

        wp_update_term(
            $t3,
            'wptests_tax',
            [
                'parent' => $t1,
            ],
        );

        $t3_term = get_term($t3, 'wptests_tax');

        $this->assertSame('foo', $t3_term->slug);

        _unregister_taxonomy('wptests_tax');
    }

    /**
     * @ticket 31954
     */
    public function test_wp_update_term_with_null_get_term()
    {
        $t = self::factory()->term->create(['taxonomy' => 'category']);
        $found = wp_update_term($t, 'post_tag', ['slug' => 'foo']);

        $this->assertWPError($found);
        $this->assertSame('invalid_term', $found->get_error_code());
    }
}
