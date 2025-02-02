<?php

/**
 * @group formatting
 * @ticket 22300
 *
 * @covers ::map_deep
 */
class Tests_Formatting_MapDeep extends WP_UnitTestCase
{

    public function test_map_deep_with_any_function_over_empty_array_should_return_empty_array()
    {
        $this->assertSame([], map_deep([], [$this, 'append_baba']));
    }

    public function test_map_deep_should_map_each_element_of_array_one_level_deep()
    {
        $this->assertSame(
            [
                'ababa',
                'xbaba',
            ],
            map_deep(
                [
                    'a',
                    'x',
                ],
                [$this, 'append_baba'],
            ),
        );
    }

    public function test_map_deep_should_map_each_element_of_array_two_levels_deep()
    {
        $this->assertSame(
            [
                'ababa',
                [
                    'xbaba',
                ],
            ],
            map_deep(
                [
                    'a',
                    [
                        'x',
                    ],
                ],
                [$this, 'append_baba'],
            ),
        );
    }

    public function test_map_deep_should_map_each_object_element_of_an_array()
    {
        $this->assertEqualSets(
            [
                'var0' => 'ababa',
                'var1' => (object)[
                    'var0' => 'xbaba',
                ],
            ],
            map_deep(
                [
                    'var0' => 'a',
                    'var1' => (object)[
                        'var0' => 'x',
                    ],
                ],
                [$this, 'append_baba'],
            ),
        );
    }

    public function test_map_deep_should_apply_the_function_to_a_string()
    {
        $this->assertSame('xbaba', map_deep('x', [$this, 'append_baba']));
    }

    public function test_map_deep_should_apply_the_function_to_an_integer()
    {
        $this->assertSame('5baba', map_deep(5, [$this, 'append_baba']));
    }

    public function test_map_deep_should_map_each_property_of_an_object()
    {
        $this->assertEquals(
            (object)[
                'var0' => 'ababa',
                'var1' => 'xbaba',
            ],
            map_deep(
                (object)[
                    'var0' => 'a',
                    'var1' => 'x',
                ],
                [$this, 'append_baba'],
            ),
        );
    }

    public function test_map_deep_should_map_each_array_property_of_an_object()
    {
        $this->assertEquals(
            (object)[
                'var0' => 'ababa',
                'var1' => [
                    'xbaba',
                ],
            ],
            map_deep(
                (object)[
                    'var0' => 'a',
                    'var1' => [
                        'x',
                    ],
                ],
                [$this, 'append_baba'],
            ),
        );
    }

    public function test_map_deep_should_map_each_object_property_of_an_object()
    {
        $this->assertEquals(
            (object)[
                'var0' => 'ababa',
                'var1' => (object)[
                    'var0' => 'xbaba',
                ],
            ],
            map_deep(
                (object)[
                    'var0' => 'a',
                    'var1' => (object)[
                        'var0' => 'x',
                    ],
                ],
                [$this, 'append_baba'],
            ),
        );
    }

    /**
     * @ticket 35058
     */
    public function test_map_deep_should_map_object_properties_passed_by_reference()
    {
        $object_a = (object)['var0' => 'a'];
        $object_b = (object)[
            'var0' => &$object_a->var0,
            'var1' => 'x',
        ];
        $this->assertEquals(
            (object)[
                'var0' => 'ababa',
                'var1' => 'xbaba',
            ],
            map_deep($object_b, [$this, 'append_baba']),
        );
    }

    /**
     * @ticket 35058
     */
    public function test_map_deep_should_map_array_elements_passed_by_reference()
    {
        $array_a = ['var0' => 'a'];
        $array_b = [
            'var0' => &$array_a['var0'],
            'var1' => 'x',
        ];
        $this->assertSame(
            [
                'var0' => 'ababa',
                'var1' => 'xbaba',
            ],
            map_deep($array_b, [$this, 'append_baba']),
        );
    }

    public function append_baba($value)
    {
        return $value . 'baba';
    }
}
