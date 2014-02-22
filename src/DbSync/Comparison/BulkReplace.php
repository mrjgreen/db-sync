<?php namespace DbSync\Comparison;

class BulkReplace {
    
    private $source;
    
    private $destination;
    
    private $table;
    
    private $primaryKey;
    
    private $syncColumns;
    
    private $where;
    
    private $blockSize;

    public function __construct($source, $destination, $table, $primaryKey, $syncColumns, $where, $blockSize)
    {
        $this->source = $source;
        
        $this->destination = $destination;
        
        $this->table = $table;
        
        $this->primaryKey = $primaryKey;
        
        $this->syncColumns = $syncColumns;
        
        $this->where = $where;
        
        $this->blockSize = $blockSize;
    }
    
    public function sync(array $primaryKeyValues)
    {
        $colString = implode(array_unique(array_merge($this->syncColumns, $this->primaryKey)), ',');
        
        $whereString = implode($this->primaryKey, ' = ? AND ') . ' = ?';
        
        $rows = $this->source->fetchAll("SELECT $colString FROM $this->table WHERE $whereString", $primaryKeyValues);

        $this->destination->multiReplace($this->table, $rows);
    }
}

