<?php
class ResultTest extends PHPUnit_Framework_TestCase
{
    public function testItStoresCorrectCounts()
    {
        $result = new \DbSync\Result();

        $result->addRowsAffected(10);
        $result->addRowsChecked(5);
        $result->addRowsTransferred(2);

        $result->addRowsAffected(3);
        $result->addRowsChecked(2);
        $result->addRowsTransferred(1);

        $this->assertEquals(13, $result->getRowsAffected());
        $this->assertEquals(7, $result->getRowsChecked());
        $this->assertEquals(3, $result->getRowsTransferred());

        $expectedArray = array(
            'checked'       => 7,
            'transferred'   => 3,
            'affected'      => 13,
        );

        $this->assertEquals($expectedArray, $result->toArray());
    }
}