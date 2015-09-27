<?php
class ColumnConfigurationTest extends PHPUnit_Framework_TestCase
{
    public function testItReturnsFullArraySet()
    {
        $array = [
            'one',
            'two',
            'three'
        ];

        $config = new \DbSync\ColumnConfiguration([], []);

        $this->assertEquals($array, $config->getIntersection($array));
    }

    public function testItReturnsOnlyArraySet()
    {
        $array = [
            'one',
            'two',
            'three'
        ];

        $only = [
            'one',
            'two',
        ];

        $config = new \DbSync\ColumnConfiguration($only, []);

        $this->assertEquals($only, $config->getIntersection($array));
    }

    public function testItReturnsExceptArraySet()
    {
        $array = [
            'one',
            'two',
            'three'
        ];

        $except = [
            'two',
        ];

        $expected = [
            'one',
            'three'
        ];

        $config = new \DbSync\ColumnConfiguration([], $except);

        $this->assertEquals($expected, $config->getIntersection($array));
    }

    public function testItReturnsCaseInsensitiveDiffIntersect()
    {
        $array = [
            'ONE',
            'Two',
            'three'
        ];

        $only = [
            'One',
            'TWO'
        ];

        $except = [
            'two',
        ];

        $expected = [
            'ONE',
        ];

        $config = new \DbSync\ColumnConfiguration($only, $except);

        $this->assertEquals($expected, $config->getIntersection($array));
    }
}