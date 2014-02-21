<?php namespace DbSync\Comparison;

class LimitIterator implements \Iterator {
    
    protected $blockSize;
    
    protected $block = 0;
        
    protected $row = 0;
    
    protected $total;
    
    protected $comparsion;
    
    public function __construct($comparsion, $blockSize = 1000)
    {        
        $this->blockSize = $blockSize;
        
        $this->comparsion = $comparsion;

        $this->total = $this->comparsion->total();
    }
    
    public function valid()
    {
        if($this->row === 0)
        {
            $this->nextBlock();
        }
        
        return $this->key() < $this->total;
    }
    
    public function rewind()
    {
        $this->row = 0;
        
        return $this->block = 0;
    }
    
    public function next()
    {   
        if(++$this->row >= $this->blockSize)
        {
            $this->row = 0;
            $this->block++;
        }
    }
    
    private function nextBlock()
    {
        while($this->key() < $this->total)
        {         
            if($this->comparsion->compare($this->key(), $this->blockSize))
            {
                return;
            }
            
            $this->block++;
        }
    }
    
    public function key()
    {        
        return $this->block * $this->blockSize + $this->row;
    }

    public function current()
    {
        return $this->comparsion->compare($this->key(), 1);
    }
}

