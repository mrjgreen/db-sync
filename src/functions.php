<?php namespace DbSync;

/**
* Pluck an array of values from an array.
*
* @param  array   $array
* @param  string  $value
* @param  string  $key
* @return array
*/
function array_pluck($array, $value, $key = null)
{
       $results = array();

       foreach ($array as $item)
       {
               $itemValue = is_object($item) ? $item->{$value} : $item[$value];

               // If the key is "null", we will just append the value to the array and keep
               // looping. Otherwise we will key the array using the value of the key we
               // received from the developer. Then we'll return the final array form.
               if (is_null($key))
               {
                       $results[] = $itemValue;
               }
               else
               {
                       $itemKey = is_object($item) ? $item->{$key} : $item[$key];

                       $results[$itemKey] = $itemValue;
               }
       }

       return $results;
}
