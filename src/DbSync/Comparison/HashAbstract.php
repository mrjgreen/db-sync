<?php namespace DbSync\Comparison;

abstract class HashAbstract implements \IteratorAggregate {
    
    const HASH_MD5 = 'MD5';
    const HASH_SHA1 = 'SHA1';
    const HASH_CRC32 = 'CRC32';
    
    private static $validHasFunctions = array(
        self::HASH_MD5,
        self::HASH_SHA1,
        self::HASH_CRC32,
    );
    
    private $hashFunction;
    
    private $iterator;
    
    public function __construct($iterator, $hashFunction = null)
    {
        $this->hashFunction = $hashFunction ? : self::HASH_MD5;
        
        $this->iterator = $iterator;
        
        $this->iterator->setComparison($this);
        
        if(!in_array($this->hashFunction, self::$validHasFunctions))
        {
            throw new InvalidHashFunctionException("Function '$this->hashFunction' is not a valid hash function");
        }
    }
    
    protected function getHashFunction()
    {
        return $this->hashFunction;
    }
    
    public function getIterator()
    {
        return $this->iterator;
    }
}

