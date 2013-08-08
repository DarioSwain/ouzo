<?php
use Model\Product;
use Thulium\Utilities\Arrays;
use Thulium\Utilities\Functions;

class ArraysTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldFindIntZeroInArray()
    {
        //given
        $array = array(
            'k1' => 4,
            'k2' => 'd',
            'k3' => 0,
            9 => 'p'
        );
        //when
        $zeroKey = Arrays::findKeyByValue($array, 0);

        //then
        $this->assertEquals('k3', $zeroKey);
    }

    /**
     * @test
     */
    public function shouldUseIdentityAsDefaultValueFunctionInToMap()
    {
        //given
        $array = range(1, 2);

        //when
        $map = Arrays::toMap($array, function ($elem) {
            return $elem * 10;
        });

        //then
        $this->assertEquals(array(10 => 1, 20 => 2), $map);
    }

    /**
     * @test
     */
    public function shouldGetLastElementOfArray()
    {
        //given
        $array = array('a', 'b', 'c');

        //when
        $last = Arrays::last($array);

        //then
        $this->assertEquals('c', $last);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function shouldThrowExceptionWhenElementsAreEmptyInLast()
    {
        //given
        $array = array();

        //when
        Arrays::last($array);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function shouldThrowExceptionWhenElementsAreEmptyInFirst()
    {
        //given
        $array = array();

        //when
        Arrays::first($array);
    }

    /**
     * @test
     */
    public function shouldCheckIsAnyIsBool()
    {
        //given
        $array = array('a', true, 'c');

        //when
        $any = Arrays::any($array, function ($element) {
            return is_bool($element);
        });

        //then
        $this->assertTrue($any);
    }

    /**
     * @test
     */
    public function shouldFilterByKeys()
    {
        //given
        $array = array('a' => 1, 'b' => 2, 'c' => 3);

        //when
        $filtered = Arrays::filterByKeys($array, array('a', 'b'));

        //then
        $this->assertEquals(array('a' => 1, 'b' => 2), $filtered);
    }

    /**
     * @test
     */
    public function shouldGroupByFunctionResult()
    {
        //given
        $product1 = new Product(array('name' => 'a', 'description' => '1'));
        $product2 = new Product(array('name' => 'b', 'description' => '2'));
        $product3 = new Product(array('name' => 'c', 'description' => '2'));
        $array = array($product1, $product2, $product3);

        //when
        $grouped = Arrays::groupBy($array, Functions::extractField('description'));

        //then
        $this->assertEquals(array(
            '1' => array($product1),
            '2' => array($product2, $product3))
            , $grouped);
    }

    /**
     * @test
     */
    public function shouldHandleEmptyArrayInGroupBy()
    {
        //given
        $array = array();

        //when
        $grouped = Arrays::groupBy($array, Functions::extractField('field'));

        //then
        $this->assertEmpty($grouped);
    }

}