<?php namespace DbSync\Comparison;

class Hash extends HashAbstract {
    
    protected $source;
    
    protected $destination;
        
    protected $primaryKey;
    
    protected $sourcetable;
    
    protected $desttable;
            
    protected $syncColumns;
    
    protected $columns;
    
    protected $where;
    
    protected $total;
    
    
    public function __construct($source, $destination, $iterator, $hashFunction = null)
    {
        $this->source = $source;
        
        $this->destination = $destination;
                                
        parent::__construct($iterator, $hashFunction);
    }
    
    public function setTable($sourcetable, $desttable, $comparisonColumns, $syncColumns, $where)
    {
        $this->sourcetable = $sourcetable;
        $this->desttable = $desttable;
        
        $primaryKey = $this->source->showPrimaryKey($sourcetable);
        
        $this->primaryKey = \DbSync\implode_identifiers($primaryKey);
                
        $this->syncColumns = \DbSync\implode_identifiers($syncColumns);
        
        $this->columns = \DbSync\implode_identifiers(array_unique(array_merge($primaryKey, $comparisonColumns)));
        
        $this->where = $where ? ' AND ' . $where : '';
        
        $this->total = $this->source->fetchOne('SELECT count(*) FROM ' . $this->sourcetable . ' WHERE 1' . $this->where);
    }
    
    public function total()
    {
        return $this->total;
    }
    
    public function compare($offset, $blockSize)
    {
                                                
        $query = "SELECT
COALESCE(LOWER(CONV(BIT_XOR(CAST(" . $this->getHashFunction() . "(CONCAT_WS('#', $this->columns)) AS UNSIGNED)), 10, 16)), 0)
FROM (SELECT $this->columns FROM %s
    FORCE INDEX (`PRIMARY`) 
    WHERE 1
    $this->where
    ORDER BY $this->primaryKey
    LIMIT $offset, $blockSize) as tmp";
  
        if($this->source->fetchOne(sprintf($query,$this->sourcetable)) === $this->destination->fetchOne(sprintf($query,$this->desttable)))
        {
            return false;
        }
        
        return "SELECT $this->syncColumns FROM $this->sourcetable WHERE 1 $this->where ORDER BY $this->primaryKey LIMIT $offset, $blockSize";
    }
}

