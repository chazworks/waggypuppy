<?php

/**
 * @group post
 */
class Tests_Post_GetPostTypeLabels extends WP_UnitTestCase
{
    public function test_returns_an_object()
    {
        $this->assertIsObject(
            get_post_type_labels(
                (object)[
                    'name' => 'foo',
                    'labels' => [],
                    'hierarchical' => false,
                ],
            ),
        );
    }

    public function test_returns_hierarchical_labels()
    {
        $labels = get_post_type_labels(
            (object)[
                'name' => 'foo',
                'labels' => [],
                'hierarchical' => true,
            ],
        );

        $this->assertSame('Pages', $labels->name);
    }

    public function test_existing_labels_are_not_overridden()
    {
        $labels = get_post_type_labels(
            (object)[
                'name' => 'foo',
                'labels' => [
                    'singular_name' => 'Foo',
                ],
                'hierarchical' => false,
            ],
        );

        $this->assertSame('Foo', $labels->singular_name);
    }

    public function test_name_admin_bar_label_should_fall_back_to_singular_name()
    {
        $labels = get_post_type_labels(
            (object)[
                'name' => 'foo',
                'labels' => [
                    'singular_name' => 'Foo',
                ],
                'hierarchical' => false,
            ],
        );

        $this->assertSame('Foo', $labels->name_admin_bar);
    }


    public function test_name_admin_bar_label_should_fall_back_to_post_type_name()
    {
        $labels = get_post_type_labels(
            (object)[
                'name' => 'bar',
                'labels' => [],
                'hierarchical' => false,
            ],
        );

        $this->assertSame('bar', $labels->name_admin_bar);
    }

    public function test_menu_name_should_fall_back_to_name()
    {
        $labels = get_post_type_labels(
            (object)[
                'name' => 'foo',
                'labels' => [
                    'name' => 'Bar',
                ],
                'hierarchical' => false,
            ],
        );

        $this->assertSame('Bar', $labels->menu_name);
    }

    public function test_labels_should_be_added_when_registering_a_post_type()
    {
        $post_type_object = register_post_type(
            'foo',
            [
                'labels' => [
                    'singular_name' => 'bar',
                ],
            ],
        );

        unregister_post_type('foo');

        $this->assertObjectHasProperty('labels', $post_type_object);
        $this->assertObjectHasProperty('label', $post_type_object);
        $this->assertObjectHasProperty('not_found_in_trash', $post_type_object->labels);
    }

    public function test_label_should_be_derived_from_labels_when_registering_a_post_type()
    {
        $post_type_object = register_post_type(
            'foo',
            [
                'labels' => [
                    'name' => 'bar',
                ],
            ],
        );

        $this->assertSame('bar', $post_type_object->label);

        unregister_post_type('foo');
    }

    /**
     * @ticket 33543
     */
    public function test_should_fall_back_on_defaults_when_filtered_labels_do_not_contain_the_keys()
    {
        add_filter('post_type_labels_foo', [$this, 'filter_post_type_labels']);
        register_post_type('foo');

        $this->assertObjectHasProperty('featured_image', get_post_type_object('foo')->labels);
        $this->assertObjectHasProperty('set_featured_image', get_post_type_object('foo')->labels);

        unregister_post_type('foo');
        remove_filter('post_type_labels_foo', [$this, 'filter_post_type_labels']);
    }

    public function filter_post_type_labels($labels)
    {
        unset($labels->featured_image);
        unset($labels->set_featured_image);

        return $labels;
    }
}
