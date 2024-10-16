<?php

/**
 * @group editor
 *
 * @covers ::_enable_content_editor_for_navigation_post_type
 */
class Tests_Editor_EnableContentEditorForNavigationPostType extends WP_UnitTestCase
{
    const NAVIGATION_POST_TYPE = 'wp_navigation';

    public function tear_down()
    {
        add_post_type_support(static::NAVIGATION_POST_TYPE, 'editor');
        parent::tear_down();
    }

    /**
     * @ticket 56266
     */
    public function test_should_be_enabled_by_default()
    {
        $this->assertTrue(post_type_supports(static::NAVIGATION_POST_TYPE, 'editor'));
    }

    /**
     * @ticket 56266
     */
    public function test_should_enable()
    {
        $post = $this->create_post(static::NAVIGATION_POST_TYPE);

        _enable_content_editor_for_navigation_post_type($post);

        $this->assertTrue(post_type_supports(static::NAVIGATION_POST_TYPE, 'editor'));
    }

    /**
     * @ticket 56266
     */
    public function test_should_reenable_when_disabled()
    {
        $post = $this->create_post(static::NAVIGATION_POST_TYPE);

        // Set up the test by removing the 'editor' post type support.
        remove_post_type_support(static::NAVIGATION_POST_TYPE, 'editor');
        $this->assertFalse(post_type_supports(static::NAVIGATION_POST_TYPE, 'editor'));

        _enable_content_editor_for_navigation_post_type($post);

        $this->assertTrue(post_type_supports(static::NAVIGATION_POST_TYPE, 'editor'));
    }

    /**
     * @dataProvider data_should_not_enable
     * @ticket       56266
     *
     * @param string $post_type Post type to test.
     */
    public function test_should_not_enable($post_type)
    {
        $post = $this->create_post($post_type);

        _enable_content_editor_for_navigation_post_type($post);

        $this->assertFalse(post_type_supports($post_type, 'editor'));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function data_should_not_enable()
    {
        return [
            'invalid post type' => ['book'],
            'attachments' => ['attachments'],
            'revision' => ['revision'],
            'custom_css' => ['custom_css'],
            'customize_changeset' => ['customize_changeset'],
        ];
    }

    /**
     * @dataProvider data_should_not_change_post_type_support
     * @ticket       56266
     *
     * @param string $post_type Post type to test.
     */
    public function test_should_not_change_post_type_support($post_type)
    {
        $post = $this->create_post($post_type);

        // Capture the original support.
        $before = post_type_supports($post_type, 'editor');

        _enable_content_editor_for_navigation_post_type($post);

        // Ensure it did not change.
        $this->assertSame($before, post_type_supports($post_type, 'editor'));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function data_should_not_change_post_type_support()
    {
        return [
            'post' => ['post'],
            'page' => ['page'],
            'attachments' => ['attachments'],
            'revision' => ['revision'],
            'custom_css' => ['custom_css'],
            'customize_changeset' => ['customize_changeset'],
            'nav_menu_item' => ['nav_menu_item'],
            'oembed_cache' => ['oembed_cache'],
            'user_request' => ['user_request'],
            'wp_block' => ['wp_block'],
            'wp_template' => ['wp_template'],
            'wp_template_part' => ['wp_template_part'],
            'wp_global_styles' => ['wp_global_styles'],
        ];
    }

    /**
     * Creates a post.
     *
     * @param string $post_type Post type to create.
     * @return int
     */
    private function create_post($post_type)
    {
        return $this->factory()->post->create(
            ['post_type' => $post_type],
        );
    }
}
