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
    }

    private function setUpConnection()
    {
        $configs = include __DIR__ . '/config.php';

        foreach($configs as $config)
        {
            list($host, $port) = $this->parseHostPort($config['host']);
            $config['host'] = $host;
            $config['port'] = $port;

            try{
                $this->connection = (new \Database\Connectors\ConnectionFactory())->make($config);

                $this->config = $config;
                return;
            }catch (\PDOException $e) {
                throw $e;
            }
        }

        throw new \InvalidArgumentException("No valid database configs");
    }

    private function parseHostPort($host)
    {
        $parts = explode(':', $host, 2);

        isset($parts[1]) or $parts[1] = 3306;

        return $parts;
    }

}
