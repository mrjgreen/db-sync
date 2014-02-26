<?php namespace DbSync\Sync;

class Replace extends SyncAbstract {
    
    protected function write($table, \PDOStatement $stmt)
    {
        return $this->destination->multiReplace($table, $stmt)->rowCount();
    }
}

