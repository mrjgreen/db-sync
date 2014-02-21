<?php namespace DbSync\Sync;

class Single {
    
    public function selectQuery($table, array $primaryKey, array $syncColumns)
    {
        $colString = implode(array_unique(array_merge($syncColumns, $primaryKey)), ',');
        
        $whereString = implode($primaryKey, ' = ? AND ') . ' = ?';
        
        return "SELECT $colString FROM $table WHERE $whereString";
    }
    
    public function insertQuery($table, array $primaryKey, array $syncColumns)
    {
        $cols = array_unique(array_merge($syncColumns, $primaryKey));
        
        $colString = '`'. implode($cols, '`,`') . '`';
        
        $updateString = '';
        
        foreach($cols as $col)
        {
            $updateString .= '`'.$col . '` = ?, ';
        }
        
        $placeHolders = trim(str_repeat('?,', count($cols)), ',');
                
        return "INSERT INTO $table ($colString) VALUES($placeHolders) ON DUPLICATE KEY UPDATE " . substr($updateString, 0, -2);
    }
}

