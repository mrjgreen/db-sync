<?php namespace DbSync\Dbal;

use PDO;

class EmptyDataset extends \RuntimeException {
    
}

class Db {

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
    
    /**
     * An array to store a query profiling function
     * @var bool 
     */
    private $_profile;
    
    /**
     * An array where query profiles will be stored if _profile is set to true
     * @var array
     */ 
    private $_queries = array();
    
    public function __construct(PDO $connection, $profile = false) {
        
        $this->_connection = $connection;
        
        $this->_profile = (bool) $profile;
        
    }

    /**
     * Get the PDO connection or fetch the default one if it doesn't exist
     * @return \PDO 
     */
    function getConnection() {
        return $this->_connection;
    }
    
     /**
     * Get the PDO connection or fetch the default one if it doesn't exist
     * @return \PDO 
     */
    function setConnection(PDO $connection) {
        $this->_connection = $connection;
    }

    /**
     * Prpare and execute a full query using place holders and bound paramters. Return the executed PDOStatement
     * 
     * @param string $sql
     * @param mixed $bind
     * @return \PDOStatement 
     */
    public function query($sql, $bind = array()) {

        if ($sql instanceof Select) {
            list($sql, $bind) = $sql->get();
        }

        is_array($bind) or $bind = array($bind);

        $stmt = $this->getConnection()->prepare($sql);
        
        $start = microtime(true);
        
        try {
        	$stmt->execute($bind);
        }catch(\PDOException $e){
        	throw new \PDOException($e->getMessage() . "\n QUERY: " . $sql . "\n BIND: " . var_export($bind,1));	
        }
        
        $time = microtime(true) - $start;
        
        $this->_profile and $this->_queries[] = compact('sql','bind','time','stmt');

        return $stmt;
    }

    /**
     * Queries the database to find the columns in the given table(s)
     * 
     * @param mixed $table A string table name, or an array of string tablenames
     * @return array  An array containing the column names from the given table(s)
     */
    public function getColumnNames($table) {
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
    public function getColumnInfo($table) {
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
        
        return array_map(function($obj){
            return $obj[0];
        }, $this->query('SHOW TABLES' . $from)->fetchAll(PDO::FETCH_NUM));
    }
    
    public function showPrimaryKey($table)
    {                        
        $fields = array_filter($this->getColumnInfo($table), function($item){
            return $item['Key'] === 'PRI';
        });
        
        return array_keys($fields);
    }

    /**
     * Escape a value or array of values and bind them into an sql statement
     * 
     * @param string $string The sql statement
     * @param array|mixed $bind A single value to be bound or an array of values
     * @param string $quotedString The quoted string
     */
    public function quoteInto($sql, $bind = array()) {
        is_array($bind) or $bind = array($bind);

        foreach ($bind as $key => $value) {
            $replace = (is_numeric($key) ? '?' : ':' . $key);
            $sql = substr_replace($sql, $this->quote($value), strpos($sql, $replace), strlen($replace));
        }
        return $sql;
    }

    /**
     * Escape a value ready to be inserted into the database
     * 
     * @param string $string The unquoted string
     * @param string $quotedString The quoted string
     */
    public function quote($string) {
        
        $connection = $this->getConnection();
        
        if(is_array($string)){
            foreach($string as $k => $value){
                $string[$k] = $connection->quote($value);
            }
            return $string;
        }

        return $connection->quote($string);
    }

    /**
     * Remove all keys that don't exist as columns in the given table(s)
     * 
     * @param mixed $table
     * @param array $data The input array
     * @return array The remaining array key=>values 
     */
    public function reduceData($table, $data) {
        return array_intersect_key($data, array_flip($this->getColumnNames($table)));
    }

    /**
     *
     * @param string $sql
     * @param mixed $bind
     * @return mixed 
     */
    public function fetch($sql, $bind = array()) {
        return $this->query($sql, $bind)->fetch();
    }
    
    /**
     *
     * @param string $sql
     * @param mixed $bind
     * @return mixed 
     */
    public function fetchNumeric($sql, $bind = array()) {
        return $this->query($sql, $bind)->fetch(PDO::FETCH_NUM);
    }
    
    /**
     *
     * @param string $sql
     * @param mixed $bind
     * @return mixed 
     */
    public function fetchObject($sql, $bind = array(),$object = null) {   
        $query = $this->query($sql, $bind);
        $query->setFetchMode(PDO::FETCH_INTO,is_null($object) ? new \stdClass() : $object);
        return $query->fetch();
    }

    /**
     *
     * @param string $sql
     * @param mixed $bind
     * @return array 
     */
    public function fetchOne($sql, $bind = array()) {
        return $this->query($sql, $bind)->fetchColumn();
    }
    
    /**
     *
     * @param string $sql
     * @param mixed $bind
     * @return array 
     */
    public function fetchAll($sql, $bind = array()) {
        return $this->query($sql, $bind)->fetchAll();
    }

    /**
     *
     * @param string $table
     * @param array $data
     * @return \PDOStatement 
     */
    public function insert($table, array $data) {
        return $this->doInsert($table, $data);
    }
    
    /**
     * 
     * @param string $table
     * @param array $data
     * @return \PDOStatement
     * @throws DbException
     */
    public function insertIgnore($table, array $data){
    	return $this->doInsert($table, $data, true);
    }
    
    /**
     * 
     * @param string $table
     * @param array $data
     * @param bool $ignore Run as an insert ignore
     * @return \PDOStatement
     * @throws DbException
     */
    protected function doInsert($table, array $data, $ignore = false){
    	
    	$data = $this->reduceData($table, $data);

        if (!count($data)) {
            throw new EmptyDataset('No data in array to insert');
        }

        $cols = '';
        $qs = '';
        foreach ($data as $col => $v) {
            $cols .= $col . ',';
            $qs .= ($v instanceof Expr ? (string)$v : '?') . ',';
            if($v instanceof Expr){
                unset($data[$col]);
            }
        }
        
        $ignore = $ignore ? 'IGNORE' : '';

        $sql = 'INSERT ' . $ignore . ' INTO ' . $table . ' (' . trim($cols, ',') . ') VALUES (' . trim($qs, ',') . ')';

        return $this->query($sql, array_values($data));
    }

    /**
     * 
     * @param string $table
     * @param array $data
     * @return \PDOStatement
     * @throws DbException
     */
    public function insertOnDuplicateKeyUpdate($table, array $data) {
        $data = $this->reduceData($table, $data);

        if (!count($data)) {
            throw new EmptyDataset('No data in array to update');
        }

        // Extract column names from the array keys
        $update = $vals = $cols = array();
        foreach ($data as $col => $v) {
            $cols[] = $col;
            $vals[] = $v instanceof Expr ? (string)$v : '?';
            $update[] = $col . ' = ' . ($v instanceof Expr ? (string)$v : '?');

            if($v instanceof Expr){
                unset($data[$col]);
            }
        }

        // Build the statement
        $sql = 'INSERT INTO ' . $table . ' 
		(' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')	
		ON DUPLICATE KEY UPDATE ' . implode(', ', $update);

        return $this->query($sql, array_merge(array_values($data), array_values($data)));
    }
    
    /**
     *
     * @param string $table
     * @param array $data
     * @param string $where
     * @param mixed $bind
     * @return \PDOStatement 
     */
    public function update($table, array $data, $where = '0', $bind = array()) {

        $data = $this->reduceData($table, $data);

        if (!count($data)) {
            throw new EmptyDataset('No data in array to update');
        }

        $str = '';
        foreach ($data as $col => $v) {
            $val = $v instanceof Expr ? (string)$v : '?';
            $str .= $col . '=' . $val . ',';
            
            if($v instanceof Expr){
                unset($data[$col]);
            }
        }

        $sql = 'UPDATE ' . $table . ' SET ' . trim($str, ',') . ' WHERE ' . $where;

        $bind = array_merge($data, is_array($bind) ? $bind : array($bind));

        return $this->query($sql, array_values($bind));
    }

    /**
     * 
     * @param string $table
     * @param string $where
     * @param mixed|array $bind
     * @return \PDOStatement
     */
    public function delete($table, $where = 0, $bind = array()) {
        return $this->query('DELETE FROM ' . $table . ' WHERE ' . $where, $bind);
    }
    
    /**
     * Get the "auto increment id" of the last inserted row.
     * 
     * @return int
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    
    /**
     * Execute a Closure within a transaction.
     *
     * @param  Closure  $callback
     * @return mixed
     */
    public function transaction(\Closure $callback){
        
        $this->getConnection()->beginTransaction();
        
        $callback($this);
        
        $this->getConnection()->commit();
    }
    
    /**
     * Get the profile data of queries ran so far
     * 
     * @return array
     */
    public function getProfile(){
        return $this->_queries;
    }
    
    public static function make(array $config){
    
	    extract($config);
	
            $db = isset($database) ? ";dbname={$database}" : '';
            
            $defaults = array(
	        PDO::ATTR_ERRMODE 		=> PDO::ERRMODE_EXCEPTION,
	        PDO::ATTR_DEFAULT_FETCH_MODE 	=> PDO::FETCH_ASSOC,
	    );
	    
	    $options = isset($options) ? array_replace($defaults, $options) : $defaults;
            
	    $pdo = new PDO("mysql:host={$host}$db", $user, $password, $options);
	
	    !empty($charset) and $pdo->prepare("SET NAMES '{$charset}'")->execute();
	    
	    return new static($pdo);
    }

}
