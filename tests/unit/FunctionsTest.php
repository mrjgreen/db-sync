<?php
class FunctionsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerIdentifiersTest
     *
     */
    public function testItCorrectlyImplodesIdentifiers($identifiers, $expected)
    {
        $this->assertEquals($expected, \DbSync\implode_identifiers($identifiers));
    }

    public function providerIdentifiersTest()
    {
        return array(
            array(array('db.test', 'another.db', ), '`db`.`test`,`another`.`db`'),
            array(array('some`thing.e`lse'),'`some\`thing`.`e\`lse`'),
        );
    }
}