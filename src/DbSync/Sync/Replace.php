<?php namespace DbSync\Sync;

class Replace extends SyncAbstract {

    public function sync($table, $select)
    {
        $query = $this->source->query($select);
        
        if($query->rowCount() > 0)
        {
            $this->destination->multiReplace($table, $query);
        }
    }
}

