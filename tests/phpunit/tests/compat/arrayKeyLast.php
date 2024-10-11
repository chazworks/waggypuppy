<?php

/**
 * @group compat
 *
 * @covers ::array_key_last
 */
class Tests_Compat_ArrayKeyLast extends WP_UnitTestCase
{

    /**
     * Test that array_key_last() is always available (either from PHP or WP).
     *
     * @ticket 45055
     */
    public function test_array_key_last_availability()
    {
        $this->assertTrue(function_exists('array_key_last'));
    }

    /**
     * @dataProvider data_array_key_last
     *
     * @ticket 45055
     *
     * @param bool $expected The value of the key extracted to extracted from given array.
     * @param array $arr The array to get last key from.
     */
    public function test_array_key_last($expected, $arr)
    {
        $this->assertSame($expected, array_key_last($arr));
    }

    /**
     * Data provider for test_array_key_last().
     *
     * @return array
     */
    public function data_array_key_last()
    {
        return [
            'string key' => [
                'expected' => 'key2',
                'arr' => [
                    'key1' => 'val1',
                    'key2' => 'val2',
                ],
            ],
            'int key' => [
                'expected' => 1,
                'arr' => [
                    99 => 'val1',
                    1 => 'val2',
                ],
            ],
            'no key' => [
                'expected' => 1,
                'arr' => ['val1', 'val2'],
            ],
            'multi array' => [
                'expected' => 1,
                'arr' => [
                    99 => [22 => 'val1'],
                    1 => 'val2',
                ],
            ],
            'mixed keys' => [
                'expected' => 1,
                'arr' => [
                    'val1',
                    'key2' => 'val2',
                    'val3',
                ],
            ],
            'empty array' => [
                'expected' => null,
                'arr' => [],
            ],
        ];
    }
}
