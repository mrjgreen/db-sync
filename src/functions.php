<?php namespace DbSync;

function implode_identifiers(array $columns)
{
    $columns = array_map(function($identifier){
        
        $parts = explode('.', $identifier);
        
        foreach ($parts as $i => $part) {
            $parts[$i] = '`' . str_replace('`', '\\`', $part) . '`';
        }
        
        return implode('.', $parts);
    }, $columns);
    
    return implode(',', $columns);
}
