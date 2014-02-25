<?php namespace DbSync;

use Psr\Log\LoggerInterface;
use DbSync\Sync\SyncInterface;
use DbSync\Comparison\HashAbstract;
use Dbal\Db;

class DbSync {
    
    protected $source;
    
    protected $destination;
                
    protected $comparison;
    
    protected $sync;
    
    protected $output;
    
    protected $execute;
        
    public function __construct($execute, Db $source, Db $destination , SyncInterface $syncObject, HashAbstract $comparisonObject, LoggerInterface $output)
    {        
        $this->source = $source;
        
        $this->destination = $destination;
                        
        $this->comparison = $comparisonObject;
                
        $this->sync = $syncObject;
                
        $this->output = $output;
        
        $this->execute = $execute;
        
        $execute ? $this->output->alert('Executing') : $this->output->info('Dry run only. Add --execute (-e) to perform write');
    }
    
    protected function diffAndIntersect(array $array, array $only, array $except)
    {
        $only and $array = array_intersect($array, $only);
                
        $except and $array = array_intersect($array, $except);
        
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
                    $this->output->info("\tExecuted");
                    
                    $this->sync->sync($desttable, $select);
                }
            }
            
        }
    }
}