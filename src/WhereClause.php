<?php namespace DbSync;

class WhereClause {

    private $where;

    private $bindings;

    public function __construct($where, array $bindings = array())
    {
        $this->where = $where;

        $this->bindings = $bindings;
    }

    public function getWhere()
    {
        return $this->where;
    }

    public function getBindings()
    {
        return $this->bindings;
    }
}