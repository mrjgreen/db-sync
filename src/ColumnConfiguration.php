<?php namespace DbSync;

class ColumnConfiguration {

    private $except;

    private $only;

    public function __construct(array $only, array $except)
    {
        $this->only = $only;

        $this->except = $except;
    }

    public function getIntersection(array $columns, array $merge = [])
    {
        $columns = array_values($columns);
        $merge = array_values($merge);

        $comparisonFunc = function($a, $b){
            if(strtolower($a) === strtolower($b)) return 0;
            return strtolower($a) > strtolower($b) ? 1 : -1;
        };

        if(count($this->only))
        {
            $columns = array_uintersect($columns, $this->only, $comparisonFunc);
        }

        if(count($this->except))
        {
            $columns = array_udiff($columns, $this->except, $comparisonFunc);
        }

        return $this->arrayUniqueCI(array_merge($merge, $columns));
    }

    private function arrayUniqueCI($array)
    {
        return array_values(array_intersect_key($array, array_unique(array_map("strtolower",$array))));
    }
}
