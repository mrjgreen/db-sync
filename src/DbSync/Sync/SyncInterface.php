<?php namespace DbSync\Sync;

interface SyncInterface {
    
    public function sync($table, $select);
}

