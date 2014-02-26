<?php namespace DbSync\Sync;

class Replace extends SyncAbstract {
    
    protected function write($table, \PDOStatement $stmt)
    {
        $this->destination->multiReplace($table, $stmt);
    }
}

