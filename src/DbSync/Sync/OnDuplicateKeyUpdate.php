<?php namespace DbSync\Sync;

class OnDuplicateKeyUpdate extends SyncAbstract {
    
    public function sync($table, $select)
    {
        foreach($this->source->query($select) as $row)
        {
            $this->destination->insertOnDuplicateKeyUpdate($table, $row);
        }
    }
}

