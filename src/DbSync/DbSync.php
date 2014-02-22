<?php namespace DbSync;

use DbSync\Comparison\LimitIterator;
use DbSync\Comparison\Hash;
use DbSync\Comparison\Factory;
use DbSync\Sync\Single;
use Psr\Log\LoggerInterface;

class DbSync {
    
    protected $source;
    
    protected $destination;
            
    protected $comparisonFactory;
    
    protected $output;
    
    protected $execute;
        
    public function __construct($execute, array $sourceOptions, array $destOptions, Factory $comparisonFactory, LoggerInterface $output)
    {        
        $this->source = Dbal\Db::make($sourceOptions);
        
        $this->destination = Dbal\Db::make($destOptions);
                        
        $this->comparisonFactory = $comparisonFactory;
        
        $this->comparisonFactory->setSource($this->source);
        
        $this->comparisonFactory->setDestination($this->destination);
        
        $this->output = $output;
        
        $this->execute = $execute;
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
            $this->compareTable($table, $onlySync, $exceptSync, $onlyComparison, $exceptComparison, $where);
        }
    }
    
    public function compareTable($table, array $onlySync = array(), array $exceptSync = array(), array $onlyComparison = array(), array $exceptComparison = array(), $where = null)
    {        
        $this->output->info("Table: " . $table);
                
        $primaryKey = $this->source->showPrimaryKey($table);
        
        $syncColumns = $this->diffAndIntersect($this->source->getColumnNames($table), $onlySync, $exceptSync);
        
        $comparisonColumns = $this->diffAndIntersect($this->source->getColumnNames($table), $onlyComparison, $exceptComparison);
        
        $comparisonColumns = array_intersect($comparisonColumns, $syncColumns);

        $this->comparisonFactory->setTable($table, $primaryKey, $comparisonColumns, $syncColumns, $where);

        $comparisonIterator = $this->comparisonFactory->getComparisonIterator();
        
        $syncObject = $this->comparisonFactory->getSyncObject();
                
        foreach($comparisonIterator as $row => $result)
        {            
            if($result) 
            {            
                $this->output->info("\tMismatch found in table: " . $table . ' Row: ' . $row . ' Keys: ', $result);
                
                $syncObject->sync(array_values($result));
            }
            
        }
    }
    
    /*
    protected function sync($select, $insert, $keyValues)
    {
        $this->output->info("\tSource: \t" . $select);
        $this->output->info("\tDest:   \t" . $insert);
        
        foreach($this->source->query($select, $keyValues) as $row)
        {
            $this->output->debug("\tData: ", $row);
            
            if($this->execute)
            {
                $this->output->info("\tExecuted");
                $this->destination->query($insert, array_merge(array_values($row),array_values($row)));
            }
            else
            {
                $this->output->info("\tDry run");
            }
        }
    }*/
}