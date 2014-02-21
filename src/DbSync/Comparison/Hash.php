<?php namespace DbSync\Comparison;

class Hash extends HashAbstract {
    
    protected $source;
    
    protected $destination;
    
    protected $total;
    
    protected $primaryKey;
    
    protected $table;
        
    protected $comparisonColumns;
    
    protected $columns;
    
    protected $where;
    
    
    public function __construct($source, $destination, $table, $primaryKeyColumns, $comparisonColumns, $where = null, $hashFunction = null)
    {
        
        $this->where = $where ? ' WHERE ' . $where : '';
        
        $this->total = $source->fetchOne('SELECT count(*) FROM ' . $table . $this->where);
        
        $this->primaryKey = $primaryKeyColumns;
        
        $this->comparisonColumns = $comparisonColumns;
        
        $this->source = $source;
        
        $this->destination = $destination;
        
        $this->columns = array_unique(array_merge($primaryKeyColumns, $comparisonColumns));
        
        $this->table = $table;
        
        $this->primaryKey = $primaryKeyColumns;
        
        parent::__construct($hashFunction);
    }
    
    public function total()
    {
        return $this->source->fetchOne('SELECT count(*) FROM ' . $this->table . $this->where);
    }
    
    
    public function compare($offset, $blockSize)
    {
        $orderString = '`' . implode($this->primaryKey, '`,`') . '`';
        
        $colsString = '`' . implode($this->columns, '`,`') . '`';
                                
        $query = "SELECT COUNT(*) AS cnt, 
        COALESCE(LOWER(CONV(BIT_XOR(CAST(" . $this->getHashFunction() . "(CONCAT_WS('#', $colsString)) AS UNSIGNED)), 10, 16)), 0) AS crc,
        $orderString
        FROM (SELECT $colsString FROM $this->table
        FORCE INDEX (`PRIMARY`) 
        $this->where
        ORDER BY $orderString
        LIMIT $offset, $blockSize) as tmp";
                        
        $sourceResult = $this->source->fetch($query);
        
        $destResult = $this->destination->fetch($query);
        
        if($sourceResult['cnt'] == 0 && $destResult['cnt'] == 0)
        {
            $this->block = $this->total; // Make it invalid
        }
        
        return $sourceResult === $destResult ? false : array_slice($sourceResult, 2);
    }
}

