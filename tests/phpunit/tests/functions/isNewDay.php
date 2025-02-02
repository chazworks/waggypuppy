<?php

/**
 * Test is_new_date() function.
 *
 * @since 5.2.0
 *
 * @group functions
 *
 * @covers ::is_new_day
 */
class Tests_Functions_IsNewDate extends WP_UnitTestCase
{

    /**
     * @ticket 46627
     * @dataProvider data_is_new_date
     *
     * @param string $currentday_string The day of the current post in the loop.
     * @param string $previousday_string The day of the previous post in the loop.
     * @param bool $expected Expected result.
     */
    public function test_is_new_date($currentday_string, $previousday_string, $expected)
    {
        global $currentday, $previousday;

        $currentday = $currentday_string;
        $previousday = $previousday_string;

        $this->assertSame($expected, is_new_day());
    }

    /**
     * Data provider.
     *
     * @return array[]
     */
    public function data_is_new_date()
    {
        return [
            ['21.05.19', '21.05.19', 0],
            ['21.05.19', '20.05.19', 1],
            ['21.05.19', false, 1],
        ];
    }
}
