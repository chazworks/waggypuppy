<?php

/**
 * @group formatting
 *
 * @covers ::esc_xml
 */
class Tests_Formatting_EscXml extends WP_UnitTestCase
{
    /**
     * Test basic escaping
     *
     * @dataProvider data_esc_xml_basics
     *
     * @param string $source The source string to be escaped.
     * @param string $expected The expected escaped value of `$source`.
     */
    public function test_esc_xml_basics($source, $expected)
    {
        $actual = esc_xml($source);
        $this->assertSame($expected, $actual);
    }

    /**
     * Data provider for `test_esc_xml_basics()`.
     *
     * @return array {
     * @type string $source The source string to be escaped.
     * @type string $expected The expected escaped value of `$source`.
     * }
     */
    public function data_esc_xml_basics()
    {
        return [
            // Simple string.
            [
                'The quick brown fox.',
                'The quick brown fox.',
            ],
            // URL with &.
            [
                'http://localhost/trunk/wp-login.php?action=logout&_wpnonce=cd57d75985',
                'http://localhost/trunk/wp-login.php?action=logout&amp;_wpnonce=cd57d75985',
            ],
            // SQL query w/ single quotes.
            [
                "SELECT meta_key, meta_value FROM wp_trunk_sitemeta WHERE meta_key IN ('site_name', 'siteurl', 'active_sitewide_plugins', '_site_transient_timeout_theme_roots', '_site_transient_theme_roots', 'site_admins', 'can_compress_scripts', 'global_terms_enabled') AND site_id = 1",
                'SELECT meta_key, meta_value FROM wp_trunk_sitemeta WHERE meta_key IN (&apos;site_name&apos;, &apos;siteurl&apos;, &apos;active_sitewide_plugins&apos;, &apos;_site_transient_timeout_theme_roots&apos;, &apos;_site_transient_theme_roots&apos;, &apos;site_admins&apos;, &apos;can_compress_scripts&apos;, &apos;global_terms_enabled&apos;) AND site_id = 1',
            ],
            // Zero string.
            [
                '0',
                '0',
            ],
        ];
    }

    public function test_escapes_ampersands()
    {
        $source = 'penn & teller & at&t';
        $expected = 'penn &amp; teller &amp; at&amp;t';
        $actual = esc_xml($source);
        $this->assertSame($expected, $actual);
    }

    public function test_escapes_greater_and_less_than()
    {
        $source = 'this > that < that <randomhtml />';
        $expected = 'this &gt; that &lt; that &lt;randomhtml /&gt;';
        $actual = esc_xml($source);
        $this->assertSame($expected, $actual);
    }

    public function test_escapes_html_named_entities()
    {
        $source = 'this &amp; is a &hellip; followed by &rsaquo; and more and a &nonexistent; entity';
        $expected = 'this &amp; is a … followed by › and more and a &amp;nonexistent; entity';
        $actual = esc_xml($source);
        $this->assertSame($expected, $actual);
    }

    public function test_ignores_existing_entities()
    {
        $source = '&#038; &#x00A3; &#x22; &amp;';
        // note that _wp_specialchars() strips leading 0's from numeric character references.
        $expected = '&#038; &#xA3; &#x22; &amp;';
        $actual = esc_xml($source);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test that CDATA Sections are not escaped.
     *
     * @dataProvider data_ignores_cdata_sections
     *
     * @param string $source The source string to be escaped.
     * @param string $expected The expected escaped value of `$source`.
     */
    public function test_ignores_cdata_sections($source, $expected)
    {
        $actual = esc_xml($source);
        $this->assertSame($expected, $actual);
    }

    /**
     * Data provider for `test_ignores_cdata_sections()`.
     *
     * @return array {
     * @type string $source The source string to be escaped.
     * @type string $expected The expected escaped value of `$source`.
     * }
     */
    public function data_ignores_cdata_sections()
    {
        return [
            // basic CDATA Section containing chars that would otherwise be escaped if not in a CDATA Section
            // not to mention the CDATA Section markup itself :-)
            // $source contains embedded newlines to test that the regex that ignores CDATA Sections
            // correctly handles that case.
            [
                "This is\na<![CDATA[test of\nthe <emergency>]]>\nbroadcast system",
                "This is\na<![CDATA[test of\nthe <emergency>]]>\nbroadcast system",
            ],
            // string with chars that should be escaped as well as a CDATA Section that should be not be.
            [
                'This is &hellip; a <![CDATA[test of the <emergency>]]> broadcast <system />',
                'This is … a <![CDATA[test of the <emergency>]]> broadcast &lt;system /&gt;',
            ],
            // Same as above, but with the CDATA Section at the start of the string.
            [
                '<![CDATA[test of the <emergency>]]> This is &hellip; a broadcast <system />',
                '<![CDATA[test of the <emergency>]]> This is … a broadcast &lt;system /&gt;',
            ],
            // Same as above, but with the CDATA Section at the end of the string.
            [
                'This is &hellip; a broadcast <system /><![CDATA[test of the <emergency>]]>',
                'This is … a broadcast &lt;system /&gt;<![CDATA[test of the <emergency>]]>',
            ],
            // Multiple CDATA Sections.
            [
                'This is &hellip; a <![CDATA[test of the <emergency>]]> &broadcast; <![CDATA[<system />]]>',
                'This is … a <![CDATA[test of the <emergency>]]> &amp;broadcast; <![CDATA[<system />]]>',
            ],
            // Ensure that ']]>' that does not mark the end of a CDATA Section is escaped.
            [
                '<![CDATA[<&]]>]]>',
                '<![CDATA[<&]]>]]&gt;',
            ],
        ];
    }
}
