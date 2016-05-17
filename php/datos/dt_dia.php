<?php

class dt_dia extends toba_datos_tabla {
    
    function get_dias (){
        $sql="SELECT nombre
              FROM dia
              ORDER BY orden";
        
        return toba::db('rukaja')->consultar($sql);
    }
    
}

?>

