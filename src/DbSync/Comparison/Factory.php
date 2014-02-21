<?php namespace DbSync\Comparison;

class Factory {
    
    protected $type;
    
    protected $function;
    
    protected $blockSize;
    
    public function __construct($blockSize = 1000, $hashFunction = null)
    {
        $this->hashFunction = $hashFunction;
        
        $this->blockSize = $blockSize;
    }
    
    public function getComparisonIterator($source, $destination, $table, $primaryKey, $comparisonColumns, $where)
    {
        return new LimitIterator(new Hash($source, $destination, $table, $primaryKey, $comparisonColumns, $where, $this->hashFunction),$this->blockSize);
    }
}

