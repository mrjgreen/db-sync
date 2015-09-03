<?php
class ColumnConfigurationTest extends PHPUnit_Framework_TestCase
{
    public function testItReturnsFullArraySet()
    {
        $array = array(
            'one',
            'two',
            'three'
        );

        $config = new \DbSync\ColumnConfiguration(array(), array());

        $this->assertEquals($array, $config->getIntersection($array));
    }

    public function testItReturnsOnlyArraySet()
    {
        $array = array(
            'one',
            'two',
            'three'
        );

        $only = array(
            'one',
            'two',
        );

        $config = new \DbSync\ColumnConfiguration($only, array());

        $this->assertEquals($only, $config->getIntersection($array));
    }

    public function testItReturnsExceptArraySet()
    {
        $array = array(
            'one',
            'two',
            'three'
        );

        $except = array(
            'two',
        );

        $expected = array(
            'one',
            'three'
        );

        $config = new \DbSync\ColumnConfiguration(array(), $except);

        $this->assertEquals($expected, $config->getIntersection($array));
    }

    public function testItReturnsCaseInsensitiveDiffIntersect()
    {
        $array = array(
            'ONE',
            'Two',
            'three'
        );

        $only = array(
            'One',
            'TWO'
        );

        $except = array(
            'two',
        );

        $expected = array(
            'ONE',
        );

        $config = new \DbSync\ColumnConfiguration($only, $except);

        $this->assertEquals($expected, $config->getIntersection($array));
    }
}