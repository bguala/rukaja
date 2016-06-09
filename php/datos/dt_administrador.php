<?php

class dt_administrador extends toba_datos_tabla {
    
    function get_correo_electronico ($id_sede){
        $sql="SELECT correo_electronico
              FROM administrador 
              WHERE id_sede=$id_sede";
        
        return toba::db('rukaja')->consultar($sql);
    }
}

?>