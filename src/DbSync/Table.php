<?php namespace DbSync;

use Database\Connection;
use Database\Query\Expression;

class Table {

    const HASH_MD5 = 'MD5';
    const HASH_SHA1 = 'SHA1';
    const HASH_CRC32 = 'CRC32';

    private $connection;

    private $database;

    private $table;

    /**
     * @param Connection $connection
     * @param $database
     * @param $table
     */
    public function __construct(Connection $connection, $database, $table)
    {
        $this->connection = $connection;

        $this->database = $database;

        $this->table = $table;
    }

    /**
     * @param $position
     * @param array|null $lastKey
     * @return mixed|static
     */
    public function getKeyAtPosition(array $lastKey, $position)
    {
        $key = $this->getPrimaryKey();

        $query =
            $this->query()
                ->select($key)
                ->offset($position)
                ->limit(1);

        foreach($key as $keyCol)
        {
            $query->orderBy($keyCol);
        }

        if($lastKey)
        {
            foreach($lastKey as $column => $value)
            {
                $query->where($column, '>=', $value);
            }
        }

        return $query->first();
    }

    /**
     * Queries the database to find the columns in the given table(s)
     *
     * @return array  An array containing the column names
     */
    public function getColumns()
    {
        return $this->connection
            ->table('information_schema.columns')
            ->where(array(
                'table_schema' => $this->database,
                'table_name' => $this->table,
            ))->lists('column_name');
    }

    /**
     * @return array
     */
    public function getPrimaryKey()
    {
        $rows = $this->connection->query("SHOW INDEX FROM $this->database.$this->table WHERE key_name = 'PRIMARY'");

        $index = array();

        foreach($rows as $row)
        {
            $index[$row['Seq_in_index']] = $row['Column_name'];
        }

        ksort($index);

        return array_values($index);
    }

    private function buildFullComparison($hash)
    {
        return "COALESCE(LOWER(CONV(BIT_XOR(CAST(" . $hash . " AS UNSIGNED)), 10, 16)), 0)";
    }

    private function buildComparisonHash($compareColumns)
    {
        $cols = implode(',', $compareColumns);

        $cols = "CONCAT_WS('#', $cols)";

        var_dump($cols);

        $hash = self::HASH_SHA1; //$this->getHashFunction();

        if($hash === self::HASH_CRC32)
        {
            return $this->buildFullComparison("$hash($cols)");
        }

        $byteSizes = array(
            self::HASH_MD5 => 1,
            self::HASH_SHA1 => 3,
        );

        $i = $byteSizes[$hash];
        $str = array();

        while($i--)
        {
            $start = (16 * $i) + 1;
            $str[] = $this->buildFullComparison("CONV(SUBSTR($hash($cols),$start,16),16,10)");
        }

        return "CONCAT(" . implode(',', $str) . ")";
    }

    public function getHashForKey(array $compareColumns, array $lastKey, $blockSize)
    {
        $select = $this->buildComparisonHash($compareColumns);

        $key = $this->getPrimaryKey();

        $query =
            $this->query()
                ->limit($blockSize);

        foreach($key as $keyCol)
        {
            $query->orderBy($keyCol);
        }

        foreach($lastKey as $column => $value)
        {
            $query->where($column, '>=', $value);
        }

        $hash = $query->pluck(new Expression($select));

        return $hash;
    }

    public function getRowsForKey(array $columns, array $lastKey, $blockSize)
    {
        $key = $this->getPrimaryKey();

        $query =
            $this->query()
                ->select($columns)
                ->limit($blockSize);

        foreach($key as $keyCol)
        {
            $query->orderBy($keyCol);
        }

        foreach($lastKey as $column => $value)
        {
            $query->where($column, '>=', $value);
        }

        return $query->get();
    }

    public function insert(array $rows)
    {
        $first = reset($rows);

        $update = array();

        foreach(array_keys($first) as $column)
        {
            $update[$column] = new Expression("VALUES($column)");
        }

        $this->query()->insertOnDuplicateKeyUpdate($rows, $update);
    }

    private function query()
    {
        return $this->connection->table("$this->database.$this->table");
    }
}