<?php namespace DbSync\Comparison;

class HashAbstract {
    
    const HASH_MD5 = 'MD5';
    const HASH_SHA1 = 'SHA1';
    const HASH_CRC32 = 'CRC32';
    
    private static $validHasFunctions = array(
        self::HASH_MD5,
        self::HASH_SHA1,
        self::HASH_CRC32,
    );
    
    private $hashFunction;
    
    public function __construct($hashFunction = null)
    {
        $this->hashFunction = $hashFunction ? : self::HASH_CRC32;
        
        if(!in_array($this->hashFunction, self::$validHasFunctions))
        {
            throw new InvalidHashFunctionException("Function '$this->hashFunction' is not a valid hash function");
        }
    }
    
    protected function getHashFunction()
    {
        return $this->hashFunction;
    }
    
}

