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

/*
 *
function implode_identifiers(array $columns, $safe = array('as', '.'))
{
    $columns = array_map(function($identifier) use($safe){
        
        $parts = preg_split('#([\.\s])#', $identifier, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $i => $part) {
            in_array(strtolower($part), $safe) or $parts[$i] = '`' . str_replace('`', '\\`', $part) . '`';
        }

        return implode('', $parts);
    
    }, $columns);
    
    return implode(',', $columns);
}
 * 
 */
