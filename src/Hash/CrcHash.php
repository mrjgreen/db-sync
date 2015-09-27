<?php namespace DbSync\Hash;

class CrcHash extends HashAbstract {

    const CRC_SQL_FUNCTION = 'CRC32';

    const CRC_BYTE_SIZE = 1;

    public function getHashSelect($columnsString)
    {
        return $this->getMultiByteHash($columnsString, self::CRC_SQL_FUNCTION, self::CRC_BYTE_SIZE);
    }
}
