<?php namespace DbSync;

class Logger extends \Psr\Log\AbstractLogger
{
    protected $quiet;
    
    protected $verbose;
    
    
    public function __construct($verbose = false, $quiet = false)
    {
        $this->quiet = $quiet;
        
        $this->verbose = $verbose;
    }
    public function log($level, $message, array $context = array())
    {
        if(!$this->quiet && ($this->verbose || $level > \Psr\Log\LogLevel::DEBUG))
        {
            echo $message;
            
            foreach ($context as $key => $value)
            {
                echo $key . ' => ' . $value . ', ';
            }
            
            echo PHP_EOL;
        }
    }
}
