<?php namespace DbSync;

use Database\Connection;
use Database\Query\Builder;
use Database\Query\Expression;

class Table {

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $database;

    /**
     * @var string
     */
    private $table;

    /**
     * @var array|null
     */
    private $cacheColumns;

    /**
     * @var array|null
     */
    private $cachePrimaryKey;

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
        $query = $this->query()
            ->select($this->getPrimaryKey())
            ->offset($position)
            ->limit(1);

        $this->applyPrimaryKeyWhere($query, $lastKey);

        return $query->first();
    }

    /**
     * @param $hash
     * @param array $lastKey
     * @param $blockSize
     * @return mixed
     */
    public function getHashForKey(array $columns, $hash, array $lastKey, $blockSize)
    {
        $subQuery = $this->query()->select($columns)->limit($blockSize);

        $this->applyPrimaryKeyWhere($subQuery, $lastKey);

        $query = $subQuery->newQuery()->from(new Expression("({$subQuery->toSql()}) t"));

        $query->mergeBindings($subQuery);

        $hash = $query->pluck(new Expression($hash));

        return $hash;
    }

    /**
     * @param array $columns
     * @param array $lastKey
     * @param $blockSize
     * @return \PDOStatement
     */
    public function getRowsForKey(array $columns, array $lastKey, $blockSize)
    {
        $query = $this->query()->select($columns)->limit($blockSize);

        $this->applyPrimaryKeyWhere($query, $lastKey);

        $stmt = $query->query();

        $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        return $stmt;
    }

    /**
     * @param \Traversable $rows
     * @param array $columns
     * @return int
     */
    public function insert(\Traversable $rows, array $columns)
    {
        $update = array();

        foreach($columns as $column)
        {
            $update[$column] = new Expression("VALUES(`$column`)");
        }

        return $this->query()->buffer()->insertOnDuplicateKeyUpdate($rows, $update);
    }

    /**
     * Queries the database to find the columns in the given table(s)
     *
     * @return array  An array containing the column names
     */
    public function getColumns()
    {
        if(!is_null($this->cacheColumns))
        {
            return $this->cacheColumns;
        }

        return $this->cacheColumns = $this->connection
            ->table('information_schema.columns')
            ->where(array(
                'table_schema' => $this->database,
                'table_name' => $this->table,
            ))->lists('column_name');
    }

    /**
     * #TODO - find non mysql specific way of doing this
     *
     * @return array
     */
    public function getPrimaryKey()
    {
        if(!is_null($this->cachePrimaryKey))
        {
            return $this->cachePrimaryKey;
        }

        $name = $this->connection->getQueryGrammar()->wrap($this->getQualifiedName());

        $rows = $this->connection->fetchAll("SHOW INDEX FROM $name WHERE `key_name` = 'PRIMARY'");

        $index = array();

        foreach($rows as $row)
        {
            $index[$row['Seq_in_index']] = $row['Column_name'];
        }

        ksort($index);

        return $this->cachePrimaryKey = array_values($index);
    }

    /**
     * @param Builder $query
     * @param array|null $values
     */
    private function applyPrimaryKeyWhere(Builder $query, array $values = null)
    {
        foreach($this->getPrimaryKey() as $keyCol)
        {
            $query->orderBy($keyCol);
        }

        if($values)
        {
            $sql = "({$this->columnize(array_keys($values))}) >= ({$this->connection->getQueryGrammar()->parameterize($values)})";

            $query->whereRaw($sql, $values);
        }
    }

    /**
     * @return Builder
     */
    private function query()
    {
        return $this->connection->table($this->getQualifiedName());
    }

    /**
     * @param array $columns
     * @return string
     */
    public function columnize(array $columns)
    {
        return $this->connection->getQueryGrammar()->columnize($columns);
    }

    /**
     * @return string
     */
    public function getQualifiedName()
    {
        return "$this->database.$this->table";
    }

    public function __toString()
    {
        return $this->getQualifiedName();
    }
}