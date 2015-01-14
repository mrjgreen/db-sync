<?php namespace DbSync\Comparison;

use DbSync\Connection;

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

    protected $start;

    private static $intTypes = array(
        'tinyint',
        'smallint',
        'int',
        'mediumint',
        'bigint'
    );
    
    public function __construct(Connection $source, Connection $destination, $iterator, $hashFunction = null)
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
                
        $this->syncColumns = \DbSync\implode_identifiers(array_unique(array_merge($primaryKey, $syncColumns)));
        
        $this->columns = \DbSync\implode_identifiers(array_unique(array_merge($primaryKey, $comparisonColumns)));
        
        $this->where = $where ? ' AND ' . $where : '';

        foreach($primaryKey as $key)
        {
            if($this->isInt($cols[$key]['Type']))
            {
                $this->limitKey = $key;

                $this->start = $this->getAggregateCount('MIN');

                $this->total = $this->getAggregateCount('MAX');

                return;
            }
        }

        $this->start = 0;

        $this->total = $this->source->fetchOne('SELECT count(*) FROM ' . $this->sourcetable . ' WHERE 1' . $this->where);
    }

    private function getAggregateCount($aggregate, $above = null)
    {
        $where = '';

        if($above)
        {
            $where = ' AND ' .  $this->limitKey . ' >= ' . $this->source->quote($above);
        }

        $query = 'SELECT %s(' . $this->limitKey . ') FROM ' . $this->sourcetable . ' WHERE 1' . $this->where . $where;

        return $this->source->fetchOne(sprintf($query, $aggregate));
    }
    
    public function total()
    {
        return $this->total;
    }
    
    public function start()
    {
        return $this->start;
    }

    private function buildFullComparison($hash)
    {
        return "COALESCE(LOWER(CONV(BIT_XOR(CAST(" . $hash . " AS UNSIGNED)), 10, 16)), 0)";
    }

    private function buildComparisonHash()
    {
        $cols = "CONCAT_WS('#', $this->columns)";

        $hash = $this->getHashFunction();

        if($hash === self::HASH_CRC32)
        {
            return $this->buildFullComparison("$hash($cols)");
        }

        $byteSizes = array(
            self::HASH_MD5 => 1,
            self::HASH_SHA1 => 3,
        );

        $i = $byteSizes[$hash];
        $str = array();

        while($i--)
        {
            $start = (16 * $i) + 1;
            $str[] = $this->buildFullComparison("CONV(SUBSTR($hash($cols),$start,16),16,10)");
        }

        return "CONCAT(" . implode(',', $str) . ")";
    }

    public function nextValidIndex($block)
    {
        return $this->limitKey ? ($this->getAggregateCount('MIN', $block) ?: $block) : $block;
    }
    
    private function compareLimit($offset, $blockSize)
    {
        return "SELECT " . $this->buildComparisonHash() . " FROM " .
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
        
        return "SELECT " . $this->buildComparisonHash() . " FROM " .
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

