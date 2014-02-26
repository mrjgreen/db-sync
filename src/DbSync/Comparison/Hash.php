<?php namespace DbSync\Comparison;

class Hash extends HashAbstract {
    
    protected $source;
    
    protected $destination;
        
    protected $primaryKey;
    
    protected $limitKey;
    
    protected $sourcetable;
    
    protected $desttable;
            
    protected $syncColumns;
    
    protected $columns;
    
    protected $where;
    
    protected $total;
    
    private static $intTypes = array(
        'tinyint',
        'smallint',
        'int',
        'mediumint',
        'bigint'
    );
    
    public function __construct($source, $destination, $iterator, $hashFunction = null)
    {
        $this->source = $source;
        
        $this->destination = $destination;
                                
        parent::__construct($iterator, $hashFunction);
    }
    
    public function isInt($type)
    {
        foreach(static::$intTypes as $int)
        {
            if(strpos($type, $int) === 0)
            {
                return true;
            }
        }
    }
    
    public function setTable($sourcetable, $desttable, $comparisonColumns, $syncColumns, $where)
    {
        $this->sourcetable = $sourcetable;
        $this->desttable = $desttable;
        
        $primaryKey = $this->source->showPrimaryKey($sourcetable);
        
        $cols = $this->source->getColumnInfo($sourcetable);
        
        $this->primaryKey = \DbSync\implode_identifiers($primaryKey);
                
        $this->syncColumns = \DbSync\implode_identifiers($syncColumns);
        
        $this->columns = \DbSync\implode_identifiers(array_unique(array_merge($primaryKey, $comparisonColumns)));
        
        $this->where = $where ? ' AND ' . $where : '';
        
        if(count($primaryKey) === 1 and $this->isInt($cols[$primaryKey[0]]['Type']))
        {
            $this->limitKey = $primaryKey[0];
            
            $query = 'SELECT %s(' . $this->limitKey . ') FROM ' . $this->sourcetable . ' WHERE 1' . $this->where;
            
            $this->start = $this->source->fetchOne(sprintf($query, 'MIN'));
        
            $this->total = $this->source->fetchOne(sprintf($query, 'MAX'));
        }
        else {
            $this->start = 0;
        
            $this->total = $this->source->fetchOne('SELECT count(*) FROM ' . $this->sourcetable . ' WHERE 1' . $this->where);
        }
    }
    
    public function total()
    {
        return $this->total;
    }
    
    public function start()
    {
        return $this->start;
    }
    
    private function compareLimit($offset, $blockSize)
    {                                      
        return "SELECT COALESCE(LOWER(CONV(BIT_XOR(CAST(" . $this->getHashFunction() . "(CONCAT_WS('#', $this->columns)) AS UNSIGNED)), 10, 16)), 0) FROM " .
               "(" .
                    "SELECT $this->columns FROM %s FORCE INDEX (`PRIMARY`) WHERE 1 " .
                    $this->where . " ".
                    "ORDER BY $this->primaryKey " .
                    "LIMIT $offset, $blockSize" .
                ") as tmp";
    }
    
    private function compareIndex($offset, $blockSize)
    {
                  
        $endOffset = $offset + $blockSize;
        
        return "SELECT COALESCE(LOWER(CONV(BIT_XOR(CAST(" . $this->getHashFunction() . "(CONCAT_WS('#', $this->columns)) AS UNSIGNED)), 10, 16)), 0) FROM " .
               " %s FORCE INDEX (`PRIMARY`) WHERE " .
               "$this->limitKey BETWEEN $offset AND $endOffset " .
               $this->where;
    }
    
    private function selectLimit($offset, $blockSize)
    {
        return "SELECT $this->syncColumns FROM $this->sourcetable WHERE 1 $this->where ORDER BY $this->primaryKey LIMIT $offset, $blockSize";
    }
    
    private function selectIndex($offset, $blockSize)
    {
        $endOffset = $offset + $blockSize;
        
        return "SELECT $this->syncColumns FROM $this->sourcetable WHERE $this->limitKey BETWEEN $offset AND $endOffset  $this->where";
    }
    
    public function compare($offset, $blockSize)
    {      
        $query = $this->limitKey ? $this->compareIndex($offset, $blockSize) : $this->compareLimit($offset, $blockSize);

        if($this->source->fetchOne(sprintf($query,$this->sourcetable)) === $this->destination->fetchOne(sprintf($query,$this->desttable)))
        {
            return false;
        }
        
        return $this->limitKey ? $this->selectIndex($offset, $blockSize) : $this->selectLimit($offset, $blockSize);
    }
}

