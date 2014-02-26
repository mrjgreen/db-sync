<?php namespace DbSync\Sync;

class OnDuplicateKeyUpdate extends SyncAbstract {
    
    protected function write($table, \PDOStatement $stmt)
    {
        return $this->destination->multiInsertOnDuplicateKeyUpdate($table, $stmt)->rowCount();
    }
}

