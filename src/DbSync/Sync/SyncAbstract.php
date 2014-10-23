<?php namespace DbSync\Sync;

use DbSync\Connection;

abstract class SyncAbstract implements SyncInterface {
    
    protected $source;
    
    protected $destination;
    
    public function __construct(Connection $source, Connection $destination)
    {
        $this->source = $source;
        
        $this->destination = $destination;
    }
    
    public function sync($table, $select)
    {
        $stmt = $this->source->query($select);
        
        if($stmt->rowCount() > 0)
        {
            return $this->write($table, $stmt);
        }
    }
    
    abstract protected function write($table, \PDOStatement $stmt);
}

