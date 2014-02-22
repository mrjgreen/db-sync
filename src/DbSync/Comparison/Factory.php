<?php namespace DbSync\Comparison;

class Factory {
    
    //protected $type;
    
    protected $function;
    
    protected $blockSize;
    
    protected $table;
    
    protected $primaryKey;
    
    protected $comparisonColumns;

    protected $where;
    
    protected $destination;

    protected $source;
    
    public function __construct($blockSize = 1000, $hashFunction = null)
    {
        $this->hashFunction = $hashFunction;
        
        $this->blockSize = $blockSize;
    }
    
    public function setTable($table, $primaryKey, $comparisonColumns, $syncColumns, $where)
    {
        $this->table = $table;
        
        $this->primaryKey = $primaryKey;
        
        $this->comparisonColumns = $comparisonColumns;
        
        $this->syncColumns = $syncColumns;
        
        $this->where = $where;
    }
    
    public function setSource($source)
    {
        $this->source = $source;
    }
    
    public function setDestination($destination)
    {
        $this->destination = $destination;
    }
    
    public function getComparisonIterator()
    {
        return new LimitIterator(new Hash($this->source, $this->destination, $this->table, $this->primaryKey, $this->comparisonColumns, $this->where, $this->hashFunction),$this->blockSize);
    }
    
    public function getSyncObject()
    {
        return new Sync($this->source, $this->destination, $this->table, $this->primaryKey, $this->syncColumns, $this->where, $this->blockSize);
    }
}

