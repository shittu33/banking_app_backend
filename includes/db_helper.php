<?php

class DBH{
    /**
     * 
     *This method help generate insert query strings
     *@param String $table_name the fist param is table name and second is 
     *@* @param Array  $column_assoc an associative array of column and values
     *$column_assoc =["id"=> 1,"name"=> "Olawale"];
     *
     */
    public static function insert(String $table_name,Array $column_assoc){
    
        $sql = "INSERT INTO `$table_name` (";
    
        # add column name to statement
    foreach ($column_assoc as $key => $value) {
        // $index= array_search($key,array_keys($column_assoc));
        // $count = count($column_assoc)-1;
     
        $sql.= "`$key`" . ", ";
    }
    
    //remove the last two characters (which is ", ") and replace it with parenthesis 
    $sql = substr($sql,0,-2).")";

    $sql.=" VALUES ( ";

    # add column values to statement
    foreach ($column_assoc as $value) {
        // $index =array_search($value,array_values($column_assoc));
        // $last = $index == count($column_assoc)-1;
        $sql.=($value==CURRENT_DATE?"$value":"'$value'"). ", ";
        // $sql.="'$value'". ", ";
    }

    //remove the last two characters (which is ", ") and replace it with parenthesis 
    $sql = substr($sql,0,-2).")";

        return $sql;
    }

   
}   