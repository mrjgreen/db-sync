<?php namespace DbSync\Hash;

class ShaHash extends HashAbstract {

    const SHA1_SQL_FUNCTION = 'SHA1';

    const SHA1_BYTE_SIZE = 3;

    public function getHashSelect($columnsString)
    {
        return $this->getMultiByteHash($columnsString, self::SHA1_SQL_FUNCTION, self::SHA1_BYTE_SIZE);
    }
}
