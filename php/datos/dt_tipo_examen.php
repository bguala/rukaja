<?php
class dt_tipo_examen extends toba_datos_tabla{

    function get_listado (){
        $sql="SELECT turno, turno || '  ' || '(' || tipo || ')' as descripcion
              FROM tipo_examen t_te
              ";
        return toba::db('rukaja')->consultar($sql);
    }
}
?>