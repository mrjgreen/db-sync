<?php namespace DbSync\Hash;

abstract class HashAbstract implements HashInterface {

    protected function buildChecksumQuery($hash)
    {
        return "COALESCE(LOWER(CONV(BIT_XOR(CAST(" . $hash . " AS UNSIGNED)), 10, 16)), 0)";
    }

    protected function getMultiByteHash($columnsString, $hash, $byteSize)
    {
        $cols = "CONCAT_WS('#', $columnsString)";

        $i = $byteSize;

        if($i === 1)
        {
            return $this->buildChecksumQuery("$hash($cols)");
        }

        $str = [];

        while($i--)
        {
            $start = (16 * $i) + 1;
            $str[] = $this->buildChecksumQuery("CONV(SUBSTR($hash($cols),$start,16),16,10)");
        }

        return "CONCAT(" . implode(',', $str) . ")";
    }
}
