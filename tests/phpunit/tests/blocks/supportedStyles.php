<?php

/**
 * Test block supported styles.
 *
 * @package WP
 * @subpackage Blocks
 * @since 5.6.0
 *
 * @group blocks
 */
class Tests_Blocks_SupportedStyles extends WP_UnitTestCase
{

    /**
     * Block content to test with (i.e. what's wrapped by the block wrapper `<div />`).
     *
     * @var string
     */
    const BLOCK_CONTENT = '
		<p data-image-description="&lt;p&gt;Test!&lt;/p&gt;">Test</p>
		<p>äöü</p>
		<p>ß</p>
		<p>系の家庭に</p>
		<p>Example &lt;p&gt;Test!&lt;/p&gt;</p>
	';

    /**
     * Registered block names.
     *
     * @var string[]
     */
    private $registered_block_names = [];

    /**
     * Tear down each test method.
     */
    public function tear_down()
    {
        while (!empty($this->registered_block_names)) {
            $block_name = array_pop($this->registered_block_names);
            unregister_block_type($block_name);
        }

        parent::tear_down();
    }

    /**
     * Registers a block type.
     *
     * @param string|WP_Block_Type $name Block type name including namespace, or alternatively a
     *                                   complete WP_Block_Type instance. In case a WP_Block_Type
     *                                   is provided, the $args parameter will be ignored.
     * @param array $args {
     *     Optional. Array of block type arguments. Any arguments may be defined, however the
     *     ones described below are supported by default. Default empty array.
     *
     * @type callable $render_callback Callback used to render blocks of this block type.
     * }
     */
    protected function register_block_type($name, $args)
    {
        register_block_type($name, $args);

        $this->registered_block_names[] = $name;
    }

    /**
     * Retrieves attribute such as 'class' or 'style' from the rendered block string.
     *
     * @param string $attribute Name of attribute to get.
     * @param string $block String of rendered block to check.
     */
    private function get_attribute_from_block($attribute, $block)
    {
        $start_index = strpos($block, $attribute . '="') + strlen($attribute) + 2;
        $split_arr = substr($block, $start_index);
        $end_index = strpos($split_arr, '"');
        return substr($split_arr, 0, $end_index);
    }

    /**
     * Retrieves block content from the rendered block string
     * (i.e. what's wrapped by the block wrapper `<div />`).
     *
     * @param string $block String of rendered block to check.
     */
    private function get_content_from_block($block)
    {
        $start_index = strpos($block, '>') + 1; // First occurrence of '>'.
        $split_arr = substr($block, $start_index);
        $end_index = strrpos($split_arr, '<'); // Last occurrence of '<'.
        return substr($split_arr, 0, $end_index); // String between first '>' and last '<'.
    }

    /**
     * Returns the rendered output for the current block.
     *
     * @param array $block Block to render.
     * @return string Rendered output for the current block.
     */
    private function render_example_block($block)
    {
        WP_Block_Supports::init();
        WP_Block_Supports::$block_to_render = $block;
        $wrapper_attributes = get_block_wrapper_attributes(
            [
                'class' => 'foo-bar-class',
                'style' => 'test: style;',
            ],
        );
        return '<div ' . $wrapper_attributes . '>' . self::BLOCK_CONTENT . '</div>';
    }

    /**
     * Runs assertions that the rendered output has expected class/style attrs.
     *
     * @param array $block Block to render.
     * @param string $expected_classes Expected output class attr string.
     * @param string $expected_styles Expected output styles attr string.
     */
    private function assert_styles_and_classes_match($block, $expected_classes, $expected_styles)
    {
        $styled_block = $this->render_example_block($block);
        $class_list = $this->get_attribute_from_block('class', $styled_block);
        $style_list = $this->get_attribute_from_block('style', $styled_block);

        $this->assertSame($expected_classes, $class_list, 'Class list does not match expected classes');
        $this->assertSame($expected_styles, $style_list, 'Style list does not match expected styles');
    }

    /**
     * Runs assertions that the rendered output has expected content and class/style attrs.
     *
     * @param array $block Block to render.
     * @param string $expected_classes Expected output class attr string.
     * @param string $expected_styles Expected output styles attr string.
     */
    private function assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles)
    {
        $styled_block = $this->render_example_block($block);

        // Ensure blocks to not add extra whitespace.
        $this->assertSame($styled_block, trim($styled_block));

        $content = $this->get_content_from_block($styled_block);
        $class_list = $this->get_attribute_from_block('class', $styled_block);
        $style_list = $this->get_attribute_from_block('style', $styled_block);

        $this->assertSame(self::BLOCK_CONTENT, $content, 'Block content does not match expected content');
        $this->assertSameSets(
            explode(' ', $expected_classes),
            explode(' ', $class_list),
            'Class list does not match expected classes',
        );
        $this->assertSame(
            array_map('trim', explode(';', $expected_styles)),
            array_map('trim', explode(';', $style_list)),
            'Style list does not match expected styles',
        );
    }

    /**
     * Tests color support for named color support for named colors.
     */
    public function test_named_color_support()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [
                'color' => true,
            ],
            'render_callback' => true,
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'textColor' => 'red',
                'backgroundColor' => 'black',
                // The following should not be applied (subcategories of color support).
                'gradient' => 'some-gradient',
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example has-text-color has-red-color has-background has-black-background-color';
        $expected_styles = 'test: style;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests color support for custom colors.
     */
    public function test_custom_color_support()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [
                'color' => true,
            ],
            'render_callback' => true,
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'style' => [
                    'color' => [
                        'text' => '#000',
                        'background' => '#fff',
                        // The following should not be applied (subcategories of color support).
                        'gradient' => 'some-gradient',
                        'style' => ['color' => ['link' => '#fff']],
                    ],
                ],
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_styles = 'test: style;color:#000;background-color:#fff;';
        $expected_classes = 'foo-bar-class wp-block-example has-text-color has-background';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests gradient color support for named gradients.
     */
    public function test_named_gradient_support()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [
                'color' => [
                    'gradients' => true,
                ],
            ],
            'render_callback' => true,
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'gradient' => 'red',
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example has-background has-red-gradient-background';
        $expected_styles = 'test: style;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests gradient color support for custom gradients.
     */
    public function test_custom_gradient_support()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [
                'color' => [
                    'gradients' => true,
                ],
            ],
            'render_callback' => true,
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'style' => ['color' => ['gradient' => 'some-gradient-style']],
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example has-background';
        $expected_styles = 'test: style; background:some-gradient-style;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests that style attributes for colors are not applied without the support flag.
     */
    public function test_color_unsupported()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [],
            'render_callback' => true,
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'textColor' => 'red',
                'backgroundColor' => 'black',
                'style' => [
                    'color' => [
                        'text' => '#000',
                        'background' => '#fff',
                        'link' => '#ggg',
                        'gradient' => 'some-gradient',
                    ],
                ],
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example';
        $expected_styles = 'test: style;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests support for named font sizes.
     */
    public function test_named_font_size()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [
                'typography' => [
                    'fontSize' => true,
                ],
            ],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'fontSize' => 'large',
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example has-large-font-size';
        $expected_styles = 'test: style;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests support for custom font sizes.
     */
    public function test_custom_font_size()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [
                'typography' => [
                    'fontSize' => true,
                ],
            ],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'style' => ['typography' => ['fontSize' => '10px']],
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example';
        $expected_styles = 'test: style; font-size:10px;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests that font size attributes are not applied without support flag.
     */
    public function test_font_size_unsupported()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'fontSize' => 'large',
                'style' => ['typography' => ['fontSize' => '10']],
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example';
        $expected_styles = 'test: style;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests line height support.
     */
    public function test_line_height()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [
                'typography' => [
                    'lineHeight' => true,
                ],
            ],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'style' => ['typography' => ['lineHeight' => '10']],
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example';
        $expected_styles = 'test: style; line-height:10;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests line height not applied without support flag.
     */
    public function test_line_height_unsupported()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'style' => ['typography' => ['lineHeight' => '10']],
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example';
        $expected_styles = 'test: style;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests support for block alignment.
     */
    public function test_block_alignment()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [
                'align' => true,
            ],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'align' => 'wide',
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example alignwide';
        $expected_styles = 'test: style;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests block alignment requires support to be added.
     */
    public function test_block_alignment_unsupported()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'align' => 'wide',
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example';
        $expected_styles = 'test: style;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests all support flags together to ensure they work together as expected.
     */
    public function test_all_supported()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [
                'color' => [
                    'gradients' => true,
                    'link' => true,
                ],
                'typography' => [
                    'fontSize' => true,
                    'lineHeight' => true,
                ],
                'align' => true,
            ],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'align' => 'wide',
                'style' => [
                    'color' => [
                        'text' => '#000',
                        'background' => '#fff',
                        'style' => ['color' => ['link' => '#fff']],
                    ],
                    'typography' => [
                        'lineHeight' => '20',
                        'fontSize' => '10px',
                    ],
                ],
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example has-text-color has-background alignwide';
        $expected_styles = 'test: style; color:#000; background-color:#fff; font-size:10px; line-height:20;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests that only styles for the supported flag are added.
     * Verify one support enabled does not imply multiple supports enabled.
     */
    public function test_one_supported()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [
                'typography' => [
                    'fontSize' => true,
                ],
            ],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'align' => 'wide',
                'style' => [
                    'color' => [
                        'text' => '#000',
                        'background' => '#fff',
                        'gradient' => 'some-gradient',
                        'style' => ['color' => ['link' => '#fff']],
                    ],
                    'typography' => [
                        'lineHeight' => '20',
                        'fontSize' => '10px',
                    ],
                ],
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_classes = 'foo-bar-class wp-block-example';
        $expected_styles = 'test: style; font-size:10px;';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests custom classname server-side block support.
     */
    public function test_custom_classnames_support()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'className' => 'my-custom-classname',
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_styles = 'test: style;';
        $expected_classes = 'foo-bar-class wp-block-example my-custom-classname';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests custom classname server-side block support opt-out.
     */
    public function test_custom_classnames_support_opt_out()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [
                'customClassName' => false,
            ],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [
                'className' => 'my-custom-classname',
            ],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_styles = 'test: style;';
        $expected_classes = 'foo-bar-class wp-block-example';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Tests generated classname server-side block support opt-out.
     */
    public function test_generated_classnames_support_opt_out()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [
                'className' => false,
            ],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];

        $expected_styles = 'test: style;';
        $expected_classes = 'foo-bar-class';

        $this->assert_content_and_styles_and_classes_match($block, $expected_classes, $expected_styles);
    }

    /**
     * Ensures libxml_internal_errors is being used instead of @ warning suppression
     */
    public function test_render_block_suppresses_warnings_without_at_suppression()
    {
        $block_type_settings = [
            'attributes' => [],
            'supports' => [],
        ];
        $this->register_block_type('core/example', $block_type_settings);

        $block = [
            'blockName' => 'core/example',
            'attrs' => [],
            'innerBlock' => [],
            'innerContent' => [],
            'innerHTML' => [],
        ];
        $wp_block = new WP_Block($block);

        // Custom error handler's see Warnings even if they are suppressed by the @ symbol.
        $errors = [];
        set_error_handler(
            static function ($errno = 0, $errstr = '') use (&$errors) {
                $errors[] = $errstr;
                return false;
            },
        );

        // HTML5 elements like <time> are not supported by the DOMDocument parser used by the block supports feature.
        // This specific example is emitted by the "Display post date" setting in the latest-posts block.
        apply_filters('render_block',
            '<div><time datetime="2020-06-18T04:01:43+10:00" class="wp-block-latest-posts__post-date">June 18, 2020</time></div>',
            $block, $wp_block);

        restore_error_handler();

        $this->assertEmpty($errors, 'Libxml errors should be dropped.');
    }
}
