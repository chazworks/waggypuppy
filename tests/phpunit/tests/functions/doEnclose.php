<?php

/**
 * Test cases for the `do_enclose()` function.
 *
 * @package WP\UnitTests
 *
 * @since 5.3.0
 *
 * @group functions
 * @group post
 *
 * @covers ::do_enclose
 */
class Tests_Functions_DoEnclose extends WP_UnitTestCase
{

    /**
     * Setup before each test method.
     *
     * @since 5.3.0
     */
    public function set_up()
    {
        parent::set_up();
        add_filter('pre_http_request', [$this, 'mock_http_request'], 10, 3);
    }

    /**
     * Tests the function with an explicit content input.
     *
     * @since 5.3.0
     *
     * @dataProvider data_do_enclose
     */
    public function test_function_with_explicit_content_input($content, $expected)
    {
        $post_id = self::factory()->post->create();

        do_enclose($content, $post_id);

        $actual = $this->get_enclosed_by_post_id($post_id);
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests the function with an implicit content input.
     *
     * @since 5.3.0
     *
     * @dataProvider data_do_enclose
     */
    public function test_function_with_implicit_content_input($content, $expected)
    {
        $post_id = self::factory()->post->create(
            [
                'post_content' => $content,
            ],
        );

        do_enclose(null, $post_id);

        $actual = $this->get_enclosed_by_post_id($post_id);
        $this->assertSame($expected, $actual);
    }

    /**
     * Data provider for `test_function_with_explicit_content_input()`
     * and `test_function_with_implicit_content_input()`.
     *
     * @return array {
     * @type array {
     * @type string Post content.
     * @type string Expected values.
     *     }
     * }
     * @since 5.3.0
     *
     */
    public function data_do_enclose()
    {
        return [
            'null' => [
                'content' => null,
                'expected' => '',
            ],
            'empty' => [
                'content' => '',
                'expected' => '',
            ],
            'single-bare-movie' => [
                'content' => 'movie.mp4',
                'expected' => '',
            ],
            'single-bare-audio' => [
                'content' => 'audio.ogg',
                'expected' => '',
            ],
            'single-relative-movie' => [
                'content' => '/movie.mp4',
                'expected' => "/movie.mp4\n123\nvideo/mp4\n",
            ],
            'single-relative-audio' => [
                'content' => '/audio.ogg',
                'expected' => "/audio.ogg\n321\naudio/ogg\n",
            ],
            'single-unknown' => [
                'content' => 'https://example.com/wp-content/uploads/2018/06/file.unknown',
                'expected' => '',
            ],
            'single-movie' => [
                'content' => 'https://example.com/wp-content/uploads/2018/06/movie.mp4',
                'expected' => "https://example.com/wp-content/uploads/2018/06/movie.mp4\n123\nvideo/mp4\n",
            ],
            'single-audio' => [
                'content' => 'https://example.com/wp-content/uploads/2018/06/audio.ogg',
                'expected' => "https://example.com/wp-content/uploads/2018/06/audio.ogg\n321\naudio/ogg\n",
            ],
            'single-movie-query' => [
                'content' => 'https://example.com/wp-content/uploads/2018/06/movie.mp4?test=1',
                'expected' => "https://example.com/wp-content/uploads/2018/06/movie.mp4?test=1\n123\nvideo/mp4\n",
            ],
            'multi' => [
                'content' => "https://example.com/wp-content/uploads/2018/06/audio.ogg\n" .
                    'https://example.com/wp-content/uploads/2018/06/movie.mp4',
                'expected' => "https://example.com/wp-content/uploads/2018/06/audio.ogg\n321\naudio/ogg\n" .
                    "https://example.com/wp-content/uploads/2018/06/movie.mp4\n123\nvideo/mp4\n",
            ],
            'no-path' => [
                'content' => 'https://example.com?test=1',
                'expected' => '',
            ],
        ];
    }

    /**
     * The function should return false when the post ID input is invalid.
     *
     * @since 5.3.0
     */
    public function test_function_should_return_false_when_invalid_post_id()
    {
        $post_id = null;
        $result = do_enclose(null, $post_id);
        $this->assertFalse($result);
    }

    /**
     * The function should delete an enclosed link when it's no longer in the post content.
     *
     * @since 5.3.0
     */
    public function test_function_should_delete_enclosed_link_when_no_longer_in_post_content()
    {
        $data = $this->data_do_enclose();

        // Create a post with a single movie link.
        $post_id = self::factory()->post->create(
            [
                'post_content' => $data['single-movie']['content'],
            ],
        );

        do_enclose(null, $post_id);

        $actual = $this->get_enclosed_by_post_id($post_id);
        $this->assertSame($data['single-movie']['expected'], $actual);

        // Replace the movie link with an audio link.
        wp_update_post(
            [
                'ID' => $post_id,
                'post_content' => $data['single-audio']['content'],
            ],
        );

        do_enclose(null, $post_id);

        $actual = $this->get_enclosed_by_post_id($post_id);
        $this->assertSame($data['single-audio']['expected'], $actual);
    }

    /**
     * The function should support a post object input.
     *
     * @since 5.3.0
     */
    public function test_function_should_support_post_object_input()
    {
        $data = $this->data_do_enclose();

        $post_object = self::factory()->post->create_and_get(
            [
                'post_content' => $data['multi']['content'],
            ],
        );

        do_enclose(null, $post_object);

        $actual = $this->get_enclosed_by_post_id($post_object->ID);
        $this->assertSame($data['multi']['expected'], $actual);
    }

    /**
     * The enclosure links should be filterable with the `enclosure_links` filter.
     *
     * @since 5.3.0
     */
    public function test_function_enclosure_links_should_be_filterable()
    {
        $data = $this->data_do_enclose();

        $post_id = self::factory()->post->create(
            [
                'post_content' => $data['multi']['content'],
            ],
        );

        add_filter('enclosure_links', [$this, 'filter_enclosure_links'], 10, 2);
        do_enclose(null, $post_id);
        remove_filter('enclosure_links', [$this, 'filter_enclosure_links']);

        $actual = $this->get_enclosed_by_post_id($post_id);
        $expected = str_replace('example.org', sprintf('example-%d.org', $post_id), $data['multi']['expected']);
        $this->assertSame($expected, $actual);
    }

    /**
     * A callback to filter the list of enclosure links.
     *
     * @param array $post_links An array of enclosure links.
     * @param int $post_id Post ID.
     * @return array An array of enclosure links.
     * @since 5.3.0
     *
     */
    public function filter_enclosure_links($enclosure_links, $post_id)
    {
        // Replace the link host to contain the post ID, to test both filter input arguments.
        foreach ($enclosure_links as &$link) {
            $link = str_replace('example.org', sprintf('example-%d.org', $post_id), $link);
        }
        return $enclosure_links;
    }

    /**
     * Helper function to get all enclosure data for a given post.
     *
     * @param int $post_id Post ID.
     * @return string  All enclosure data for the given post.
     * @since 5.3.0
     *
     */
    protected function get_enclosed_by_post_id($post_id)
    {
        return implode('', (array)get_post_meta($post_id, 'enclosure', false));
    }

    /**
     * Mock the HTTP request response.
     *
     * @param false|array|WP_Error $response A preemptive return value of an HTTP request. Default false.
     * @param array $parsed_args HTTP request arguments.
     * @param string $url The request URL.
     * @return array Response data.
     * @since 5.3.0
     *
     */
    public function mock_http_request($response, $parsed_args, $url)
    {
        // Video and audio headers.
        $fake_headers = [
            'mp4' => [
                'headers' => [
                    'Content-Length' => 123,
                    'Content-Type' => 'video/mp4',
                ],
            ],
            'ogg' => [
                'headers' => [
                    'Content-Length' => 321,
                    'Content-Type' => 'audio/ogg',
                ],
            ],
        ];

        $path = parse_url($url, PHP_URL_PATH);

        if (is_string($path)) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if (isset($fake_headers[$extension])) {
                return $fake_headers[$extension];
            }
        }

        // Fallback header.
        return [
            'headers' => [
                'Content-Length' => 0,
                'Content-Type' => '',
            ],
        ];
    }
}
