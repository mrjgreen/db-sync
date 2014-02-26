<?php namespace DbSync\Sync;

abstract class SyncAbstract implements SyncInterface {
    
    protected $source;
    
    protected $destination;
    
    public function __construct($source, $destination)
    {
        $this->source = $source;
        
        $this->destination = $destination;
    }
    
    public function sync($table, $select)
    {
        $stmt = $this->source->query($select);
        
        if($stmt->rowCount() > 0)
        {
            $this->write($table, $stmt);
        }
    }
    
    abstract protected function write($table, \PDOStatement $stmt);
}

