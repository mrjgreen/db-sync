<?php namespace DbSync\Sync;

class Replace extends SyncAbstract {

    public function sync($table, $select)
    {
        $this->destination->multiReplace($table, $this->source->query($select));
    }
}

