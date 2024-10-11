<?php
/**
 * Tests for WP_Block.
 *
 * @package WP
 * @subpackage Blocks
 * @since 5.5.0
 *
 * @group blocks
 */
class Tests_Blocks_wpBlock extends WP_UnitTestCase
{

    /**
     * Fake block type registry.
     *
     * @var WP_Block_Type_Registry
     */
    private $registry = null;

    /**
     * Set up each test method.
     */
    public function set_up()
    {
        parent::set_up();

        $this->registry = new WP_Block_Type_Registry();
    }

    /**
     * Tear down each test method.
     */
    public function tear_down()
    {
        $this->registry = null;

        parent::tear_down();
    }

    public function filter_render_block($content, $parsed_block)
    {
        return 'Original: "' . $content . '", from block "' . $parsed_block['blockName'] . '"';
    }

    /**
     * @ticket 49927
     */
    public function test_constructor_assigns_properties_from_parsed_block()
    {
        $this->registry->register('core/example', []);

        $parsed_blocks = parse_blocks('<!-- wp:example {"ok":true} -->a<!-- wp:example /-->b<!-- /wp:example -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [];
        $block         = new WP_Block($parsed_block, $context, $this->registry);

        $this->assertSame($parsed_block, $block->parsed_block);
        $this->assertSame($parsed_block['blockName'], $block->name);
        $this->assertSame($parsed_block['attrs'], $block->attributes);
        $this->assertSame($parsed_block['innerContent'], $block->inner_content);
        $this->assertSame($parsed_block['innerHTML'], $block->inner_html);
    }

    /**
     * @ticket 49927
     * @ticket 59797
     */
    public function test_constructor_assigns_block_type_from_registry()
    {
        $block_type_settings = [
            'attributes' => [
                'defaulted' => [
                    'type'    => 'number',
                    'default' => 10,
                ],
            ],
        ];
        $this->registry->register('core/example', $block_type_settings);

        $parsed_block = ['blockName' => 'core/example'];
        $context      = [];
        $block        = new WP_Block($parsed_block, $context, $this->registry);

        $this->assertInstanceOf(WP_Block_Type::class, $block->block_type);
        $this->assertSameSetsWithIndex(
            [
                'defaulted' => [
                    'type'    => 'number',
                    'default' => 10,
                ],
                'lock'      => ['type' => 'object'],
                'metadata'  => ['type' => 'object'],
            ],
            $block->block_type->attributes
        );
    }

    /**
     * @ticket 49927
     */
    public function test_lazily_assigns_attributes_with_defaults()
    {
        $this->registry->register(
            'core/example',
            [
                'attributes' => [
                    'defaulted' => [
                        'type'    => 'number',
                        'default' => 10,
                    ],
                ],
            ]
        );

        $parsed_block = [
            'blockName' => 'core/example',
            'attrs'     => [
                'explicit' => 20,
            ],
        ];
        $context      = [];
        $block        = new WP_Block($parsed_block, $context, $this->registry);

        $this->assertSame(
            [
                'explicit'  => 20,
                'defaulted' => 10,
            ],
            $block->attributes
        );
    }

    /**
     * @ticket 49927
     */
    public function test_lazily_assigns_attributes_with_only_defaults()
    {
        $this->registry->register(
            'core/example',
            [
                'attributes' => [
                    'defaulted' => [
                        'type'    => 'number',
                        'default' => 10,
                    ],
                ],
            ]
        );

        $parsed_block = [
            'blockName' => 'core/example',
            'attrs'     => [],
        ];
        $context      = [];
        $block        = new WP_Block($parsed_block, $context, $this->registry);

        $this->assertSame(['defaulted' => 10], $block->attributes);
        // Intentionally call a second time, to ensure property was assigned.
        $this->assertSame(['defaulted' => 10], $block->attributes);
    }

    /**
     * @ticket 49927
     */
    public function test_constructor_assigns_context_from_block_type()
    {
        $this->registry->register(
            'core/example',
            [
                'uses_context' => ['requested'],
            ]
        );

        $parsed_block = ['blockName' => 'core/example'];
        $context      = [
            'requested'   => 'included',
            'unrequested' => 'not included',
        ];
        $block        = new WP_Block($parsed_block, $context, $this->registry);

        $this->assertSame(['requested' => 'included'], $block->context);
    }

    /**
     * @ticket 49927
     */
    public function test_constructor_maps_inner_blocks()
    {
        $this->registry->register('core/example', []);

        $parsed_blocks = parse_blocks('<!-- wp:example {"ok":true} -->a<!-- wp:example /-->b<!-- /wp:example -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [];
        $block         = new WP_Block($parsed_block, $context, $this->registry);

        $this->assertCount(1, $block->inner_blocks);
        $this->assertInstanceOf(WP_Block::class, $block->inner_blocks[0]);
        $this->assertSame('core/example', $block->inner_blocks[0]->name);
    }

    /**
     * @ticket 49927
     */
    public function test_constructor_prepares_context_for_inner_blocks()
    {
        $this->registry->register(
            'core/outer',
            [
                'attributes'       => [
                    'recordId' => [
                        'type' => 'number',
                    ],
                ],
                'provides_context' => [
                    'core/recordId' => 'recordId',
                ],
            ]
        );
        $this->registry->register(
            'core/inner',
            [
                'uses_context' => ['core/recordId'],
            ]
        );

        $parsed_blocks = parse_blocks('<!-- wp:outer {"recordId":10} --><!-- wp:inner /--><!-- /wp:outer -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = ['unrequested' => 'not included'];
        $block         = new WP_Block($parsed_block, $context, $this->registry);

        $this->assertCount(0, $block->context);
        $this->assertSame(
            ['core/recordId' => 10],
            $block->inner_blocks[0]->context
        );
    }

    /**
     * @ticket 49927
     */
    public function test_constructor_assigns_merged_context()
    {
        $this->registry->register(
            'core/example',
            [
                'attributes'       => [
                    'value' => [
                        'type' => ['string', 'null'],
                    ],
                ],
                'provides_context' => [
                    'core/value' => 'value',
                ],
                'uses_context'     => ['core/value'],
            ]
        );

        $parsed_blocks = parse_blocks(
            '<!-- wp:example {"value":"merged"} -->' .
            '<!-- wp:example {"value":null} -->' .
            '<!-- wp:example /-->' .
            '<!-- /wp:example -->' .
            '<!-- /wp:example -->'
        );
        $parsed_block  = $parsed_blocks[0];
        $context       = ['core/value' => 'original'];
        $block         = new WP_Block($parsed_block, $context, $this->registry);

        $this->assertSame(
            ['core/value' => 'original'],
            $block->context
        );
        $this->assertSame(
            ['core/value' => 'merged'],
            $block->inner_blocks[0]->context
        );
        $this->assertSame(
            ['core/value' => null],
            $block->inner_blocks[0]->inner_blocks[0]->context
        );
    }

    /**
     * @ticket 49927
     */
    public function test_render_static_block_type_returns_own_content()
    {
        $this->registry->register('core/static', []);
        $this->registry->register(
            'core/dynamic',
            [
                'render_callback' => static function () {
                    return 'b';
                },
            ]
        );

        $parsed_blocks = parse_blocks('<!-- wp:static -->a<!-- wp:dynamic /-->c<!-- /wp:static -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [];
        $block         = new WP_Block($parsed_block, $context, $this->registry);

        $this->assertSame('abc', $block->render());
    }

    /**
     * @ticket 49927
     */
    public function test_render_passes_block_for_render_callback()
    {
        $this->registry->register(
            'core/greeting',
            [
                'render_callback' => static function ($attributes, $content, $block) {
                    return sprintf('Hello from %s', $block->name);
                },
            ]
        );

        $parsed_blocks = parse_blocks('<!-- wp:greeting /-->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [];
        $block         = new WP_Block($parsed_block, $context, $this->registry);

        $this->assertSame('Hello from core/greeting', $block->render());
    }

    /**
     * @ticket 49927
     */
    public function test_render_applies_render_block_filter()
    {
        $this->registry->register('core/example', []);

        add_filter('render_block', [$this, 'filter_render_block'], 10, 2);

        $parsed_blocks = parse_blocks('<!-- wp:example -->Static<!-- wp:example -->Inner<!-- /wp:example --><!-- /wp:example -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [];
        $block         = new WP_Block($parsed_block, $context, $this->registry);

        $rendered_content = $block->render();

        remove_filter('render_block', [$this, 'filter_render_block']);

        $this->assertSame('Original: "StaticOriginal: "Inner", from block "core/example"", from block "core/example"', $rendered_content);
    }

    /**
     * @ticket 46187
     */
    public function test_render_applies_dynamic_render_block_filter()
    {
        $this->registry->register('core/example', []);

        add_filter('render_block_core/example', [$this, 'filter_render_block'], 10, 2);

        $parsed_blocks = parse_blocks('<!-- wp:example -->Static<!-- wp:example -->Inner<!-- /wp:example --><!-- /wp:example -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [];
        $block         = new WP_Block($parsed_block, $context, $this->registry);

        $rendered_content = $block->render();

        remove_filter('render_block_core/example', [$this, 'filter_render_block']);

        $this->assertSame('Original: "StaticOriginal: "Inner", from block "core/example"", from block "core/example"', $rendered_content);
    }

    /**
     * @ticket 49927
     */
    public function test_passes_attributes_to_render_callback()
    {
        $this->registry->register(
            'core/greeting',
            [
                'attributes'      => [
                    'toWhom'      => [
                        'type' => 'string',
                    ],
                    'punctuation' => [
                        'type'    => 'string',
                        'default' => '!',
                    ],
                ],
                'render_callback' => static function ($block_attributes) {
                    return sprintf(
                        'Hello %s%s',
                        $block_attributes['toWhom'],
                        $block_attributes['punctuation']
                    );
                },
            ]
        );

        $parsed_blocks = parse_blocks('<!-- wp:greeting {"toWhom":"world"} /-->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [];
        $block         = new WP_Block($parsed_block, $context, $this->registry);

        $this->assertSame('Hello world!', $block->render());
    }

    /**
     * @ticket 49927
     */
    public function test_passes_content_to_render_callback()
    {
        $this->registry->register(
            'core/outer',
            [
                'render_callback' => static function ($block_attributes, $content) {
                    return $content;
                },
            ]
        );
        $this->registry->register(
            'core/inner',
            [
                'render_callback' => static function () {
                    return 'b';
                },
            ]
        );

        $parsed_blocks = parse_blocks('<!-- wp:outer -->a<!-- wp:inner /-->c<!-- /wp:outer -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [];
        $block         = new WP_Block($parsed_block, $context, $this->registry);

        $this->assertSame('abc', $block->render());
    }

    /**
     * @ticket 52991
     */
    public function test_build_query_vars_from_query_block()
    {
        $this->registry->register(
            'core/example',
            ['uses_context' => ['query']]
        );

        $parsed_blocks = parse_blocks('<!-- wp:example {"ok":true} -->a<!-- wp:example /-->b<!-- /wp:example -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [
            'query' => [
                'postType'    => 'page',
                'exclude'     => [1, 2],
                'categoryIds' => [56],
                'orderBy'     => 'title',
                'tagIds'      => [3, 11, 10],
                'parents'     => [1, 2],
            ],
        ];
        $block         = new WP_Block($parsed_block, $context, $this->registry);
        $query         = build_query_vars_from_query_block($block, 1);

        $this->assertSame(
            [
                'post_type'       => 'page',
                'order'           => 'DESC',
                'orderby'         => 'title',
                'post__not_in'    => [1, 2],
                'tax_query'       => [
                    [
                        'taxonomy'         => 'category',
                        'terms'            => [56],
                        'include_children' => false,
                    ],
                    [
                        'taxonomy'         => 'post_tag',
                        'terms'            => [3, 11, 10],
                        'include_children' => false,
                    ],
                ],
                'post_parent__in' => [1, 2],
            ],
            $query
        );
    }

    /**
     * @ticket 62014
     */
    public function test_build_query_vars_from_query_block_standard_post_formats()
    {
        $this->registry->register(
            'core/example',
            ['uses_context' => ['query']]
        );

        $parsed_blocks = parse_blocks('<!-- wp:example {"ok":true} -->a<!-- wp:example /-->b<!-- /wp:example -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [
            'query' => [
                'postType' => 'post',
                'format'   => ['standard'],
            ],
        ];
        $block         = new WP_Block($parsed_block, $context, $this->registry);
        $query         = build_query_vars_from_query_block($block, 1);

        $this->assertSame(
            [
                'post_type'    => 'post',
                'order'        => 'DESC',
                'orderby'      => 'date',
                'post__not_in' => [],
                'tax_query'    => [
                    'relation' => 'OR',
                    [
                        'taxonomy' => 'post_format',
                        'field'    => 'slug',
                        'operator' => 'NOT EXISTS',
                    ],
                ],
            ],
            $query
        );
    }

    /**
     * @ticket 62014
     */
    public function test_build_query_vars_from_query_block_post_format()
    {
        $this->registry->register(
            'core/example',
            ['uses_context' => ['query']]
        );

        $parsed_blocks = parse_blocks('<!-- wp:example {"ok":true} -->a<!-- wp:example /-->b<!-- /wp:example -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [
            'query' => [
                'postType' => 'post',
                'format'   => ['aside'],
            ],
        ];
        $block         = new WP_Block($parsed_block, $context, $this->registry);
        $query         = build_query_vars_from_query_block($block, 1);

        $this->assertSame(
            [
                'post_type'    => 'post',
                'order'        => 'DESC',
                'orderby'      => 'date',
                'post__not_in' => [],
                'tax_query'    => [
                    'relation' => 'OR',
                    [
                        'taxonomy' => 'post_format',
                        'field'    => 'slug',
                        'terms'    => ['post-format-aside'],
                        'operator' => 'IN',
                    ],
                ],
            ],
            $query
        );
    }
    /**
     * @ticket 62014
     */
    public function test_build_query_vars_from_query_block_post_formats_with_category()
    {
        $this->registry->register(
            'core/example',
            ['uses_context' => ['query']]
        );

        $parsed_blocks = parse_blocks('<!-- wp:example {"ok":true} -->a<!-- wp:example /-->b<!-- /wp:example -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [
            'query' => [
                'postType'    => 'post',
                'format'      => ['standard'],
                'categoryIds' => [56],
            ],
        ];
        $block         = new WP_Block($parsed_block, $context, $this->registry);
        $query         = build_query_vars_from_query_block($block, 1);

        $this->assertSame(
            [
                'post_type'    => 'post',
                'order'        => 'DESC',
                'orderby'      => 'date',
                'post__not_in' => [],
                'tax_query'    => [
                    'relation' => 'AND',
                    [
                        [
                            'taxonomy'         => 'category',
                            'terms'            => [56],
                            'include_children' => false,
                        ],
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'taxonomy' => 'post_format',
                            'field'    => 'slug',
                            'operator' => 'NOT EXISTS',
                        ],
                    ],
                ],
            ],
            $query
        );
    }

    /**
     * @ticket 52991
     */
    public function test_build_query_vars_from_query_block_no_context()
    {
        $this->registry->register('core/example', []);

        $parsed_blocks    = parse_blocks('<!-- wp:example {"ok":true} -->a<!-- wp:example /-->b<!-- /wp:example -->');
        $parsed_block     = $parsed_blocks[0];
        $block_no_context = new WP_Block($parsed_block, [], $this->registry);
        $query            = build_query_vars_from_query_block($block_no_context, 1);

        $this->assertSame(
            [
                'post_type'    => 'post',
                'order'        => 'DESC',
                'orderby'      => 'date',
                'post__not_in' => [],
                'tax_query'    => [],
            ],
            $query
        );
    }

    /**
     * @ticket 52991
     */
    public function test_build_query_vars_from_query_block_first_page()
    {
        $this->registry->register(
            'core/example',
            ['uses_context' => ['query']]
        );

        $parsed_blocks = parse_blocks('<!-- wp:example {"ok":true} -->a<!-- wp:example /-->b<!-- /wp:example -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [
            'query' => [
                'perPage' => 2,
                'offset'  => 0,
            ],
        ];
        $block         = new WP_Block($parsed_block, $context, $this->registry);
        $query         = build_query_vars_from_query_block($block, 1);

        $this->assertSame(
            [
                'post_type'      => 'post',
                'order'          => 'DESC',
                'orderby'        => 'date',
                'post__not_in'   => [],
                'tax_query'      => [],
                'offset'         => 0,
                'posts_per_page' => 2,
            ],
            $query
        );
    }

    /**
     * @ticket 52991
     */
    public function test_build_query_vars_from_query_block_page_no_offset()
    {
        $this->registry->register(
            'core/example',
            ['uses_context' => ['query']]
        );

        $parsed_blocks = parse_blocks('<!-- wp:example {"ok":true} -->a<!-- wp:example /-->b<!-- /wp:example -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [
            'query' => [
                'perPage' => 5,
                'offset'  => 0,
            ],
        ];
        $block         = new WP_Block($parsed_block, $context, $this->registry);
        $query         = build_query_vars_from_query_block($block, 3);
        $this->assertSame(
            [
                'post_type'      => 'post',
                'order'          => 'DESC',
                'orderby'        => 'date',
                'post__not_in'   => [],
                'tax_query'      => [],
                'offset'         => 10,
                'posts_per_page' => 5,
            ],
            $query
        );
    }

    /**
     * @ticket 52991
     */
    public function test_build_query_vars_from_query_block_page_with_offset()
    {
        $this->registry->register(
            'core/example',
            ['uses_context' => ['query']]
        );

        $parsed_blocks = parse_blocks('<!-- wp:example {"ok":true} -->a<!-- wp:example /-->b<!-- /wp:example -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [
            'query' => [
                'perPage' => 5,
                'offset'  => 2,
            ],
        ];
        $block         = new WP_Block($parsed_block, $context, $this->registry);
        $query         = build_query_vars_from_query_block($block, 3);
        $this->assertSame(
            [
                'post_type'      => 'post',
                'order'          => 'DESC',
                'orderby'        => 'date',
                'post__not_in'   => [],
                'tax_query'      => [],
                'offset'         => 12,
                'posts_per_page' => 5,
            ],
            $query
        );
    }

    /**
     * @ticket 56467
     */
    public function test_query_loop_block_query_vars_filter()
    {
        $this->registry->register(
            'core/example',
            ['uses_context' => ['query']]
        );

        $parsed_blocks = parse_blocks('<!-- wp:example {"ok":true} -->a<!-- wp:example /-->b<!-- /wp:example -->');
        $parsed_block  = $parsed_blocks[0];
        $context       = [
            'query' => [
                'postType' => 'page',
                'orderBy'  => 'title',
            ],
        ];
        $block         = new WP_Block($parsed_block, $context, $this->registry);

        add_filter(
            'query_loop_block_query_vars',
            static function ($query, $block, $page) {
                $query['post_type'] = 'book';
                return $query;
            },
            10,
            3
        );

        $query = build_query_vars_from_query_block($block, 1);
        $this->assertSame(
            [
                'post_type'    => 'book',
                'order'        => 'DESC',
                'orderby'      => 'title',
                'post__not_in' => [],
                'tax_query'    => [],
            ],
            $query
        );
    }

    /**
     * @ticket 52991
     */
    public function test_block_has_support()
    {
        $this->registry->register(
            'core/example',
            [
                'supports' => [
                    'align'    => ['wide', 'full'],
                    'fontSize' => true,
                    'color'    => [
                        'link'     => true,
                        'gradient' => false,
                    ],
                ],
            ]
        );
        $block_type    = $this->registry->get_registered('core/example');
        $align_support = block_has_support($block_type, ['align']);
        $this->assertTrue($align_support);
        $gradient_support = block_has_support($block_type, ['color', 'gradient']);
        $this->assertFalse($gradient_support);
        $link_support = block_has_support($block_type, ['color', 'link'], false);
        $this->assertTrue($link_support);
        $text_support = block_has_support($block_type, ['color', 'text']);
        $this->assertFalse($text_support);
        $font_nested = block_has_support($block_type, ['fontSize', 'nested']);
        $this->assertFalse($font_nested);
    }

    /**
     * @ticket 52991
     */
    public function test_block_has_support_no_supports()
    {
        $this->registry->register('core/example', []);
        $block_type  = $this->registry->get_registered('core/example');
        $has_support = block_has_support($block_type, ['color']);
        $this->assertFalse($has_support);
    }

    /**
     * @ticket 52991
     */
    public function test_block_has_support_provided_defaults()
    {
        $this->registry->register(
            'core/example',
            [
                'supports' => [
                    'color' => [
                        'gradient' => false,
                    ],
                ],
            ]
        );
        $block_type    = $this->registry->get_registered('core/example');
        $align_support = block_has_support($block_type, ['align'], true);
        $this->assertTrue($align_support);
        $gradient_support = block_has_support($block_type, ['color', 'gradient'], true);
        $this->assertFalse($gradient_support);
    }

    /**
     * @ticket 58532
     *
     * @dataProvider data_block_has_support_string
     *
     * @param array  $block_data Block data.
     * @param string $support    Support string to check.
     * @param bool   $expected   Expected result.
     */
    public function test_block_has_support_string($block_data, $support, $expected, $message)
    {
        $this->registry->register('core/example', $block_data);
        $block_type  = $this->registry->get_registered('core/example');
        $has_support = block_has_support($block_type, $support);
        $this->assertSame($expected, $has_support, $message);
    }

    /**
     * Data provider for test_block_has_support_string
     */
    public function data_block_has_support_string()
    {
        return [
            [
                [],
                'color',
                false,
                'Block with empty support array.',
            ],
            [
                [
                    'supports' => [
                        'align'    => ['wide', 'full'],
                        'fontSize' => true,
                        'color'    => [
                            'link'     => true,
                            'gradient' => false,
                        ],
                    ],
                ],
                'align',
                true,
                'Feature present in support array.',
            ],
            [
                [
                    'supports' => [
                        'align'    => ['wide', 'full'],
                        'fontSize' => true,
                        'color'    => [
                            'link'     => true,
                            'gradient' => false,
                        ],
                    ],
                ],
                'anchor',
                false,
                'Feature not present in support array.',
            ],
            [
                [
                    'supports' => [
                        'align'    => ['wide', 'full'],
                        'fontSize' => true,
                        'color'    => [
                            'link'     => true,
                            'gradient' => false,
                        ],
                    ],
                ],
                ['align'],
                true,
                'Feature present in support array, single element array.',
            ],
        ];
    }

    /**
     * @ticket 51612
     */
    public function test_block_filters_for_inner_blocks()
    {
        $pre_render_callback           = new MockAction();
        $render_block_data_callback    = new MockAction();
        $render_block_context_callback = new MockAction();

        $this->registry->register(
            'core/outer',
            [
                'render_callback' => static function ($block_attributes, $content) {
                    return $content;
                },
            ]
        );

        $this->registry->register(
            'core/inner',
            [
                'render_callback' => static function () {
                    return 'b';
                },
            ]
        );

        $parsed_blocks = parse_blocks('<!-- wp:outer -->a<!-- wp:inner /-->c<!-- /wp:outer -->');
        $parsed_block  = $parsed_blocks[0];

        add_filter('pre_render_block', [$pre_render_callback, 'filter']);
        add_filter('render_block_data', [$render_block_data_callback, 'filter']);
        add_filter('render_block_context', [$render_block_context_callback, 'filter']);

        render_block($parsed_block);

        $this->assertSame(2, $pre_render_callback->get_call_count());
        $this->assertSame(2, $render_block_data_callback->get_call_count());
        $this->assertSame(2, $render_block_context_callback->get_call_count());
    }
}
