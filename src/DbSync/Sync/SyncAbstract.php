<?php namespace DbSync\Sync;

abstract class SyncAbstract implements SyncInterface {
    
    protected $source;
    
    protected $destination;
    
    public function __construct($source, $destination)
    {
        $this->source = $source;
        
        $this->destination = $destination;
    }
    
}

