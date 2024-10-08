<?php

require_once __DIR__ . '/testcase-adjacent-image-link.php';

/**
 * @group media
 * @covers ::get_adjacent_image_link
 */
class Tests_Media_GetAdjacentImageLink extends WP_Test_Adjacent_Image_Link_TestCase
{
    protected $default_args = [
        'prev' => true,
        'size' => 'thumbnail',
        'text' => false,
    ];

    /**
     * @ticket 45708
     *
     * @dataProvider data_get_adjacent_image_link
     */
    public function test_get_adjacent_image_link($current_attachment_index, $expected_attachment_index, $expected, array $args = [])
    {
        [$expected, $args] = $this->setup_test_scenario($current_attachment_index, $expected_attachment_index, $expected, $args);

        $actual = get_adjacent_image_link(...$args);

        $this->assertSame($expected, $actual);
    }

    public function data_get_adjacent_image_link()
    {
        return [
            // Happy paths.
            'when has previous link'           => [
                'current_attachment_index'  => 3,
                'expected_attachment_index' => 2,
                'expected'                  => '<a href=\'http://' . WP_TESTS_DOMAIN . '/?attachment_id=%%ID%%\'><img width="1" height="1" src="' . WP_CONTENT_URL . '/uploads/image2.jpg" class="attachment-thumbnail size-thumbnail" alt="" decoding="async" loading="lazy" /></a>',
            ],
            'with text when has previous link' => [
                'current_attachment_index'  => 3,
                'expected_attachment_index' => 2,
                'expected'                  => '<a href=\'http://' . WP_TESTS_DOMAIN . '/?attachment_id=%%ID%%\'>Some text</a>',
                'args'                      => ['text' => 'Some text'],
            ],
            'when has next link'               => [
                'current_attachment_index'  => 4,
                'expected_attachment_index' => 5,
                'expected'                  => '<a href=\'http://' . WP_TESTS_DOMAIN . '/?attachment_id=%%ID%%\'><img width="1" height="1" src="' . WP_CONTENT_URL . '/uploads/image5.jpg" class="attachment-thumbnail size-thumbnail" alt="" decoding="async" loading="lazy" /></a>',
                'args'                      => ['prev' => false],
            ],
            'with text when has next link'     => [
                'current_attachment_index'  => 4,
                'expected_attachment_index' => 5,
                'expected'                  => '<a href=\'http://' . WP_TESTS_DOMAIN . '/?attachment_id=%%ID%%\'>Some text</a>',
                'args'                      => [
                    'prev' => false,
                    'text' => 'Some text',
                ],
            ],

            // Unhappy paths.
            'when no previous link'            => [
                'current_attachment_index'  => 1,
                'expected_attachment_index' => 0,
                'expected'                  => '',
            ],
            'with text when no previous link'  => [
                'current_attachment_index'  => 1,
                'expected_attachment_index' => 0,
                'expected'                  => '',
                'args'                      => ['text' => 'Some text'],
            ],
            'when no next link'                => [
                'current_attachment_index'  => 5,
                'expected_attachment_index' => 0,
                'expected'                  => '',
                'args'                      => ['prev' => false],
            ],
            'with text when no next link'      => [
                'current_attachment_index'  => 5,
                'expected_attachment_index' => 0,
                'expected'                  => '',
                'args'                      => [
                    'prev' => false,
                    'text' => 'Some text',
                ],
            ],
        ];
    }
}
