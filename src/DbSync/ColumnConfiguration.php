<?php namespace DbSync;

class ColumnConfiguration {

    private $except;

    private $only;

    public function __construct(array $only, array $except)
    {
        $this->only = $only;

        $this->except = $except;
    }

    public function getIntersection(array $columns)
    {
        if(count($this->only))
        {
            $columns = array_intersect($columns, $this->only);
        }

        if(count($this->except))
        {
            $columns = array_intersect($columns, $this->except);
        }

        return array_unique($columns);
    }
}