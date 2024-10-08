<?php

/**
 * @group formatting
 *
 * @covers ::balanceTags
 */
class Tests_Formatting_BalanceTags extends WP_UnitTestCase
{

    /**
     * @ticket 47014
     * @dataProvider data_supported_traditional_tag_names
     */
    public function test_detects_traditional_tag_names($tag)
    {
        $normalized = strtolower($tag);

        $this->assertSame("<$normalized>inside</$normalized>", balanceTags("<$tag>inside", true));
    }

    public function data_supported_traditional_tag_names()
    {
        return [
            ['a'],
            ['div'],
            ['blockquote'],
            // HTML tag names can be CAPITALIZED and are case-insensitive.
            ['A'],
            ['dIv'],
            ['BLOCKQUOTE'],
        ];
    }

    /**
     * @ticket 47014
     * @dataProvider data_supported_custom_element_tag_names
     */
    public function test_detects_supported_custom_element_tag_names($tag)
    {
        $this->assertSame("<$tag>inside</$tag>", balanceTags("<$tag>inside", true));
    }

    public function data_supported_custom_element_tag_names()
    {
        return [
            ['custom-element'],
            ['my-custom-element'],
            ['weekday-5-item'],
            ['a-big-old-tag-name'],
            ['with_underscores-and_the_dash'],
            ['a-.'],
            ['a._-.-_'],
        ];
    }

    /**
     * @ticket 47014
     * @dataProvider data_invalid_tag_names
     */
    public function test_ignores_invalid_tag_names($input, $output)
    {
        $this->assertSame($output, balanceTags($input, true));
    }

    public function data_invalid_tag_names()
    {
        return [
            ['<0-day>inside', '&lt;0-day>inside'], // Can't start with a number - handled by the "<3" fix.
            ['<UPPERCASE-TAG>inside', '<UPPERCASE-TAG>inside'], // Custom elements cannot be uppercase.
        ];
    }

    /**
     * @ticket 47014
     * @dataProvider data_unsupported_valid_tag_names
     */
    public function test_ignores_unsupported_custom_tag_names($tag)
    {
        $this->assertSame("<$tag>inside", balanceTags("<$tag>inside", true));
    }

    /**
     * These are valid custom elements but we don't support them yet.
     *
     * @see https://html.spec.whatwg.org/multipage/custom-elements.html#valid-custom-element-name
     */
    public function data_unsupported_valid_tag_names()
    {
        return [
            // We don't allow ending in a dash.
            ['<what->inside'],
            // Examples from the spec working document.
            ['math-α'],
            ['emotion-😍'],
            // Unicode ranges.
            // 0x00b7
            ['b-·'],
            // Latin characters with accents/modifiers.
            // 0x00c0-0x00d6
            // 0x00d8-0x00f6
            ['a-À-Ó-Ý'],
            // 0x00f8-0x037d
            ['a-ͳ'],
            // No 0x037e, which is a Greek semicolon.
            // 0x037f-0x1fff
            ['a-Ფ'],
            // Zero-width characters, probably never supported.
            // 0x200c-0x200d
            ['a-‌to-my-left-is-a-zero-width-non-joiner-do-not-delete-it'],
            ['a-‍to-my-left-is-a-zero-width-joiner-do-not-delete-it'],
            // Ties.
            // 0x203f-0x2040
            ['under-‿-tie'],
            ['over-⁀-tie'],
            // 0x2170-0x218f
            ['a-⁰'],
            ['a-⅀'],
            ['tag-ↀ-it'],
            // 0x2c00-0x2fef
            ['a-Ⰰ'],
            ['b-ⴓ-c'],
            ['d-⽗'],
            // 0x3001-0xd7ff
            ['a-、'],
            ['z-态'],
            ['a-送-䠺-ퟱ-퟿'],
            // 0xf900-0xfdcf
            ['a-豈'],
            ['my-切'],
            ['aﴀ-tag'],
            ['my-﷌'],
            // 0xfdf0-0xfffd
            ['a-ﷰ'],
            ['a-￰-￸-�'], // Warning; blank characters are in there.
            // Extended ranges.
            // 0x10000-0xeffff
            ['a-𐀀'],
            ['my-𝀀'],
            ['a𞀀-𜿐'],
        ];
    }

    /**
     * @ticket 47014
     * @dataProvider data_supported_invalid_tag_names
     */
    public function test_detects_supported_invalid_tag_names($tag)
    {
        $this->assertSame("<$tag>inside</$tag>", balanceTags("<$tag>inside", true));
    }

    /**
     * These are invalid custom elements but we support them right now in order to keep the parser simpler.
     *
     * @see https://w3c.github.io/webcomponents/spec/custom/#valid-custom-element-name
     */
    public function data_supported_invalid_tag_names()
    {
        return [
            // Reserved names for custom elements.
            ['annotation-xml'],
            ['color-profile'],
            ['font-face'],
            ['font-face-src'],
            ['font-face-uri'],
            ['font-face-format'],
            ['font-face-name'],
            ['missing-glyph'],
        ];
    }

    /**
     * If a recognized valid single tag appears unclosed, it should get self-closed
     *
     * @ticket 1597
     * @dataProvider data_single_tags
     */
    public function test_selfcloses_unclosed_known_single_tags($tag)
    {
        $this->assertSame("<$tag />", balanceTags("<$tag>", true));
    }

    /**
     * If a recognized valid single tag is given a closing tag, the closing tag
     *   should get removed and tag should be self-closed.
     *
     * @ticket 1597
     * @dataProvider data_single_tags
     */
    public function test_selfcloses_known_single_tags_having_closing_tag($tag)
    {
        $this->assertSame("<$tag />", balanceTags("<$tag></$tag>", true));
    }

    // This is a complete(?) listing of valid single/self-closing tags.
    public function data_single_tags()
    {
        return [
            ['area'],
            ['base'],
            ['basefont'],
            ['br'],
            ['col'],
            ['command'],
            ['embed'],
            ['frame'],
            ['hr'],
            ['img'],
            ['input'],
            ['isindex'],
            ['link'],
            ['meta'],
            ['param'],
            ['source'],
            ['track'],
            ['wbr'],
        ];
    }

    /**
     * @ticket 1597
     */
    public function test_closes_unknown_single_tags_with_closing_tag()
    {

        $inputs   = [
            '<strong/>',
            '<em />',
            '<p class="main1"/>',
            '<p class="main2" />',
            '<STRONG/>',
        ];
        $expected = [
            '<strong></strong>',
            '<em></em>',
            '<p class="main1"></p>',
            '<p class="main2"></p>',
            // Valid tags are transformed to lowercase.
            '<strong></strong>',
        ];

        foreach ($inputs as $key => $input) {
            $this->assertSame($expected[$key], balanceTags($inputs[$key], true));
        }
    }

    public function test_closes_unclosed_single_tags_having_attributes()
    {
        $inputs   = [
            '<img src="/images/example.png">',
            '<input type="text" name="example">',
        ];
        $expected = [
            '<img src="/images/example.png"/>',
            '<input type="text" name="example"/>',
        ];

        foreach ($inputs as $key => $input) {
            $this->assertSame($expected[$key], balanceTags($inputs[$key], true));
        }
    }

    public function test_allows_validly_closed_single_tags()
    {
        $inputs = [
            '<br />',
            '<hr />',
            '<img src="/images/example.png" />',
            '<input type="text" name="example" />',
        ];

        foreach ($inputs as $key => $input) {
            $this->assertSame($inputs[$key], balanceTags($inputs[$key], true));
        }
    }

    /**
     * @dataProvider data_nestable_tags
     */
    public function test_balances_nestable_tags($tag)
    {
        $inputs   = [
            "<$tag>Test<$tag>Test</$tag>",
            "<$tag><$tag>Test",
            "<$tag>Test</$tag></$tag>",
        ];
        $expected = [
            "<$tag>Test<$tag>Test</$tag></$tag>",
            "<$tag><$tag>Test</$tag></$tag>",
            "<$tag>Test</$tag>",
        ];

        foreach ($inputs as $key => $input) {
            $this->assertSame($expected[$key], balanceTags($inputs[$key], true));
        }
    }

    public function data_nestable_tags()
    {
        return [
            ['article'],
            ['aside'],
            ['blockquote'],
            ['details'],
            ['div'],
            ['figure'],
            ['object'],
            ['q'],
            ['section'],
            ['span'],
        ];
    }

    public function test_allows_adjacent_nestable_tags()
    {
        $inputs = [
            '<blockquote><blockquote>Example quote</blockquote></blockquote>',
            '<div class="container"><div>This is allowed></div></div>',
            '<span><span><span>Example in spans</span></span></span>',
            '<blockquote>Main quote<blockquote>Example quote</blockquote> more text</blockquote>',
            '<q><q class="inner-q">Inline quote</q></q>',
        ];

        foreach ($inputs as $key => $input) {
            $this->assertSame($inputs[$key], balanceTags($inputs[$key], true));
        }
    }

    /**
     * @ticket 20401
     */
    public function test_allows_immediately_nested_object_tags()
    {
        $object = '<object id="obj1"><param name="param1"/><object id="obj2"><param name="param2"/></object></object>';
        $this->assertSame($object, balanceTags($object, true));
    }

    public function test_balances_nested_non_nestable_tags()
    {
        $inputs   = [
            '<b><b>This is bold</b></b>',
            '<b>Some text here <b>This is bold</b></b>',
        ];
        $expected = [
            '<b></b><b>This is bold</b>',
            '<b>Some text here </b><b>This is bold</b>',
        ];

        foreach ($inputs as $key => $input) {
            $this->assertSame($expected[$key], balanceTags($inputs[$key], true));
        }
    }

    public function test_fixes_improper_closing_tag_sequence()
    {
        $inputs   = [
            '<p>Here is a <strong class="part">bold <em>and emphasis</p></em></strong>',
            '<ul><li>Aaa</li><li>Bbb</ul></li>',
        ];
        $expected = [
            '<p>Here is a <strong class="part">bold <em>and emphasis</em></strong></p>',
            '<ul><li>Aaa</li><li>Bbb</li></ul>',
        ];

        foreach ($inputs as $key => $input) {
            $this->assertSame($expected[$key], balanceTags($inputs[$key], true));
        }
    }

    public function test_adds_missing_closing_tags()
    {
        $inputs   = [
            '<b><i>Test</b>',
            '<p>Test',
            '<p>Test test</em> test</p>',
            '</p>Test',
            '<p>We are <strong class="wp">#WordPressStrong</p>',
        ];
        $expected = [
            '<b><i>Test</i></b>',
            '<p>Test</p>',
            '<p>Test test test</p>',
            'Test',
            '<p>We are <strong class="wp">#WordPressStrong</strong></p>',
        ];

        foreach ($inputs as $key => $input) {
            $this->assertSame($expected[$key], balanceTags($inputs[$key], true));
        }
    }

    public function test_removes_extraneous_closing_tags()
    {
        $inputs   = [
            '<b>Test</b></b>',
            '<div>Test</div></div><div>Test',
            '<p>Test test</em> test</p>',
            '</p>Test',
        ];
        $expected = [
            '<b>Test</b>',
            '<div>Test</div><div>Test</div>',
            '<p>Test test test</p>',
            'Test',
        ];

        foreach ($inputs as $key => $input) {
            $this->assertSame($expected[$key], balanceTags($inputs[$key], true));
        }
    }

    /**
     * Get custom element data.
     *
     * @return array Data.
     */
    public function data_custom_elements()
    {
        return [
            // Valid custom element tags.
            [
                '<my-custom-element data-attribute="value"/>',
                '<my-custom-element data-attribute="value"></my-custom-element>',
            ],
            [
                '<my-custom-element>Test</my-custom-element>',
                '<my-custom-element>Test</my-custom-element>',
            ],
            [
                '<my-custom-element>Test',
                '<my-custom-element>Test</my-custom-element>',
            ],
            [
                'Test</my-custom-element>',
                'Test',
            ],
            [
                '</my-custom-element>Test',
                'Test',
            ],
            [
                '<my-custom-element/>',
                '<my-custom-element></my-custom-element>',
            ],
            [
                '<my-custom-element />',
                '<my-custom-element></my-custom-element>',
            ],
            // Invalid (or at least temporarily unsupported) custom element tags.
            [
                '<MY-CUSTOM-ELEMENT>Test',
                '<MY-CUSTOM-ELEMENT>Test',
            ],
            [
                '<my->Test',
                '<my->Test',
            ],
            [
                '<--->Test',
                '<--->Test',
            ],
        ];
    }

    /**
     * Test custom elements.
     *
     * @ticket 47014
     * @dataProvider data_custom_elements
     *
     * @param string $source   Source.
     * @param string $expected Expected.
     */
    public function test_custom_elements($source, $expected)
    {
        $this->assertSame($expected, balanceTags($source, true));
    }
}
