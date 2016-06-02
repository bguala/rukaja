<?php
class dt_organizacion extends toba_datos_tabla
{
	function get_listado()
	{
		$sql = "SELECT
			t_o.id_organizacion,
			t_o.nombre,
			t_o.telefono,
			t_o.email
		FROM
			organizacion as t_o
		ORDER BY nombre";
		return toba::db('rukaja')->consultar($sql);
	}
        
        function get_organizaciones ($where){
            
            $sql="SELECT *
                  FROM organizacion 
                  WHERE $where";
            
            return toba::db('rukaja')->consultar($sql);
            
        }
        
        /*
         * Esta funcion se utiliza en la operacion Solicitar/Reservar Aula para verificar si una organizacion 
         * ya existe en el sistema.
         */
        function get_organizacion ($id_organizacion){
            $sql="SELECT *
                  FROM organizacion 
                  WHERE id_organizacion=$id_organizacion";
            return toba::db('rukaja')->consultar($sql);
        }

}

?>