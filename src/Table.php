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

        foreach($this->getPrimaryKey() as $keyCol)
        {
            $query->orderBy($keyCol);
        }

        $this->applyPrimaryKeyWhere($query, $lastKey);

        return $query->first();
    }

    /**
     * @param array $columns
     * @param $hash
     * @param array $startIndex
     * @param array $endIndex
     * @return mixed
     */
    public function getHashForKey(array $columns, $hash, array $startIndex, array $endIndex)
    {
        $subQuery = $this->query()->select($columns);

        $this->applyPrimaryKeyWhere($subQuery, $startIndex, $endIndex);

        $query = $subQuery->newQuery()->from(new Expression("({$subQuery->toSql()}) t"));

        $query->mergeBindings($subQuery);

        $hash = $query->pluck(new Expression($hash));

        return $hash;
    }

    /**
     * @param array $columns
     * @param array $startIndex
     * @param array $endIndex
     * @return \PDOStatement
     */
    public function getRowsForKey(array $columns, array $startIndex, array $endIndex)
    {
        $query = $this->query()->select($columns);

        $this->applyPrimaryKeyWhere($query, $startIndex, $endIndex);

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
     * @param array|null $startIndex
     * @param array|null $endIndex
     */
    private function applyPrimaryKeyWhere(Builder $query, array $startIndex = null, array $endIndex = null)
    {
        if($startIndex)
        {
            $sql = "({$this->columnize(array_keys($startIndex))}) >= ({$this->connection->getQueryGrammar()->parameterize($startIndex)})";

            $query->whereRaw($sql, $startIndex);

            // Optimisation to isolate first item in index - also works well for partition pruning
            $first = reset($startIndex);
            $key = key($startIndex);

            $query->where($key, '>=', $first);
        }

        if($endIndex)
        {
            $sql = "({$this->columnize(array_keys($endIndex))}) < ({$this->connection->getQueryGrammar()->parameterize($endIndex)})";

            $query->whereRaw($sql, $endIndex);

            // Optimisation to isolate first item in index - also works well for partition pruning
            $first = reset($endIndex);
            $key = key($endIndex);

            $query->where($key, '<=', $first);
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