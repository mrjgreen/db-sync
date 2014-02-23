<?php namespace DbSync\Sync;

class Replace {

    protected $source;
    
    protected $destination;
    
    public function __construct($source, $destination)
    {
        $this->source = $source;
        
        $this->destination = $destination;
    }
    
    public function sync($table, $select)
    {
        $this->destination->multiReplace($table, $this->source->query($select));
    }
}

