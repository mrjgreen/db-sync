<?php namespace DbSync\Sync;

class OnDuplicateKeyUpdate extends SyncAbstract {
    
    public function sync($table, $select)
    {
        $this->destination->multiInsertOnDuplicateKeyUpdate($table, $this->source->query($select));
    }
}

