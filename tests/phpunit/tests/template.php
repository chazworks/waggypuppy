<?php

/**
 * test wp-includes/template.php
 *
 * @group themes
 */
class Tests_Template extends WP_UnitTestCase
{

    protected $hierarchy = [];

    protected static $page_on_front;
    protected static $page_for_posts;
    protected static $page;
    protected static $post;

    /**
     * Page For Privacy Policy.
     *
     * @since 5.2.0
     *
     * @var WP_Post $page_for_privacy_policy
     */
    protected static $page_for_privacy_policy;

    public static function wpSetUpBeforeClass(WP_UnitTest_Factory $factory)
    {
        self::$page_on_front = $factory->post->create_and_get(
            [
                'post_type' => 'page',
                'post_name' => 'page-on-front-😀',
            ],
        );

        self::$page_for_posts = $factory->post->create_and_get(
            [
                'post_type' => 'page',
                'post_name' => 'page-for-posts-😀',
            ],
        );

        self::$page = $factory->post->create_and_get(
            [
                'post_type' => 'page',
                'post_name' => 'page-name-😀',
            ],
        );
        add_post_meta(self::$page->ID, '_wp_page_template', 'templates/page.php');

        self::$post = $factory->post->create_and_get(
            [
                'post_type' => 'post',
                'post_name' => 'post-name-😀',
                'post_date' => '1984-02-25 12:34:56',
            ],
        );
        set_post_format(self::$post, 'quote');
        add_post_meta(self::$post->ID, '_wp_page_template', 'templates/post.php');

        self::$page_for_privacy_policy = $factory->post->create_and_get(
            [
                'post_type' => 'page',
                'post_title' => 'Privacy Policy',
            ],
        );
    }

    public function set_up()
    {
        parent::set_up();
        register_post_type(
            'cpt',
            [
                'public' => true,
            ],
        );
        register_taxonomy(
            'taxo',
            'post',
            [
                'public' => true,
                'hierarchical' => true,
            ],
        );
        $this->set_permalink_structure('/%year%/%monthnum%/%day%/%postname%/');
    }

    public function tear_down()
    {
        unregister_post_type('cpt');
        unregister_taxonomy('taxo');
        $this->set_permalink_structure('');
        parent::tear_down();
    }


    public function test_404_template_hierarchy()
    {
        $url = add_query_arg(
            [
                'p' => '-1',
            ],
            home_url(),
        );

        $this->assertTemplateHierarchy(
            $url,
            [
                '404.php',
            ],
        );
    }

    public function test_author_template_hierarchy()
    {
        $author = self::factory()->user->create_and_get(
            [
                'user_nicename' => 'foo',
            ],
        );

        $this->assertTemplateHierarchy(
            get_author_posts_url($author->ID),
            [
                'author-foo.php',
                "author-{$author->ID}.php",
                'author.php',
                'archive.php',
            ],
        );
    }

    public function test_category_template_hierarchy()
    {
        $term = self::factory()->term->create_and_get(
            [
                'taxonomy' => 'category',
                'slug' => 'foo-😀',
            ],
        );

        $this->assertTemplateHierarchy(
            get_term_link($term),
            [
                'category-foo-😀.php',
                'category-foo-%f0%9f%98%80.php',
                "category-{$term->term_id}.php",
                'category.php',
                'archive.php',
            ],
        );
    }

    public function test_tag_template_hierarchy()
    {
        $term = self::factory()->term->create_and_get(
            [
                'taxonomy' => 'post_tag',
                'slug' => 'foo-😀',
            ],
        );

        $this->assertTemplateHierarchy(
            get_term_link($term),
            [
                'tag-foo-😀.php',
                'tag-foo-%f0%9f%98%80.php',
                "tag-{$term->term_id}.php",
                'tag.php',
                'archive.php',
            ],
        );
    }

    public function test_taxonomy_template_hierarchy()
    {
        $term = self::factory()->term->create_and_get(
            [
                'taxonomy' => 'taxo',
                'slug' => 'foo-😀',
            ],
        );

        $this->assertTemplateHierarchy(
            get_term_link($term),
            [
                'taxonomy-taxo-foo-😀.php',
                'taxonomy-taxo-foo-%f0%9f%98%80.php',
                'taxonomy-taxo.php',
                'taxonomy.php',
                'archive.php',
            ],
        );
    }

    public function test_date_template_hierarchy_for_year()
    {
        $this->assertTemplateHierarchy(
            get_year_link(1984),
            [
                'date.php',
                'archive.php',
            ],
        );
    }

    public function test_date_template_hierarchy_for_month()
    {
        $this->assertTemplateHierarchy(
            get_month_link(1984, 2),
            [
                'date.php',
                'archive.php',
            ],
        );
    }

    public function test_date_template_hierarchy_for_day()
    {
        $this->assertTemplateHierarchy(
            get_day_link(1984, 2, 25),
            [
                'date.php',
                'archive.php',
            ],
        );
    }

    public function test_search_template_hierarchy()
    {
        $url = add_query_arg(
            [
                's' => 'foo',
            ],
            home_url(),
        );

        $this->assertTemplateHierarchy(
            $url,
            [
                'search.php',
            ],
        );
    }

    public function test_front_page_template_hierarchy_with_posts_on_front()
    {
        $this->assertSame('posts', get_option('show_on_front'));
        $this->assertTemplateHierarchy(
            home_url(),
            [
                'front-page.php',
                'home.php',
                'index.php',
            ],
        );
    }

    public function test_front_page_template_hierarchy_with_page_on_front()
    {
        update_option('show_on_front', 'page');
        update_option('page_on_front', self::$page_on_front->ID);
        update_option('page_for_posts', self::$page_for_posts->ID);

        $this->assertTemplateHierarchy(
            home_url(),
            [
                'front-page.php',
                'page-page-on-front-😀.php',
                'page-page-on-front-%f0%9f%98%80.php',
                'page-' . self::$page_on_front->ID . '.php',
                'page.php',
                'singular.php',
            ],
        );
    }

    public function test_home_template_hierarchy_with_page_on_front()
    {
        update_option('show_on_front', 'page');
        update_option('page_on_front', self::$page_on_front->ID);
        update_option('page_for_posts', self::$page_for_posts->ID);

        $this->assertTemplateHierarchy(
            get_permalink(self::$page_for_posts),
            [
                'home.php',
                'index.php',
            ],
        );
    }

    public function test_page_template_hierarchy()
    {
        $this->assertTemplateHierarchy(
            get_permalink(self::$page),
            [
                'templates/page.php',
                'page-page-name-😀.php',
                'page-page-name-%f0%9f%98%80.php',
                'page-' . self::$page->ID . '.php',
                'page.php',
                'singular.php',
            ],
        );
    }

    /**
     * @ticket 44005
     * @group privacy
     */
    public function test_privacy_template_hierarchy()
    {
        update_option('wp_page_for_privacy_policy', self::$page_for_privacy_policy->ID);

        $this->assertTemplateHierarchy(
            get_permalink(self::$page_for_privacy_policy->ID),
            [
                'privacy-policy.php',
                'page-privacy-policy.php',
                'page-' . self::$page_for_privacy_policy->ID . '.php',
                'page.php',
                'singular.php',
            ],
        );
    }

    /**
     * @ticket 18375
     */
    public function test_single_template_hierarchy_for_post()
    {
        $this->assertTemplateHierarchy(
            get_permalink(self::$post),
            [
                'templates/post.php',
                'single-post-post-name-😀.php',
                'single-post-post-name-%f0%9f%98%80.php',
                'single-post.php',
                'single.php',
                'singular.php',
            ],
        );
    }

    public function test_single_template_hierarchy_for_custom_post_type()
    {
        $cpt = self::factory()->post->create_and_get(
            [
                'post_type' => 'cpt',
                'post_name' => 'cpt-name-😀',
            ],
        );

        $this->assertTemplateHierarchy(
            get_permalink($cpt),
            [
                'single-cpt-cpt-name-😀.php',
                'single-cpt-cpt-name-%f0%9f%98%80.php',
                'single-cpt.php',
                'single.php',
                'singular.php',
            ],
        );
    }

    /**
     * @ticket 18375
     */
    public function test_single_template_hierarchy_for_custom_post_type_with_template()
    {
        $cpt = self::factory()->post->create_and_get(
            [
                'post_type' => 'cpt',
                'post_name' => 'cpt-name-😀',
            ],
        );
        add_post_meta($cpt->ID, '_wp_page_template', 'templates/cpt.php');

        $this->assertTemplateHierarchy(
            get_permalink($cpt),
            [
                'templates/cpt.php',
                'single-cpt-cpt-name-😀.php',
                'single-cpt-cpt-name-%f0%9f%98%80.php',
                'single-cpt.php',
                'single.php',
                'singular.php',
            ],
        );
    }

    public function test_attachment_template_hierarchy()
    {
        $attachment = self::factory()->attachment->create_and_get(
            [
                'post_name' => 'attachment-name-😀',
                'file' => 'image.jpg',
                'post_mime_type' => 'image/jpeg',
            ],
        );
        $this->assertTemplateHierarchy(
            get_permalink($attachment),
            [
                'image-jpeg.php',
                'jpeg.php',
                'image.php',
                'attachment.php',
                'single-attachment-attachment-name-😀.php',
                'single-attachment-attachment-name-%f0%9f%98%80.php',
                'single-attachment.php',
                'single.php',
                'singular.php',
            ],
        );
    }

    /**
     * @ticket 18375
     */
    public function test_attachment_template_hierarchy_with_template()
    {
        $attachment = self::factory()->attachment->create_and_get(
            [
                'post_name' => 'attachment-name-😀',
                'file' => 'image.jpg',
                'post_mime_type' => 'image/jpeg',
            ],
        );

        add_post_meta($attachment, '_wp_page_template', 'templates/cpt.php');

        $this->assertTemplateHierarchy(
            get_permalink($attachment),
            [
                'image-jpeg.php',
                'jpeg.php',
                'image.php',
                'attachment.php',
                'single-attachment-attachment-name-😀.php',
                'single-attachment-attachment-name-%f0%9f%98%80.php',
                'single-attachment.php',
                'single.php',
                'singular.php',
            ],
        );
    }

    public function test_embed_template_hierarchy_for_post()
    {
        $this->assertTemplateHierarchy(
            get_post_embed_url(self::$post),
            [
                'embed-post-quote.php',
                'embed-post.php',
                'embed.php',
                'templates/post.php',
                'single-post-post-name-😀.php',
                'single-post-post-name-%f0%9f%98%80.php',
                'single-post.php',
                'single.php',
                'singular.php',
            ],
        );
    }

    public function test_embed_template_hierarchy_for_page()
    {
        $this->assertTemplateHierarchy(
            get_post_embed_url(self::$page),
            [
                'embed-page.php',
                'embed.php',
                'templates/page.php',
                'page-page-name-😀.php',
                'page-page-name-%f0%9f%98%80.php',
                'page-' . self::$page->ID . '.php',
                'page.php',
                'singular.php',
            ],
        );
    }

    /**
     * Tests that `locate_template()` uses the current theme even after switching the theme.
     *
     * @ticket 18298
     *
     * @covers ::locate_template
     */
    public function test_locate_template_uses_current_theme()
    {
        $themes = wp_get_themes();

        // Look for parent themes with an index.php template.
        $relevant_themes = [];
        foreach ($themes as $theme) {
            if ($theme->get_stylesheet() !== $theme->get_template()) {
                continue;
            }
            $php_templates = $theme['Template Files'];
            if (!isset($php_templates['index.php'])) {
                continue;
            }
            $relevant_themes[] = $theme;
        }
        if (count($relevant_themes) < 2) {
            $this->markTestSkipped('Test requires at least two parent themes with an index.php template.');
        }

        $template_names = ['index.php'];

        $old_theme = $relevant_themes[0];
        $new_theme = $relevant_themes[1];

        switch_theme($old_theme->get_stylesheet());
        $this->assertSame($old_theme->get_stylesheet_directory() . '/index.php', locate_template($template_names),
            'Incorrect index template found in initial theme.');

        switch_theme($new_theme->get_stylesheet());
        $this->assertSame($new_theme->get_stylesheet_directory() . '/index.php', locate_template($template_names),
            'Incorrect index template found in theme after switch.');
    }

    public function assertTemplateHierarchy($url, array $expected, $message = '')
    {
        $this->go_to($url);
        $hierarchy = $this->get_template_hierarchy();

        $this->assertSame($expected, $hierarchy, $message);
    }

    protected static function get_query_template_conditions()
    {
        return [
            'embed' => 'is_embed',
            '404' => 'is_404',
            'search' => 'is_search',
            'front_page' => 'is_front_page',
            'home' => 'is_home',
            'privacy_policy' => 'is_privacy_policy',
            'post_type_archive' => 'is_post_type_archive',
            'taxonomy' => 'is_tax',
            'attachment' => 'is_attachment',
            'single' => 'is_single',
            'page' => 'is_page',
            'singular' => 'is_singular',
            'category' => 'is_category',
            'tag' => 'is_tag',
            'author' => 'is_author',
            'date' => 'is_date',
            'archive' => 'is_archive',
            'paged' => 'is_paged',
        ];
    }

    protected function get_template_hierarchy()
    {
        foreach (self::get_query_template_conditions() as $type => $condition) {
            if (call_user_func($condition)) {
                $filter = str_replace('_', '', $type);
                add_filter("{$filter}_template_hierarchy", [$this, 'log_template_hierarchy']);
                call_user_func("get_{$type}_template");
                remove_filter("{$filter}_template_hierarchy", [$this, 'log_template_hierarchy']);
            }
        }
        $hierarchy = $this->hierarchy;
        $this->hierarchy = [];
        return $hierarchy;
    }

    public function log_template_hierarchy(array $hierarchy)
    {
        $this->hierarchy = array_merge($this->hierarchy, $hierarchy);
        return $hierarchy;
    }
}
