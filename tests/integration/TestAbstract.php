<?php

abstract class TestAbstract extends PHPUnit_Framework_TestCase
{
    const DATABASE = 'dbsync_int_test';

    /**
     * @var \Database\Connection
     */
    protected $connection;

    protected $config;

    public function setUp()
    {
        $this->setUpConnection();

        $this->setUpTables();
    }

    private function setUpConnection()
    {
        $configs = include __DIR__ . '/config.php';

        foreach($configs as $config)
        {
            try{
                $this->connection = (new \Database\Connectors\ConnectionFactory())->make($config);

                $this->config = $config;
                return;
            }catch (\PDOException $e) {

            }
        }

        throw new \InvalidArgumentException("No valid database configs");
    }

    private function setUpTables()
    {
        $dbName = self::DATABASE;
        $this->connection->query("CREATE DATABASE IF NOT EXISTS $dbName");
        $this->connection->query("DROP TABLE IF EXISTS $dbName.dbsynctest1");
        $this->connection->query("DROP TABLE IF EXISTS $dbName.dbsynctest2");

        $this->createTestTable($dbName . ".dbsynctest1");
        $this->createTestTable($dbName . ".dbsynctest2");
    }

    private function createTestTable($table)
    {
        $this->connection->query("CREATE TABLE $table (
  `customerNumber` int(11) NOT NULL,
  `customerName` varchar(50) NOT NULL,
  `contactLastName` varchar(50) DEFAULT NULL,
  `contactFirstName` varchar(50) DEFAULT NULL,
  `return` varchar(50) DEFAULT NULL,
  `addressLine1` varchar(50) DEFAULT NULL,
  `addressLine2` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `postalCode` varchar(15) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `salesRepEmployeeNumber` int(11) DEFAULT NULL,
  `creditLimit` double DEFAULT NULL,
  PRIMARY KEY (`customerNumber`,`customerName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
    }
}