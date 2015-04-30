<?php namespace DbSync;

use PDO;

class EmptyDataset extends \RuntimeException
{

}

class Connection
{

    /**
     * An array to store the column names of tables
     * @var array
     */
    private $_tableCache = array();

    /**
     * A database connection object
     * @var Connection
     */
    private $_connection;

    const INSERT = 'INSERT';

    const INSERT_IGNORE = 'INSERT IGNORE';

    const INSERT_REPLACE = 'REPLACE';


    public function __construct(PDO $connection)
    {

        $this->_connection = $connection;

    }

    /**
     * Get the PDO connection or fetch the default one if it doesn't exist
     * @return \PDO
     */
    function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Get the PDO connection or fetch the default one if it doesn't exist
     * @return \PDO
     */
    function setConnection(PDO $connection)
    {
        $this->_connection = $connection;
    }

    /**
     * Prpare and execute a full query using place holders and bound paramters. Return the executed PDOStatement
     *
     * @param string $sql
     * @param mixed $bind
     * @return \PDOStatement
     */
    public function query($sql, $bind = array())
    {
        is_array($bind) or $bind = array($bind);

        $stmt = $this->getConnection()->prepare($sql);

        $lockWaitRetries = 5;

        do {
            try {
                $stmt->execute($bind);

                return $stmt;
            } catch (\PDOException $e) {

                if(!$this->exceptionIsLockWaitTimeout($e) || !$lockWaitRetries--)
                {
                    throw new \PDOException($e->getMessage() . "\n QUERY: " . $sql . "\n BIND: " . var_export($bind, 1));
                }

                usleep(rand(1000,5000));
            }
        } while (1);

    }

    /**
     * @param \PDOException $e
     * @return bool
     */
    private function exceptionIsLockWaitTimeout(\PDOException $e)
    {
        return strpos($e->getMessage(), 'try restarting transaction') !== false;
    }

    /**
     *
     * @param string $sql
     * @param mixed $bind
     * @return mixed
     */
    public function fetch($sql, $bind = array())
    {
        return $this->query($sql, $bind)->fetch();
    }

    /**
     *
     * @param string $sql
     * @param mixed $bind
     * @return mixed
     */
    public function fetchNumeric($sql, $bind = array())
    {
        return $this->query($sql, $bind)->fetch(PDO::FETCH_NUM);
    }

    /**
     *
     */
    public function quote($value)
    {
        return $this->_connection->quote($value);
    }

    /**
     *
     * @param string $sql
     * @param mixed $bind
     * @return array
     */
    public function fetchOne($sql, $bind = array())
    {
        return $this->query($sql, $bind)->fetchColumn();
    }

    /**
     * Queries the database to find the columns in the given table(s)
     *
     * @param mixed $table A string table name, or an array of string tablenames
     * @return array  An array containing the column names from the given table(s)
     */
    public function getColumnNames($table)
    {
        if (is_array($table)) {
            $columnNames = array();
            foreach ($table as $t) {
                $columnNames = array_merge($columnNames, $this->getColumnNames($t));
            }
            return $columnNames;
        }

        return array_keys($this->getColumnInfo($table));
    }

    /**
     * Queries the database to find the columns in the given table(s)
     *
     * @param mixed $table A string table name, or an array of string tablenames
     * @return array  An array containing the column names from the given table(s)
     */
    public function getColumnInfo($table)
    {
        if (!array_key_exists($table, $this->_tableCache)) {
            $cols = array();
            foreach ($this->query('SHOW COLUMNS FROM ' . $table) as $col) {
                isset($col['Field']) and $cols[$col['Field']] = $col;
            }
            $this->_tableCache[$table] = $cols;
        }
        return $this->_tableCache[$table];
    }

    public function showTables($database = null)
    {
        $from = $database ? ' FROM ' . $database : '';

        return array_map(function ($obj) {
            return $obj[0];
        }, $this->query('SHOW TABLES' . $from)->fetchAll(PDO::FETCH_NUM));
    }

    public function showPrimaryKey($table)
    {
        $fields = array_filter($this->getColumnInfo($table), function ($item) {
            return $item['Key'] === 'PRI';
        });

        return array_keys($fields);
    }


    /**
     *
     * @param string $table
     * @param array $data
     * @param bool $ignore Run as an insert ignore
     * @return \PDOStatement
     * @throws DbException
     */
    protected function doMultiInsert($table, $data, $type = self::INSERT)
    {

        $count = $data instanceof \PDOStatement ? $data->rowCount() : count($data);

        if (!$count) {
            throw new EmptyDataset('No data in array to insert');
        }

        $cols = null;
        $qs = '';
        $bind = array();
        $i = 0;

        foreach ($data as $row) {
            $cols or $cols = array_keys($row);
            $subq = '';
            foreach ($row as $col => $v) {
                $subq .= '?,';
                $bind[] = $row[$col];
            }

            $qs .= ('(' . rtrim($subq, ',') . ')' . (++$i !== $count ? ',' : ''));
        }

        return $this->query($type . ' INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES ' . $qs, $bind);
    }

    /**
     *
     * @param string $table
     * @param array $data
     * @param bool $ignore Run as an insert ignore
     * @return \PDOStatement
     * @throws DbException
     */
    public function multiInsert($table, $data)
    {
        return $this->doMultiInsert($table, $data);
    }

    /**
     *
     * @param string $table
     * @param array $data
     * @param bool $ignore Run as an insert ignore
     * @return \PDOStatement
     * @throws DbException
     */
    public function multiInsertIgnore($table, $data)
    {
        return $this->doMultiInsert($table, $data, self::INSERT_IGNORE);
    }

    /**
     *
     * @param string $table
     * @param array $data
     * @param bool $ignore Run as an insert ignore
     * @return \PDOStatement
     * @throws DbException
     */
    public function multiReplace($table, $data)
    {
        return $this->doMultiInsert($table, $data, self::INSERT_REPLACE);
    }

    /**
     *
     * @param string $table
     * @param array $data
     * @return \PDOStatement
     * @throws DbException
     */
    public function multiInsertOnDuplicateKeyUpdate($table, $data)
    {
        $count = $data instanceof \PDOStatement ? $data->rowCount() : count($data);

        if (!$count) {
            throw new EmptyDataset('No data in array to insert');
        }

        $cols = null;
        $qs = '';
        $bind = array();

        foreach ($data as $row) {
            $qs .= '(';
            foreach ($row as $col => $v) {
                $qs .= '?,';
                $bind[] = $row[$col];
            }
            $cols or $cols = array_keys($row);
            $qs = trim($qs, ',') . '),';
        }

        $colsValues = array_map(function ($col) {
            return $col . ' = VALUES(' . $col . ')';
        }, $cols);

        // Build the statement
        $sql = 'INSERT INTO ' . $table . ' 
		(' . implode(', ', $cols) . ') VALUES ' . trim($qs, ',') . '	
		ON DUPLICATE KEY UPDATE ' . implode(', ', $colsValues);

        return $this->query($sql, $bind);
    }

    public static function make(array $config)
    {

        extract($config);

        $db = isset($database) ? ";dbname={$database}" : '';

        $defaults = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        );

        $options = isset($options) ? array_replace($defaults, $options) : $defaults;

        $pdo = new PDO("mysql:host={$host}$db", $username, $password, $options);

        isset($charset) and $pdo->prepare("SET NAMES '{$charset}'")->execute();

        return new static($pdo);
    }

}
