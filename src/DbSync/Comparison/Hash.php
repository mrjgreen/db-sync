<?php namespace DbSync\Comparison;

class Hash extends HashAbstract {
    
    protected $source;
    
    protected $destination;
        
    protected $primaryKey;
    
    protected $table;
        
    protected $comparisonColumns;
    
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
    
    public function setTable($table, $comparisonColumns, $syncColumns, $where)
    {
        $this->table = $table;
        
        $this->primaryKey = $this->source->showPrimaryKey($table);
        
        $this->comparisonColumns = $comparisonColumns;
        
        $this->syncColumns = $syncColumns;
        
        $this->columns = array_unique(array_merge($this->primaryKey, $comparisonColumns));
        
        $this->where = $where ? ' AND ' . $where : '';
        
        $this->total = $this->source->fetchOne('SELECT count(*) FROM ' . $this->table . ' WHERE 1' . $this->where);
    }
    
    public function total()
    {
        return $this->total;
    }
    
    public function compare($offset, $blockSize)
    {
        $orderString = '`' . implode($this->primaryKey, '`,`') . '`';
        
        $colsString = '`' . implode($this->columns, '`,`') . '`';
        
        $syncColsString = '`' . implode($this->columns, '`,`') . '`';
                                
        $query = "SELECT
        COALESCE(LOWER(CONV(BIT_XOR(CAST(" . $this->getHashFunction() . "(CONCAT_WS('#', $colsString)) AS UNSIGNED)), 10, 16)), 0) AS crc
        FROM (SELECT $colsString FROM $this->table
        FORCE INDEX (`PRIMARY`) 
        WHERE 1
        $this->where
        ORDER BY $orderString
        LIMIT $offset, $blockSize) as tmp";
                        
        $sourceResult = $this->source->fetch($query);
        
        $destResult = $this->destination->fetch($query);

        if($sourceResult === $destResult)
        {
            return false;
        }
        
        return "SELECT $syncColsString FROM $this->table WHERE 1 $this->where ORDER BY $orderString LIMIT $offset, $blockSize";
    }
}

