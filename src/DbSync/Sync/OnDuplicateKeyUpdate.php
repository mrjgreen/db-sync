<?php namespace DbSync\Sync;

class OnDuplicateKeyUpdate extends SyncAbstract {
    
    protected function write($table, \PDOStatement $stmt)
    {
        $this->destination->multiInsertOnDuplicateKeyUpdate($table, $stmt);
    }
}

