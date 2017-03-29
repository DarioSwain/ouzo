<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
use Ouzo\Utilities\Booleans;
use Ouzo\Utilities\Objects;

class BooleansTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider toBoolean
     * @param $string
     * @param $expected
     */
    public function shouldConvertToBoolean($string, $expected)
    {
        //when
        $toBoolean = Booleans::toBoolean($string);

        //then
        $this->assertEquals($expected, $toBoolean, 'To convert: ' . Objects::toString($string) . ' Expected: ' . Objects::toString($expected));
    }

    public function toBoolean()
    {
        return [
            [true, true],
            [null, false],
            ['true', true],
            ['TRUE', true],
            ['tRUe', true],
            ['on', true],
            ['yes', true],
            ['false', false],
            ['x gti', false],
            ['0', false],
            ['1', true],
            ['2', true],
            [0, false],
            [1, true],
            [2, true]
        ];
    }
}
