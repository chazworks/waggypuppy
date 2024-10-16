<?php

/**
 * @group formatting
 *
 * @covers ::make_clickable
 */
class Tests_Formatting_MakeClickable extends WP_UnitTestCase
{
    public function test_mailto_xss()
    {
        $in = 'testzzz@"STYLE="behavior:url(\'#default#time2\')"onBegin="alert(\'refresh-XSS\')"';
        $this->assertSame($in, make_clickable($in));
    }

    /**
     * @dataProvider data_valid_mailto
     *
     * @param string $email Email to test.
     */
    public function test_valid_mailto($email)
    {
        $this->assertSame('<a href="mailto:' . $email . '">' . $email . '</a>', make_clickable($email));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function data_valid_mailto()
    {
        return [
            ['foo@example.com'],
            ['foo.bar@example.com'],
            ['Foo.Bar@a.b.c.d.example.com'],
            ['0@example.com'],
            ['foo@example-example.com'],
        ];
    }

    /**
     * @dataProvider data_invalid_mailto
     *
     * @param string $email Email to test.
     */
    public function test_invalid_mailto($email)
    {
        $this->assertSame($email, make_clickable($email));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function data_invalid_mailto()
    {
        return [
            ['foo'],
            ['foo@'],
            ['foo@@example.com'],
            ['@example.com'],
            ['foo @example.com'],
            ['foo@example'],
        ];
    }

    /**
     * @ticket 4570
     * @ticket 10990
     * @ticket 11211
     * @ticket 14993
     * @ticket 16892
     *
     * @dataProvider data_urls
     *
     * @param string $text Content to test.
     * @param string $expected Expected results.
     */
    public function test_urls($text, $expected)
    {
        $this->assertSame($expected, make_clickable($text));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function data_urls()
    {
        return [
            // Does not link trailing periods, commas, and (semi-)colons in URLs with protocol (i.e. http://wp.org).
            'URL only' => [
                'text' => 'http://wp.org/hello.html',
                'expected' => '<a href="http://wp.org/hello.html" rel="nofollow">http://wp.org/hello.html</a>',
            ],
            'URL. with more content after' => [
                'text' => 'There was a spoon named http://wp.org. Alice!',
                'expected' => 'There was a spoon named <a href="http://wp.org" rel="nofollow">http://wp.org</a>. Alice!',
            ],
            'URL, with more content after' => [
                'text' => 'There was a spoon named http://wp.org, said Alice.',
                'expected' => 'There was a spoon named <a href="http://wp.org" rel="nofollow">http://wp.org</a>, said Alice.',
            ],
            'URL; with more content after' => [
                'text' => 'There was a spoon named http://wp.org; said Alice.',
                'expected' => 'There was a spoon named <a href="http://wp.org" rel="nofollow">http://wp.org</a>; said Alice.',
            ],
            'URL: with more content after' => [
                'text' => 'There was a spoon named http://wp.org: said Alice.',
                'expected' => 'There was a spoon named <a href="http://wp.org" rel="nofollow">http://wp.org</a>: said Alice.',
            ],
            'URL) with more content after' => [
                'text' => 'There was a spoon named (http://wp.org) said Alice.',
                'expected' => 'There was a spoon named (<a href="http://wp.org" rel="nofollow">http://wp.org</a>) said Alice.',
            ],

            // Does not link trailing periods, commas, and (semi-)colons in URLs with protocol (i.e. http://wp.org) with nothing afterwards.
            'URL.' => [
                'text' => 'There was a spoon named http://wp.org.',
                'expected' => 'There was a spoon named <a href="http://wp.org" rel="nofollow">http://wp.org</a>.',
            ],
            'URL,' => [
                'text' => 'There was a spoon named http://wp.org,',
                'expected' => 'There was a spoon named <a href="http://wp.org" rel="nofollow">http://wp.org</a>,',
            ],
            'URL;' => [
                'text' => 'There was a spoon named http://wp.org;',
                'expected' => 'There was a spoon named <a href="http://wp.org" rel="nofollow">http://wp.org</a>;',
            ],
            'URL:' => [
                'text' => 'There was a spoon named http://wp.org:',
                'expected' => 'There was a spoon named <a href="http://wp.org" rel="nofollow">http://wp.org</a>:',
            ],
            'URL)' => [
                'text' => 'There was a spoon named (http://wp.org)',
                'expected' => 'There was a spoon named (<a href="http://wp.org" rel="nofollow">http://wp.org</a>)',
            ],
            'URL)x' => [
                'text' => 'There was a spoon named (http://wp.org)x',
                'expected' => 'There was a spoon named (<a href="http://wp.org" rel="nofollow">http://wp.org</a>)x',
            ],

            // Strip trailing without protocol: will not link trailing periods, commas, and (semi-)colons in URLs without protocol (i.e. www.wp.org).
            'No protocol www.URL. with content after' => [
                'text' => 'There was a spoon named www.wp.org. Alice!',
                'expected' => 'There was a spoon named <a href="http://www.wp.org" rel="nofollow">http://www.wp.org</a>. Alice!',
            ],
            'No protocol www.URL, with content after' => [
                'text' => 'There was a spoon named www.wp.org, said Alice.',
                'expected' => 'There was a spoon named <a href="http://www.wp.org" rel="nofollow">http://www.wp.org</a>, said Alice.',
            ],
            'No protocol www.URL; with content after' => [
                'text' => 'There was a spoon named www.wp.org; said Alice.',
                'expected' => 'There was a spoon named <a href="http://www.wp.org" rel="nofollow">http://www.wp.org</a>; said Alice.',
            ],
            'No protocol www.URL: with content after' => [
                'text' => 'There was a spoon named www.wp.org: said Alice.',
                'expected' => 'There was a spoon named <a href="http://www.wp.org" rel="nofollow">http://www.wp.org</a>: said Alice.',
            ],
            'No protocol www.URL) with content after' => [
                'text' => 'There was a spoon named www.wp.org) said Alice.',
                'expected' => 'There was a spoon named <a href="http://www.wp.org" rel="nofollow">http://www.wp.org</a>) said Alice.',
            ],

            // Should not link trailing periods, commas, and (semi-)colons in URLs without protocol (i.e. www.wp.org).
            'No protocol www.URL' => [
                'text' => 'www.wp.org',
                'expected' => '<a href="http://www.wp.org" rel="nofollow">http://www.wp.org</a>',
            ],
            'No protocol www.URL.' => [
                'text' => 'There was a spoon named www.wp.org.',
                'expected' => 'There was a spoon named <a href="http://www.wp.org" rel="nofollow">http://www.wp.org</a>.',
            ],
            'No protocol www.URL,' => [
                'text' => 'There was a spoon named www.wp.org,',
                'expected' => 'There was a spoon named <a href="http://www.wp.org" rel="nofollow">http://www.wp.org</a>,',
            ],
            'No protocol www.URL;' => [
                'text' => 'There was a spoon named www.wp.org;',
                'expected' => 'There was a spoon named <a href="http://www.wp.org" rel="nofollow">http://www.wp.org</a>;',
            ],
            'No protocol www.URL:' => [
                'text' => 'There was a spoon named www.wp.org:',
                'expected' => 'There was a spoon named <a href="http://www.wp.org" rel="nofollow">http://www.wp.org</a>:',
            ],
            'No protocol www.URL)' => [
                'text' => 'There was a spoon named www.wp.org)',
                'expected' => 'There was a spoon named <a href="http://www.wp.org" rel="nofollow">http://www.wp.org</a>)',
            ],

            // @ticket 4570
            // Test IRI.
            'IRI in domain' => [
                'text' => 'http://www.詹姆斯.com/',
                'expected' => '<a href="http://www.詹姆斯.com/" rel="nofollow">http://www.詹姆斯.com/</a>',
            ],
            'IRI in path' => [
                'text' => 'http://bg.wikipedia.org/Баба',
                'expected' => '<a href="http://bg.wikipedia.org/Баба" rel="nofollow">http://bg.wikipedia.org/Баба</a>',
            ],
            'IRI in query string' => [
                'text' => 'http://example.com/?a=баба&b=дядо',
                'expected' => '<a href="http://example.com/?a=баба&#038;b=дядо" rel="nofollow">http://example.com/?a=баба&#038;b=дядо</a>',
            ],

            // @ticket 10990
            // Test URLS with brackets (within the URL).
            'URL with brackets in path' => [
                'text' => 'http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)',
                'expected' => '<a href="http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)" rel="nofollow">http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)</a>',
            ],
            '(URL with brackets in path)' => [
                'text' => '(http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software))',
                'expected' => '(<a href="http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)" rel="nofollow">http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)</a>)',
            ],
            'URL with brackets in path: word before and after' => [
                'text' => 'blah http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software) blah',
                'expected' => 'blah <a href="http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)" rel="nofollow">http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)</a> blah',
            ],
            'URL with brackets in path: trailing ) blah' => [
                'text' => 'blah (http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)) blah',
                'expected' => 'blah (<a href="http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)" rel="nofollow">http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)</a>) blah',
            ],
            'URL with brackets in path: within content' => [
                'text' => 'blah blah blah http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software) blah blah',
                'expected' => 'blah blah blah <a href="http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)" rel="nofollow">http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)</a> blah blah',
            ],
            'URL with brackets in path: trailing ) within content' => [
                'text' => 'blah blah blah http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)) blah blah',
                'expected' => 'blah blah blah <a href="http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)" rel="nofollow">http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)</a>) blah blah',
            ],
            '(URL with brackets in path) within content' => [
                'text' => 'blah blah (http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)) blah blah',
                'expected' => 'blah blah (<a href="http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)" rel="nofollow">http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)</a>) blah blah',
            ],
            'URL with brackets in path: trailing .)' => [
                'text' => 'blah blah http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software).) blah blah',
                'expected' => 'blah blah <a href="http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)" rel="nofollow">http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)</a>.) blah blah',
            ],
            'URL with brackets in path: trailing .)moreurl' => [
                'text' => 'blah blah http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software).)moreurl blah blah',
                'expected' => 'blah blah <a href="http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)" rel="nofollow">http://en.wikipedia.org/wiki/PC_Tools_(Central_Point_Software)</a>.)moreurl blah blah',
            ],
            'multiline content with URL with brackets in path' => [
                'text' => 'In his famous speech “You and Your research” (here:
							   http://www.cs.virginia.edu/~robins/YouAndYourResearch.html)
							   Richard Hamming wrote about people getting more done with their doors closed, but',
                'expected' => 'In his famous speech “You and Your research” (here:
							   <a href="http://www.cs.virginia.edu/~robins/YouAndYourResearch.html" rel="nofollow">http://www.cs.virginia.edu/~robins/YouAndYourResearch.html</a>)
							   Richard Hamming wrote about people getting more done with their doors closed, but',
            ],

            // @ticket #62037
            'URL with brackets in path before the extension' => [
                'text' => 'http://example-image(2).jpg',
                'expected' => '<a href="http://example-image(2).jpg" rel="nofollow">http://example-image(2).jpg</a>',
            ],
            'URL with brackets within path and with a extension' => [
                'text' => 'http://example-(2)-image.jpg',
                'expected' => '<a href="http://example-(2)-image.jpg" rel="nofollow">http://example-(2)-image.jpg</a>',
            ],

            // @ticket 11211
            // Test with real comments which were incorrectly linked.
            'real world: example.com text (.org URL)' => [
                'text' => 'Example: waggypuppy, test (some text), I love example.com (http://example.org), it is brilliant',
                'expected' => 'Example: waggypuppy, test (some text), I love example.com (<a href="http://example.org">http://example.org</a>), it is brilliant',
            ],
            'real world: example.com text (.com URL)' => [
                'text' => 'Example: waggypuppy, test (some text), I love example.com (http://example.com), it is brilliant',
                'expected' => 'Example: waggypuppy, test (some text), I love example.com (<a href="http://example.com" rel="nofollow">http://example.com</a>), it is brilliant',
            ],
            'real world: (URL)...' => [
                'text' => 'Some text followed by a bracketed link with a trailing ellipsis (http://example.com)...',
                'expected' => 'Some text followed by a bracketed link with a trailing ellipsis (<a href="http://example.com" rel="nofollow">http://example.com</a>)...',
            ],
            'real world: (here: URL)' => [
                'text' => 'In his famous speech “You and Your research” (here: http://www.cs.virginia.edu/~robins/YouAndYourResearch.html) Richard Hamming wrote about people getting more done with their doors closed...',
                'expected' => 'In his famous speech “You and Your research” (here: <a href="http://www.cs.virginia.edu/~robins/YouAndYourResearch.html" rel="nofollow">http://www.cs.virginia.edu/~robins/YouAndYourResearch.html</a>) Richard Hamming wrote about people getting more done with their doors closed...',
            ],

            // @ticket 14993
            // Test Twitter hash bang URL.
            'Twitter hash bang URL' => [
                'text' => 'http://twitter.com/#!/wordpress/status/25907440233',
                'expected' => '<a href="http://twitter.com/#!/wordpress/status/25907440233" rel="nofollow">http://twitter.com/#!/wordpress/status/25907440233</a>',
            ],
            'Twitter hash bang URL in sentence' => [
                'text' => 'This is a really good tweet http://twitter.com/#!/wordpress/status/25907440233 !',
                'expected' => 'This is a really good tweet <a href="http://twitter.com/#!/wordpress/status/25907440233" rel="nofollow">http://twitter.com/#!/wordpress/status/25907440233</a> !',
            ],
            'Twitter hash bang in sentence with trailing !' => [
                'text' => 'This is a really good tweet http://twitter.com/#!/wordpress/status/25907440233!',
                'expected' => 'This is a really good tweet <a href="http://twitter.com/#!/wordpress/status/25907440233" rel="nofollow">http://twitter.com/#!/wordpress/status/25907440233</a>!',
            ],

            // Test URLs wrapped in angled brackets, i.e. < >.
            '<URL>' => [
                'text' => 'URL wrapped in angle brackets <http://example.com/>',
                'expected' => 'URL wrapped in angle brackets <<a href="http://example.com/" rel="nofollow">http://example.com/</a>>',
            ],
            '< URL >' => [
                'text' => 'URL wrapped in angle brackets with padding < http://example.com/ >',
                'expected' => 'URL wrapped in angle brackets with padding < <a href="http://example.com/" rel="nofollow">http://example.com/</a> >',
            ],
            '<email>' => [
                'text' => 'mailto wrapped in angle brackets <foo@example.com>',
                'expected' => 'mailto wrapped in angle brackets <foo@example.com>',
            ],

            // Test URLs preceded by punctuation.
            ',URL' => [
                'text' => 'Comma then URL,http://example.com/',
                'expected' => 'Comma then URL,<a href="http://example.com/" rel="nofollow">http://example.com/</a>',
            ],
            '.URL' => [
                'text' => 'Period then URL.http://example.com/',
                'expected' => 'Period then URL.<a href="http://example.com/" rel="nofollow">http://example.com/</a>',
            ],
            ';URL' => [
                'text' => 'Semi-colon then URL;http://example.com/',
                'expected' => 'Semi-colon then URL;<a href="http://example.com/" rel="nofollow">http://example.com/</a>',
            ],
            ':URL' => [
                'text' => 'Colon then URL:http://example.com/',
                'expected' => 'Colon then URL:<a href="http://example.com/" rel="nofollow">http://example.com/</a>',
            ],
            '!URL' => [
                'text' => 'Exclamation mark then URL!http://example.com/',
                'expected' => 'Exclamation mark then URL!<a href="http://example.com/" rel="nofollow">http://example.com/</a>',
            ],
            '?URL' => [
                'text' => 'Question mark then URL?http://example.com/',
                'expected' => 'Question mark then URL?<a href="http://example.com/" rel="nofollow">http://example.com/</a>',
            ],

            // Test it doesn't break tag attributes.
            '<img src=URL with attributes>' => [
                'text' => "<img src='http://trunk.domain/wp-includes/images/smilies/icon_smile.gif' alt=':)' class='wp-smiley'>",
                'expected' => "<img src='http://trunk.domain/wp-includes/images/smilies/icon_smile.gif' alt=':)' class='wp-smiley'>",
            ],
            '(<img src=URL with attributes>)' => [
                'text' => "(<img src='http://trunk.domain/wp-includes/images/smilies/icon_smile.gif' alt=':)' class='wp-smiley'>)",
                'expected' => "(<img src='http://trunk.domain/wp-includes/images/smilies/icon_smile.gif' alt=':)' class='wp-smiley'>)",
            ],
            'URL (<img src=URL with attributes>)' => [
                'text' => "http://trunk.domain/testing#something (<img src='http://trunk.domain/wp-includes/images/smilies/icon_smile.gif' alt=':)' class='wp-smiley'>)",
                'expected' => "<a href=\"http://trunk.domain/testing#something\" rel=\"nofollow\">http://trunk.domain/testing#something</a> (<img src='http://trunk.domain/wp-includes/images/smilies/icon_smile.gif' alt=':)' class='wp-smiley'>)",
            ],
            'multiline URL (<img src=URL with attributes>)' => [
                'text' => "http://trunk.domain/testing#something
						  (<img src='http://trunk.domain/wp-includes/images/smilies/icon_smile.gif' alt=':)' class='wp-smiley'>)",
                'expected' => "<a href=\"http://trunk.domain/testing#something\" rel=\"nofollow\">http://trunk.domain/testing#something</a>
						  (<img src='http://trunk.domain/wp-includes/images/smilies/icon_smile.gif' alt=':)' class='wp-smiley'>)",
            ],
            '<param value=URL><embed src=URL>' => [
                'text' => "<span style='text-align:center; display: block;'><object width='425' height='350'><param name='movie' value='https://www.youtube.com/watch?v=72xdCU__XCk&rel=1&fs=1&showsearch=0&showinfo=1&iv_load_policy=1' /> <param name='allowfullscreen' value='true' /> <param name='wmode' value='opaque' /> <embed src='https://www.youtube.com/watch?v=72xdCU__XCk&rel=1&fs=1&showsearch=0&showinfo=1&iv_load_policy=1' type='application/x-shockwave-flash' allowfullscreen='true' width='425' height='350' wmode='opaque'></embed> </object></span>",
                'expected' => "<span style='text-align:center; display: block;'><object width='425' height='350'><param name='movie' value='https://www.youtube.com/watch?v=72xdCU__XCk&rel=1&fs=1&showsearch=0&showinfo=1&iv_load_policy=1' /> <param name='allowfullscreen' value='true' /> <param name='wmode' value='opaque' /> <embed src='https://www.youtube.com/watch?v=72xdCU__XCk&rel=1&fs=1&showsearch=0&showinfo=1&iv_load_policy=1' type='application/x-shockwave-flash' allowfullscreen='true' width='425' height='350' wmode='opaque'></embed> </object></span>",
            ],
            '<a src=URL title=URL></a>' => [
                'text' => '<a href="http://example.com/example.gif" title="Image from http://example.com">Look at this image!</a>',
                'expected' => '<a href="http://example.com/example.gif" title="Image from http://example.com">Look at this image!</a>',
            ],

            // Test doesn't add links within <pre> or <code> elements.
            'Does not add link within <pre>' => [
                'text' => '<pre>http://wp.org</pre>',
                'expected' => '<pre>http://wp.org</pre>',
            ],
            'Does not add link within <code>' => [
                'text' => '<code>http://wp.org</code>',
                'expected' => '<code>http://wp.org</code>',
            ],
            'Does not add link within <pre with attributes>' => [
                'text' => '<pre class="foobar" id="foo">http://wp.org</pre>',
                'expected' => '<pre class="foobar" id="foo">http://wp.org</pre>',
            ],
            'Does not add link within <code with attributes>' => [
                'text' => '<code class="foobar" id="foo">http://wp.org</code>',
                'expected' => '<code class="foobar" id="foo">http://wp.org</code>',
            ],
            'Adds link within <precustomtag>' => [
                'text' => '<precustomtag>http://wp.org</precustomtag>',
                'expected' => '<precustomtag><a href="http://wp.org" rel="nofollow">http://wp.org</a></precustomtag>',
            ],
            'Adds link within <codecustomtag>' => [
                'text' => '<codecustomtag>http://wp.org</codecustomtag>',
                'expected' => '<codecustomtag><a href="http://wp.org" rel="nofollow">http://wp.org</a></codecustomtag>',
            ],
            'Adds link to URL before <pre>, but does not add link within <pre>' => [
                'text' => 'URL before pre http://wp.org<pre>http://wp.org</pre>',
                'expected' => 'URL before pre <a href="http://wp.org" rel="nofollow">http://wp.org</a><pre>http://wp.org</pre>',
            ],
            'Adds link to URL before <code>, but does not add link within <code>' => [
                'text' => 'URL before code http://wp.org<code>http://wp.org</code>',
                'expected' => 'URL before code <a href="http://wp.org" rel="nofollow">http://wp.org</a><code>http://wp.org</code>',
            ],
            'Does not add link to <PRE>, but does add link to URL after <PRE>' => [
                'text' => 'URL after pre <PRE>http://wp.org</PRE>http://wp.org',
                'expected' => 'URL after pre <PRE>http://wp.org</PRE><a href="http://wp.org" rel="nofollow">http://wp.org</a>',
            ],
            'Does not add link within <code>, but does add link to URL after <code>' => [
                'text' => 'URL after code <code>http://wp.org</code>http://wp.org',
                'expected' => 'URL after code <code>http://wp.org</code><a href="http://wp.org" rel="nofollow">http://wp.org</a>',
            ],
            'Adds link to before and after URLs, but does not add link within <pre>' => [
                'text' => 'URL before and after pre http://wp.org<pre>http://wp.org</pre>http://wp.org',
                'expected' => 'URL before and after pre <a href="http://wp.org" rel="nofollow">http://wp.org</a><pre>http://wp.org</pre><a href="http://wp.org" rel="nofollow">http://wp.org</a>',
            ],
            'Adds link to before and after URLs, but does not add link within <code>' => [
                'text' => 'URL before and after code http://wp.org<code>http://wp.org</code>http://wp.org',
                'expected' => 'URL before and after code <a href="http://wp.org" rel="nofollow">http://wp.org</a><code>http://wp.org</code><a href="http://wp.org" rel="nofollow">http://wp.org</a>',
            ],
            'Does not add links within nested <pre>URL <code>URL</code> </pre>' => [
                'text' => 'code inside pre <pre>http://wp.org <code>http://wp.org</code> http://wp.org</pre>',
                'expected' => 'code inside pre <pre>http://wp.org <code>http://wp.org</code> http://wp.org</pre>',
            ],

            // @ticket 16892
            // Test adds link inside of HTML elements.
            '<span>URL</span>' => [
                'text' => '<span>http://example.com</span>',
                'expected' => '<span><a href="http://example.com" rel="nofollow">http://example.com</a></span>',
            ],
            '<p>URL</p>' => [
                'text' => '<p>http://example.com/</p>',
                'expected' => '<p><a href="http://example.com/" rel="nofollow">http://example.com/</a></p>',
            ],

            // Test does not add links within the <a> element.
            '<a>URL</a>' => [
                'text' => 'Some text with a link <a href="http://example.com">http://example.com</a>',
                'expected' => 'Some text with a link <a href="http://example.com">http://example.com</a>',
            ],
            /*
            Fails in 3.3.1 too.
            '<a>text www.URL</a>'                        => array(
                'text'     => '<a href="http://wp.org">This is already a link www.wp.org</a>',
                'expected' => '<a href="http://wp.org">This is already a link www.wp.org</a>',
            ),
            */
        ];
    }

    /**
     * @ticket 16892
     */
    public function test_no_segfault()
    {
        $in = str_repeat('http://example.com/2011/03/18/post-title/', 256);
        $out = make_clickable($in);
        $this->assertSame($in, $out);
    }

    /**
     * @ticket 19028
     */
    public function test_line_break_in_existing_clickable_link()
    {
        $html = "<a
				  href='mailto:someone@example.com'>someone@example.com</a>";
        $this->assertSame($html, make_clickable($html));
    }

    /**
     * @ticket 30162
     * @dataProvider data_script_and_style_tags
     */
    public function test_dont_link_script_and_style_tags($tag)
    {
        $this->assertSame($tag, make_clickable($tag));
    }

    public function data_script_and_style_tags()
    {
        return [
            [
                '<script>http://wp.org</script>',
            ],
            [
                '<style>http://wp.org</style>',
            ],
            [
                '<script type="text/javascript">http://wp.org</script>',
            ],
            [
                '<style type="text/css">http://wp.org</style>',
            ],
        ];
    }

    /**
     * @ticket 48022
     * @ticket 56444
     * @dataProvider data_add_rel_ugc_in_comments
     */
    public function test_add_rel_ugc_in_comments($content, $expected)
    {
        $comment_id = self::factory()->comment->create(
            [
                'comment_content' => $content,
            ],
        );

        ob_start();
        comment_text($comment_id);
        $comment_text = ob_get_clean();

        $this->assertStringContainsString($expected, make_clickable($comment_text));
    }

    public function data_add_rel_ugc_in_comments()
    {
        $home_url_http = set_url_scheme(home_url(), 'http');
        $home_url_https = set_url_scheme(home_url(), 'https');

        return [
            // @ticket 48022
            [
                'http://wp.org',
                '<a href="http://wp.org" rel="nofollow ugc">http://wp.org</a>',
            ],
            [
                'www.wp.org',
                '<p><a href="http://www.wp.org" rel="nofollow ugc">http://www.wp.org</a>',
            ],
            // @ticket 56444
            [
                'www.example.org',
                '<p><a href="http://www.example.org" rel="nofollow ugc">http://www.example.org</a>',
            ],
            [
                $home_url_http,
                '<a href="' . $home_url_http . '" rel="ugc">' . $home_url_http . '</a>',
            ],
            [
                $home_url_https,
                '<a href="' . $home_url_https . '" rel="ugc">' . $home_url_https . '</a>',
            ],
        ];
    }
}
