<?php

/**
 * Test cases for the `wp_privacy_generate_personal_data_export_group_html()` function.
 *
 * @package WP
 * @subpackage UnitTests
 * @since 5.2.0
 *
 * @group privacy
 * @covers ::wp_privacy_generate_personal_data_export_group_html
 */
class Tests_Privacy_wpPrivacyGeneratePersonalDataExportGroupHtml extends WP_UnitTestCase
{

    /**
     * Test when a single data item is passed.
     *
     * @ticket 44044
     */
    public function test_group_html_generation_single_data_item()
    {
        $data = [
            'group_label' => 'Test Data Group',
            'items' => [
                [
                    [
                        'name' => 'Field 1 Name',
                        'value' => 'Field 1 Value',
                    ],
                    [
                        'name' => 'Field 2 Name',
                        'value' => 'Field 2 Value',
                    ],
                ],
            ],
        ];

        $actual = wp_privacy_generate_personal_data_export_group_html($data, 'test-data-group', 2);
        $expected_table_markup = '<table><tbody><tr><th>Field 1 Name</th><td>Field 1 Value</td></tr><tr><th>Field 2 Name</th><td>Field 2 Value</td></tr></tbody></table>';

        $this->assertStringContainsString('<h2 id="test-data-group-test-data-group">Test Data Group</h2>', $actual);
        $this->assertStringContainsString($expected_table_markup, $actual);
    }

    /**
     * Test when a multiple data items are passed.
     *
     * @ticket 44044
     * @ticket 46895
     */
    public function test_group_html_generation_multiple_data_items()
    {
        $data = [
            'group_label' => 'Test Data Group',
            'items' => [
                [
                    [
                        'name' => 'Field 1 Name',
                        'value' => 'Field 1 Value',
                    ],
                    [
                        'name' => 'Field 2 Name',
                        'value' => 'Field 2 Value',
                    ],
                ],
                [
                    [
                        'name' => 'Field 1 Name',
                        'value' => 'Another Field 1 Value',
                    ],
                    [
                        'name' => 'Field 2 Name',
                        'value' => 'Another Field 2 Value',
                    ],
                ],
            ],
        ];

        $actual = wp_privacy_generate_personal_data_export_group_html($data, 'test-data-group', 2);

        // Updated to remove </h2> from test to avoid Count introducing failure (ticket #46895).
        $this->assertStringContainsString('<h2 id="test-data-group-test-data-group">Test Data Group', $actual);
        $this->assertStringContainsString('<td>Field 1 Value', $actual);
        $this->assertStringContainsString('<td>Another Field 1 Value', $actual);
        $this->assertStringContainsString('<td>Field 2 Value', $actual);
        $this->assertStringContainsString('<td>Another Field 2 Value', $actual);
        $this->assertSame(2, substr_count($actual, '<th>Field 1 Name'));
        $this->assertSame(2, substr_count($actual, '<th>Field 2 Name'));
        $this->assertSame(4, substr_count($actual, '<tr>'));
    }

    /**
     * Values that appear to be links should be wrapped in `<a>` tags.
     *
     * @ticket 44044
     */
    public function test_links_become_anchors()
    {
        $data = [
            'group_label' => 'Test Data Group',
            'items' => [
                [
                    [
                        'name' => 'HTTP Link',
                        'value' => 'http://wp.org',
                    ],
                    [
                        'name' => 'HTTPS Link',
                        'value' => 'https://wp.org',
                    ],
                    [
                        'name' => 'Link with Spaces',
                        'value' => 'https://wp.org not a link.',
                    ],
                ],
            ],
        ];

        $actual = wp_privacy_generate_personal_data_export_group_html($data, 'test-data-group', 2);

        $this->assertStringContainsString('<a href="http://wp.org">http://wp.org</a>', $actual);
        $this->assertStringContainsString('<a href="https://wp.org">https://wp.org</a>', $actual);
        $this->assertStringContainsString('https://wp.org not a link.', $actual);
    }

    /**
     * HTML in group labels should be escaped.
     *
     * @ticket 44044
     */
    public function test_group_labels_escaped()
    {
        $data = [
            'group_label' => '<div>Escape HTML in group labels</div>',
            'items' => [],
        ];

        $actual = wp_privacy_generate_personal_data_export_group_html($data, 'escape-html-in-group-labels', 2);

        $this->assertStringContainsString('<h2 id="escape-html-in-group-labels-escape-html-in-group-labels">&lt;div&gt;Escape HTML in group labels&lt;/div&gt;</h2>',
            $actual);
    }

    /**
     * Test that the exported data should contain allowed HTML.
     *
     * @ticket 44044
     */
    public function test_allowed_html_not_stripped()
    {
        $data = [
            'group_label' => 'Test Data Group',
            'items' => [
                [
                    'links' => [
                        'name' => 'Links are allowed',
                        'value' => '<a href="http://wp.org">http://wp.org</a>',
                    ],
                    'formatting' => [
                        'name' => 'Simple formatting is allowed',
                        'value' => '<b>bold</b>, <em>emphasis</em>, <i>italics</i>, and <strong>strong</strong> are allowed.',
                    ],
                ],
            ],
        ];

        $actual = wp_privacy_generate_personal_data_export_group_html($data, 'test-data-group', 2);
        $this->assertStringContainsString($data['items'][0]['links']['value'], $actual);
        $this->assertStringContainsString($data['items'][0]['formatting']['value'], $actual);
    }

    /**
     * Test that the exported data should not contain disallowed HTML.
     *
     * @ticket 44044
     */
    public function test_disallowed_html_is_stripped()
    {
        $data = [
            'group_label' => 'Test Data Group',
            'items' => [
                [
                    'scripts' => [
                        'name' => 'Script tags are not allowed.',
                        'value' => '<script>Testing that script tags are stripped.</script>',
                    ],
                    'images' => [
                        'name' => 'Images are not allowed',
                        'value' => '<img src="https://example.com/logo.jpg" alt="Alt text" />',
                    ],
                ],
            ],
        ];

        $actual = wp_privacy_generate_personal_data_export_group_html($data, 'test-data-group', 2);

        $this->assertStringNotContainsString($data['items'][0]['scripts']['value'], $actual);
        $this->assertStringContainsString('<td>Testing that script tags are stripped.</td>', $actual);

        $this->assertStringNotContainsString($data['items'][0]['images']['value'], $actual);
        $this->assertStringContainsString('<th>Images are not allowed</th><td></td>', $actual);
    }

    /**
     * Test group count is displayed for multiple items.
     *
     * @ticket 46895
     */
    public function test_group_html_generation_should_display_group_count_when_multiple_items()
    {
        $data = [
            'group_label' => 'Test Data Group',
            'items' => [
                [
                    [
                        'name' => 'Field 1 Name',
                        'value' => 'Field 1 Value',
                    ],
                ],
                [
                    [
                        'name' => 'Field 2 Name',
                        'value' => 'Field 2 Value',
                    ],
                ],
            ],
        ];

        $actual = wp_privacy_generate_personal_data_export_group_html($data, 'test-data-group', 2);

        $this->assertStringContainsString('<h2 id="test-data-group-test-data-group">Test Data Group', $actual);
        $this->assertStringContainsString('<span class="count">(2)</span></h2>', $actual);
        $this->assertSame(2, substr_count($actual, '<table>'));
    }

    /**
     * Test group count is not displayed for a single item.
     *
     * @ticket 46895
     */
    public function test_group_html_generation_should_not_display_group_count_when_single_item()
    {
        $data = [
            'group_label' => 'Test Data Group',
            'items' => [
                [
                    [
                        'name' => 'Field 1 Name',
                        'value' => 'Field 1 Value',
                    ],
                ],
            ],
        ];

        $actual = wp_privacy_generate_personal_data_export_group_html($data, 'test-data-group', 2);

        $this->assertStringContainsString('<h2 id="test-data-group-test-data-group">Test Data Group</h2>', $actual);
        $this->assertStringNotContainsString('<span class="count">', $actual);
        $this->assertSame(1, substr_count($actual, '<table>'));
    }
}
