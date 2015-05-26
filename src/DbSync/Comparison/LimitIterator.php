<?php namespace DbSync\Comparison;

class LimitIterator implements \Iterator {

    protected $blockSize;

    protected $block;

    protected $row;

    protected $comparsion;

    protected $transferSize;

    public function __construct($blockSize = 1000, $transferSize = 10)
    {
        $this->blockSize = $blockSize;

        $this->transferSize = $transferSize;
    }

    public function valid()
    {
        if($this->row === 0)
        {
            $this->nextBlock();
        }

        return $this->_valid();
    }

    private function _valid()
    {
        return $this->key() <= $this->comparsion->total();
    }

    public function rewind()
    {
        $this->row = 0;

        return $this->block = $this->comparsion->start();
    }

    public function next()
    {
        $this->row += $this->transferSize;

        if($this->row >= $this->blockSize)
        {
            $this->row = 0;

            $this->block += $this->blockSize;
        }
    }

    private function nextBlock()
    {
        while($this->_valid())
        {
            if($this->comparsion->compare($this->key(), $this->blockSize))
            {
                return;
            }

            $this->block = $this->nextBlockIndex();
        }
    }

    private function nextBlockIndex()
    {
        return $this->comparsion->nextValidIndex($this->block + $this->blockSize);
    }

    public function key()
    {
        return $this->block + $this->row;
    }

    public function current()
    {
        return $this->comparsion->compare($this->key(), $this->transferSize);
    }

    public function setComparison($comparsion)
    {
        $this->comparsion = $comparsion;
    }

}