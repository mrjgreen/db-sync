<?php namespace DbSync;

class Definition {

    private $columns;

    private $primaryKey;

    public function __construct($columns, $primaryKey)
    {
        $this->columns = $columns;

        $this->primaryKey = $primaryKey;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getColumns()
    {
        return $this->columns;
    }
}
