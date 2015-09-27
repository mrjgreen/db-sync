<?php

require_once __DIR__ . '/TestAbstract.php';

class TableTest extends TestAbstract
{
    public function testItCanCreateFromPdo()
    {
        \DbSync\Table::makeFromPdo($this->connection->getPdo(), self::DATABASE, 'dbsynctest1');
    }
}