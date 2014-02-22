<?php namespace DbSync\Comparison;

class Sync {
    
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
        
        $rows = $this->source->query("SELECT $colString FROM $this->table WHERE $whereString", $primaryKeyValues);
        
        foreach($rows as $row)
        {
            $this->destination->insertOnDuplicateKeyUpdate($this->table, $row);
        }
    }
}

