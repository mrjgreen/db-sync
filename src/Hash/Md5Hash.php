<?php namespace DbSync\Hash;

class Md5Hash extends HashAbstract {

    const MD5_SQL_FUNCTION = 'MD5';

    const MD5_BYTE_SIZE = 2;

    public function getHashSelect($columnsString)
    {
        return $this->getMultiByteHash($columnsString, self::MD5_SQL_FUNCTION, self::MD5_BYTE_SIZE);
    }
}
