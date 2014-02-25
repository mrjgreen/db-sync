<?php namespace DbSync;

use Psr\Log\LogLevel;

class Logger extends \Psr\Log\AbstractLogger
{
    protected $quiet;
    
    protected $verbose;
    
    const TAB = "    ";
    
    public $colors = array(
        LogLevel::DEBUG => '0;36', // Cyan
        LogLevel::INFO => '0;32', // Green
        LogLevel::NOTICE => '1;33', // Yellow
        LogLevel::WARNING => '0;35', // Purple
        LogLevel::ERROR => '0;31', // Red
        LogLevel::CRITICAL => array('0;30','43'), // Black/Yellow
        LogLevel::ALERT => array('1;37','45'), // White/Purple
        LogLevel::EMERGENCY => array('1;37','41'), // White/Red
     );
    
    /**
     * {@inheritdoc}
     */
    protected function format($level, $message)
    {
        // Wrap the whole thing in a nice red square
        
        $lines = explode(PHP_EOL, $message);
        
        // Get the max row length
        $max = max(array_map('strlen', $lines));
        
        // Pad each of the rows to this length
        foreach($lines as $i => $line){
            $lines[$i] = self::TAB . str_pad($line, $max + 5);
        }
        
        $string = implode(PHP_EOL, $lines);
        
        $colors = $this->colors[$level];
        
        if(is_array($colors)){
            // Create a padding string of empty spaces the same length as the max row
            $pad = str_repeat(self::TAB . str_repeat(" ", $max + 5) . PHP_EOL, 2);

            // Create the coloured string
            return "\n\033[{$colors[0]}m\033[{$colors[1]}m" . $pad . $string . $pad . "\033[0m\n";
        }else{
            return "\n\033[{$colors}m" . $string . "\033[0m\n";
        }

    }
    
    public function __construct($verbose = false, $quiet = false)
    {
        $this->quiet = $quiet;
        
        $this->verbose = $verbose;
    }
    public function log($level, $message, array $context = array())
    {
        if(!$this->quiet && ($this->verbose || $level !== \Psr\Log\LogLevel::DEBUG))
        { 
            foreach ($context as $key => $value)
            {
                $message .= $key . ' => ' . $value . ', ';
            }
            
            echo $this->format($level, $message);
        }
    }
}
