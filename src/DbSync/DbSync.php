<?php namespace DbSync;

use Psr\Log\LoggerInterface;

class DbSync {
    
    protected $source;
    
    protected $destination;
                
    protected $comparison;
    
    protected $sync;
    
    protected $output;
    
    protected $execute;
        
    public function __construct($execute, $sourceOptions, $destOptions , $syncObject, $comparisonObject, LoggerInterface $output)
    {        
        $this->source = $sourceOptions;
        
        $this->destination = $destOptions;
                        
        $this->comparison = $comparisonObject;
                
        $this->sync = $syncObject;
                
        $this->output = $output;
        
        $this->execute = $execute;
        
        $execute ? $this->output->alert('Executing') : $this->output->info('Dry run only. Add --execute (-e) to perform write');
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
                        
        $syncColumns = $this->diffAndIntersect($this->source->getColumnNames($table), $onlySync, $exceptSync);
                
        $comparisonColumns = $this->diffAndIntersect($syncColumns, $onlyComparison, $exceptComparison);

        $this->comparison->setTable($table, $comparisonColumns, $syncColumns, $where);
                
        foreach($this->comparison as $row => $result)
        {            
            if($result) 
            {            
                $this->output->info("\tMismatch found in table: " . $table . ' Row: ' . $row . ' Select: ' . $result);
                
                if($this->execute)
                {
                    $this->output->info("\tExecuted");
                    
                    $this->sync->sync($table, $result);
                }
                else
                {
                    $this->output->info("\tDry run");
                }
            }
            
        }
    }
}