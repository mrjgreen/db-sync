<?php namespace DbSync;

use DbSync\Comparison\Hash;
use DbSync\Comparison\LimitIterator;
use Psr\Log\LoggerInterface;
use DbSync\Sync\SyncInterface;
use DbSync\Comparison\HashAbstract;

class DbSync {
    
    protected $source;

    protected $comparison;
    
    protected $sync;
    
    protected $output;
    
    protected $execute;
        
    public function __construct($execute, Connection $source, SyncInterface $syncObject, HashAbstract $comparisonObject, LoggerInterface $output = null)
    {        
        $this->source = $source;

        $this->comparison = $comparisonObject;
                
        $this->sync = $syncObject;
                
        $this->output = $output ?: new Logger();
        
        $this->execute = $execute;
        
        $execute ? $this->output->alert('Executing') : $this->output->info('Dry run only. Add --execute (-e) to perform write');
    }

    public function setLogger(LoggerInterface $log)
    {
        $this->output = $log;
    }
    
    protected function diffAndIntersect(array $array, array $only, array $except)
    {
        $only and $array = array_intersect($array, $only);
                
        $except and $array = array_diff($array, $except);
        
        return $array;
    }
    
    public function compareDatabase(array $onlyTables = array(), array $exceptTables = array(), array $onlySync = array(), array $exceptSync = array(), array $onlyComparison = array(), array $exceptComparison = array(), $where = null)
    {
        $tables = $this->diffAndIntersect($this->source->showTables(), $onlyTables, $exceptTables);
        
        foreach($tables as $table)
        {
            $this->compareTable($table, $table, $onlySync, $exceptSync, $onlyComparison, $exceptComparison, $where);
        }
    }
    
    public function compareTable($sourcetable, $desttable, array $onlySync = array(), array $exceptSync = array(), array $onlyComparison = array(), array $exceptComparison = array(), $where = null)
    {        
        $this->output->info("Table: " . $sourcetable . ' => ' . $desttable);
                        
        $syncColumns = $this->diffAndIntersect($this->source->getColumnNames($sourcetable), $onlySync, $exceptSync);
                
        $comparisonColumns = $this->diffAndIntersect($syncColumns, $onlyComparison, $exceptComparison);
        
        if(count($comparisonColumns) === 0)
        {
            throw new \Exception("No columns to left to compare. Please ensure you have not set the --columns option to a non-existent field name or a value not selected to sync");
        }

        $this->comparison->setTable($sourcetable, $desttable, $comparisonColumns, $syncColumns, $where);
                
        foreach($this->comparison as $row => $select)
        {            
            if($select) 
            {            
                $this->output->info("\tMismatch found in table: " . $sourcetable . ' => ' . $desttable . "\tRow: " . $row);
                $this->output->debug("\tSelect: " . $select);
                
                if($this->execute)
                {
                    $rows = $this->sync->sync($desttable, $select);
                    
                    $this->output->info("\tExecuted. Rows written: " . intval($rows));
                }
            }
            
        }
    }

    private static function buildConnection($connection)
    {
        if($connection instanceof \PDO)
        {
            $connection = new Connection($connection);
        }
        elseif(is_array($connection))
        {
            $connection = Connection::make($connection);
        }
        elseif(!$connection instanceof Connection)
        {
            throw new \InvalidArgumentException("Argument must be an instance of PDO, DbSync\\Connection or a valid connection config array");
        }

        return $connection;
    }

    public static function make($execute, $source, $destination, $syncMethod = 'replace', $comparisonFunction = 'SHA1', $chunkSize = 1000, $transferSize = 50)
    {
        $source = self::buildConnection($source);
        $destination = self::buildConnection($destination);

        if($syncMethod === 'replace')
        {
            $method = 'DbSync\Sync\Replace';
        }
        elseif($syncMethod === 'update')
        {
            $method = 'DbSync\Sync\OnDuplicateKeyUpdate';
        }
        else
        {
            throw new Exception('Invalid sync method: ' . $syncMethod);
        }

        $syncObject = new $method($source, $destination);

        $iterator = new LimitIterator($chunkSize, $transferSize);
        $comparisonObject = new Hash($source, $destination, $iterator, $comparisonFunction);

        return new static($execute, $source, $syncObject, $comparisonObject);
    }
}
