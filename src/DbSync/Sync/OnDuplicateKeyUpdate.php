<?php namespace DbSync\Sync;

class OnDuplicateKeyUpdate {
    
    protected $source;
    
    protected $destination;
    
    public function __construct($source, $destination)
    {
        $this->source = $source;
        
        $this->destination = $destination;
    }
    
    public function sync($table, $select)
    {
        foreach($this->source->query($select) as $row)
        {
            $this->destination->insertOnDuplicateKeyUpdate($table, $row);
        }
    }
}

