<?php
/**
 * Tests the Style Engine CSS declarations class.
 *
 * @package WP
 * @subpackage StyleEngine
 * @since 6.1.0
 *
 * @group style-engine
 */

/**
 * Tests registering, storing and generating CSS declarations.
 *
 * @coversDefaultClass WP_Style_Engine_CSS_Declarations
 */
class Tests_Style_Engine_wpStyleEngineCSSDeclarations extends WP_UnitTestCase
{
    /**
     * Tests setting declarations on instantiation.
     *
     * @ticket 56467
     *
     * @covers ::__construct
     */
    public function test_should_set_declarations_on_instantiation()
    {
        $input_declarations = [
            'margin-top' => '10px',
            'font-size' => '2rem',
        ];
        $css_declarations = new WP_Style_Engine_CSS_Declarations($input_declarations);

        $this->assertSame($input_declarations, $css_declarations->get_declarations());
    }

    /**
     * Tests that declarations are added.
     *
     * @ticket 56467
     *
     * @covers ::add_declarations
     * @covers ::add_declaration
     */
    public function test_should_add_declarations()
    {
        $input_declarations = [
            'padding' => '20px',
            'color' => 'var(--wp--preset--elbow-patches)',
        ];
        $css_declarations = new WP_Style_Engine_CSS_Declarations();
        $css_declarations->add_declarations($input_declarations);

        $this->assertSame($input_declarations, $css_declarations->get_declarations());
    }

    /**
     * Tests that new declarations are added to existing declarations.
     *
     * @ticket 56467
     *
     * @covers ::add_declarations
     * @covers ::add_declaration
     */
    public function test_should_add_new_declarations_to_existing()
    {
        $input_declarations = [
            'border-width' => '1%',
            'background-color' => 'var(--wp--preset--english-mustard)',
        ];
        $css_declarations = new WP_Style_Engine_CSS_Declarations($input_declarations);
        $extra_declaration = [
            'letter-spacing' => '1.5px',
        ];
        $css_declarations->add_declarations($extra_declaration);

        $this->assertSame(array_merge($input_declarations, $extra_declaration), $css_declarations->get_declarations());
    }

    /**
     * Tests that properties are sanitized before storing.
     *
     * @ticket 56467
     *
     * @covers ::sanitize_property
     */
    public function test_should_sanitize_properties()
    {
        $input_declarations = [
            '^--wp--style--sleepy-potato$' => '40px',
            '<background-//color>' => 'var(--wp--preset--english-mustard)',
        ];
        $css_declarations = new WP_Style_Engine_CSS_Declarations($input_declarations);

        $this->assertSame(
            [
                '--wp--style--sleepy-potato' => '40px',
                'background-color' => 'var(--wp--preset--english-mustard)',
            ],
            $css_declarations->get_declarations(),
        );
    }

    /**
     * Test that values with HTML tags are escaped, and CSS properties are run through safecss_filter_attr().
     *
     * @ticket 56467
     *
     * @covers ::get_declarations_string
     * @covers ::filter_declaration
     */
    public function test_should_strip_html_tags_and_remove_unsafe_css_properties()
    {
        $input_declarations = [
            'font-size' => '<red/>',
            'padding' => '</style>',
            'potato' => 'uppercase',
            'cheese' => '10px',
            'margin-right' => '10em',
        ];
        $css_declarations = new WP_Style_Engine_CSS_Declarations($input_declarations);
        $safe_style_css_mock_action = new MockAction();

        // filter_declaration() is called in get_declarations_string().
        add_filter('safe_style_css', [$safe_style_css_mock_action, 'filter']);
        $css_declarations_string = $css_declarations->get_declarations_string();

        $this->assertSame(
            3, // Values with HTML tags are removed first by wp_strip_all_tags().
            $safe_style_css_mock_action->get_call_count(),
            '"safe_style_css" filters were not applied to CSS declaration properties.',
        );

        $this->assertSame(
            'margin-right:10em;',
            $css_declarations_string,
            'Unallowed CSS properties or values with HTML tags were not removed.',
        );
    }

    /**
     * Tests that calc, clamp, min, max, and minmax CSS functions are allowed.
     *
     * @ticket 56467
     *
     * @covers ::get_declarations_string
     * @covers ::filter_declaration
     */
    public function test_should_allow_css_functions_and_strip_unsafe_css_values()
    {
        $input_declarations = [
            'background' => 'var(--wp--preset--color--primary, 10px)', // Simple var().
            'font-size' => 'clamp(36.00rem, calc(32.00rem + 10.00vw), 40.00rem)', // Nested clamp().
            'width' => 'min(150vw, 100px)',
            'min-width' => 'max(150vw, 100px)',
            'max-width' => 'minmax(400px, 50%)',
            'padding' => 'calc(80px * -1)',
            'background-image' => 'url("https://wp.org")',
            'line-height' => 'url("https://wp.org")',
            'margin' => 'illegalfunction(30px)',
        ];
        $css_declarations = new WP_Style_Engine_CSS_Declarations($input_declarations);
        $safecss_filter_attr_allow_css_mock_action = new MockAction();

        // filter_declaration() is called in get_declarations_string().
        add_filter('safecss_filter_attr_allow_css', [$safecss_filter_attr_allow_css_mock_action, 'filter']);
        $css_declarations_string = $css_declarations->get_declarations_string();

        $this->assertSame(
            9,
            $safecss_filter_attr_allow_css_mock_action->get_call_count(),
            '"safecss_filter_attr_allow_css" filters were not applied to CSS declaration values.',
        );

        $this->assertSame(
            'background:var(--wp--preset--color--primary, 10px);font-size:clamp(36.00rem, calc(32.00rem + 10.00vw), 40.00rem);width:min(150vw, 100px);min-width:max(150vw, 100px);max-width:minmax(400px, 50%);padding:calc(80px * -1);background-image:url("https://wp.org");',
            $css_declarations_string,
            'Unsafe values were not removed',
        );
    }

    /**
     * Tests that CSS declarations are compiled into a CSS declarations block string.
     *
     * @ticket 56467
     *
     * @covers ::get_declarations_string
     *
     * @dataProvider data_should_compile_css_declarations_to_css_declarations_string
     *
     * @param string $expected The expected declarations block string.
     * @param bool $should_prettify Optional. Whether to pretty the string. Default false.
     * @param int $indent_count Optional. The number of tab indents. Default false.
     */
    public function test_should_compile_css_declarations_to_css_declarations_string(
        $expected,
        $should_prettify = false,
        $indent_count = 0,
    ) {
        $input_declarations = [
            'color' => 'red',
            'border-top-left-radius' => '99px',
            'text-decoration' => 'underline',
        ];
        $css_declarations = new WP_Style_Engine_CSS_Declarations($input_declarations);

        $this->assertSame(
            $expected,
            $css_declarations->get_declarations_string($should_prettify, $indent_count),
        );
    }

    /**
     * Data provider for test_should_compile_css_declarations_to_css_declarations_string().
     *
     * @return array
     */
    public function data_should_compile_css_declarations_to_css_declarations_string()
    {
        return [
            'unprettified, no indent' => [
                'expected' => 'color:red;border-top-left-radius:99px;text-decoration:underline;',
            ],
            'unprettified, one indent' => [
                'expected' => 'color:red;border-top-left-radius:99px;text-decoration:underline;',
                'should_prettify' => false,
                'indent_count' => 1,
            ],
            'prettified, no indent' => [
                'expected' => 'color: red; border-top-left-radius: 99px; text-decoration: underline;',
                'should_prettify' => true,
            ],
            'prettified, one indent' => [
                'expected' => "\tcolor: red;\n\tborder-top-left-radius: 99px;\n\ttext-decoration: underline;",
                'should_prettify' => true,
                'indent_count' => 1,
            ],
            'prettified, two indents' => [
                'expected' => "\t\tcolor: red;\n\t\tborder-top-left-radius: 99px;\n\t\ttext-decoration: underline;",
                'should_prettify' => true,
                'indent_count' => 2,
            ],
        ];
    }

    /**
     * Tests removing a single declaration.
     *
     * @ticket 56467
     *
     * @covers ::remove_declaration
     */
    public function test_should_remove_single_declaration()
    {
        $input_declarations = [
            'color' => 'tomato',
            'margin' => '10em 10em 20em 1px',
            'font-family' => 'Happy Font serif',
        ];
        $css_declarations = new WP_Style_Engine_CSS_Declarations($input_declarations);

        $this->assertSame(
            'color:tomato;margin:10em 10em 20em 1px;font-family:Happy Font serif;',
            $css_declarations->get_declarations_string(),
            'CSS declarations string does not match the values of `$declarations` passed to the constructor.',
        );

        $css_declarations->remove_declaration('color');

        $this->assertSame(
            'margin:10em 10em 20em 1px;font-family:Happy Font serif;',
            $css_declarations->get_declarations_string(),
            'Output after removing "color" declaration via `remove_declaration()` does not match expectations',
        );
    }

    /**
     * Tests that multiple declarations are removed.
     *
     * @ticket 56467
     *
     * @covers ::remove_declarations
     */
    public function test_should_remove_multiple_declarations()
    {
        $input_declarations = [
            'color' => 'cucumber',
            'margin' => '10em 10em 20em 1px',
            'font-family' => 'Happy Font serif',
        ];
        $css_declarations = new WP_Style_Engine_CSS_Declarations($input_declarations);

        $this->assertSame(
            'color:cucumber;margin:10em 10em 20em 1px;font-family:Happy Font serif;',
            $css_declarations->get_declarations_string(),
            'CSS declarations string does not match the values of `$declarations` passed to the constructor.',
        );

        $css_declarations->remove_declarations(['color', 'margin']);

        $this->assertSame(
            'font-family:Happy Font serif;',
            $css_declarations->get_declarations_string(),
            'Output after removing "color" and "margin" declarations via `remove_declarations()` does not match expectations',
        );
    }
}
