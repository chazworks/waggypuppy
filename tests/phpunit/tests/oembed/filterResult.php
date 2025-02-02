<?php

/**
 * @group oembed
 */
class Tests_Filter_oEmbed_Result extends WP_UnitTestCase
{
    public function test_filter_oembed_result_trusted_malicious_iframe()
    {
        $html = '<p></p><iframe onload="alert(1)"></iframe>';

        $actual = wp_filter_oembed_result($html, (object)['type' => 'rich'],
            'https://www.youtube.com/watch?v=72xdCU__XCk');

        $this->assertSame($html, $actual);
    }

    public function test_filter_oembed_result_with_untrusted_provider()
    {
        $html = '<p></p><iframe onload="alert(1)" src="http://example.com/sample-page/"></iframe>';
        $actual = wp_filter_oembed_result($html, (object)['type' => 'rich'], 'http://example.com/sample-page/');

        $matches = [];
        preg_match('|src=".*#\?secret=([\w\d]+)" data-secret="([\w\d]+)"|', $actual, $matches);

        $this->assertArrayHasKey(1, $matches);
        $this->assertArrayHasKey(2, $matches);
        $this->assertSame($matches[1], $matches[2]);
    }

    public function test_filter_oembed_result_only_one_iframe_is_allowed()
    {
        $html = '<div><iframe></iframe><iframe></iframe><p></p></div>';
        $actual = wp_filter_oembed_result($html, (object)['type' => 'rich'], '');

        $this->assertSame('<iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted"></iframe>',
            $actual);
    }

    public function test_filter_oembed_result_with_newlines()
    {
        $html = <<<EOD
            <script>var = 1;</script>
            <iframe></iframe>
            <iframe></iframe>
            <p></p>
            EOD;

        $actual = wp_filter_oembed_result($html, (object)['type' => 'rich'], '');

        $this->assertSame('<iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted"></iframe>',
            $actual);
    }

    public function test_filter_oembed_result_without_iframe()
    {
        $html = '<span>Hello</span><p>World</p>';
        $actual = wp_filter_oembed_result($html, (object)['type' => 'rich'], '');

        $this->assertFalse($actual);

        $html = '<div><p></p></div><script></script>';
        $actual = wp_filter_oembed_result($html, (object)['type' => 'rich'], '');

        $this->assertFalse($actual);
    }

    public function test_filter_oembed_result_secret_param_available()
    {
        $html = '<iframe src="https://wp.org"></iframe>';
        $actual = wp_filter_oembed_result($html, (object)['type' => 'rich'], '');

        $matches = [];
        preg_match('|src="https://wp.org#\?secret=([\w\d]+)" data-secret="([\w\d]+)"|', $actual, $matches);

        $this->assertArrayHasKey(1, $matches);
        $this->assertArrayHasKey(2, $matches);
        $this->assertSame($matches[1], $matches[2]);
    }

    public function test_filter_oembed_result_wrong_type_provided()
    {
        $actual = wp_filter_oembed_result('some string', (object)['type' => 'link'], '');

        $this->assertSame('some string', $actual);
    }

    public function test_filter_oembed_result_invalid_result()
    {
        $this->assertFalse(wp_filter_oembed_result(false, (object)['type' => 'rich'], ''));
        $this->assertFalse(wp_filter_oembed_result('', (object)['type' => 'rich'], ''));
    }

    public function test_filter_oembed_result_blockquote_adds_style_to_iframe()
    {
        $html = '<blockquote></blockquote><iframe></iframe>';
        $actual = wp_filter_oembed_result($html, (object)['type' => 'rich'], '');

        $this->assertSame('<blockquote class="wp-embedded-content"></blockquote><iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted" style="position: absolute; visibility: hidden;"></iframe>',
            $actual);
    }

    public function test_filter_oembed_result_allowed_html()
    {
        $html = '<blockquote class="foo" id="bar"><strong><a href="" target=""></a></strong></blockquote><iframe></iframe>';
        $actual = wp_filter_oembed_result($html, (object)['type' => 'rich'], '');

        $this->assertSame('<blockquote class="wp-embedded-content"><a href=""></a></blockquote><iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted" style="position: absolute; visibility: hidden;"></iframe>',
            $actual);
    }

    public function data_wp_filter_pre_oembed_custom_result()
    {
        return [
            [
                '<blockquote></blockquote><iframe title=""></iframe>',
                '<blockquote class="wp-embedded-content"></blockquote><iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted" style="position: absolute; visibility: hidden;" title="Hola"></iframe>',
            ],
            [
                '<blockquote class="foo" id="bar"><strong><a href="" target=""></a></strong></blockquote><iframe width=123></iframe>',
                '<blockquote class="wp-embedded-content"><a href=""></a></blockquote><iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted" style="position: absolute; visibility: hidden;" title="Hola" width="123"></iframe>',
            ],
            [
                '<blockquote><iframe width="100"></iframe></blockquote><iframe stitle="aaaa"></iframe>',
                '<blockquote class="wp-embedded-content"><iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted" style="position: absolute; visibility: hidden;" title="Hola" width="100"></iframe></blockquote><iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted" style="position: absolute; visibility: hidden;" title="Hola"></iframe>',
            ],
            [
                "<blockquote><iframe title=' width=\"'></iframe></blockquote><iframe title='' height=' title=' width=\"'' height='123'\"></iframe>",
                '<blockquote class="wp-embedded-content"><iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted" style="position: absolute; visibility: hidden;" title=" width=&quot;"></iframe></blockquote><iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted" style="position: absolute; visibility: hidden;" title=" width=&quot;" height=\' title=\' width="\'\' height=\'123\'"></iframe>',
            ],
        ];
    }

    /**
     * @dataProvider data_wp_filter_pre_oembed_custom_result
     */
    public function test_wp_filter_pre_oembed_custom_result($html, $expected)
    {
        $data = (object)[
            'type' => 'rich',
            'title' => 'Hola',
            'html' => $html,
        ];
        $actual = _wp_oembed_get_object()->data2html($data, 'https://untrusted.localhost');
        $this->assertSame($expected, $actual);
    }

    /**
     * @group feed
     */
    public function test_filter_feed_content()
    {
        $html = '<blockquote></blockquote><iframe></iframe>';
        $actual = _oembed_filter_feed_content(wp_filter_oembed_result($html, (object)['type' => 'rich'], ''));

        $this->assertSame('<blockquote class="wp-embedded-content"></blockquote><iframe class="wp-embedded-content" sandbox="allow-scripts" security="restricted" ></iframe>',
            $actual);
    }
}
