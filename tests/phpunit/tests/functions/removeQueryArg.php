<?php

/**
 * @group functions
 *
 * @covers ::remove_query_arg
 */
class Tests_Functions_RemoveQueryArg extends WP_UnitTestCase
{

    /**
     * @dataProvider data_remove_query_arg
     */
    public function test_remove_query_arg($keys_to_remove, $url, $expected)
    {
        $actual = remove_query_arg($keys_to_remove, $url);

        $this->assertNotEmpty($actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * Data provider.
     *
     * @return array[]
     */
    public function data_remove_query_arg()
    {
        return [
            ['foo', 'edit.php?foo=test1&baz=test1', 'edit.php?baz=test1'],
            [['foo'], 'edit.php?foo=test2&baz=test2', 'edit.php?baz=test2'],
            [['foo', 'baz'], 'edit.php?foo=test3&baz=test3', 'edit.php'],
            [['fakefoo', 'fakebaz'], 'edit.php?foo=test4&baz=test4', 'edit.php?foo=test4&baz=test4'],
            [['fakefoo', 'baz'], 'edit.php?foo=test4&baz=test4', 'edit.php?foo=test4'],
        ];
    }

    public function test_should_fall_back_on_current_url()
    {
        $old_request_uri = $_SERVER['REQUEST_URI'];
        $_SERVER['REQUEST_URI'] = 'edit.php?foo=bar&baz=quz';

        $actual = remove_query_arg('foo');

        $_SERVER['REQUEST_URI'] = $old_request_uri;

        $this->assertSame('edit.php?baz=quz', $actual);
    }
}
