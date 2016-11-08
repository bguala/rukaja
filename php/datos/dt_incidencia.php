<?php
class dt_incidencia extends toba_datos_tabla
{
        /*
         * Esta funcion se utiliza en la operacion 'Novedades de Bedelia' para obtener todas las incidencias 
         * en estado pendiente o iniciada. 
         */
	function get_listado($id_sede)
	{
		$sql = "SELECT
			t_i.id_incidencia,
			t_i.fecha,
			t_i.hora,
			t_i.estado,
			t_i.id_aula,
                        t_au.nombre,
			t_i.id_sede
		FROM
			incidencia as t_i
                JOIN aula t_au ON (t_i.id_aula=t_au.id_aula)
                WHERE t_i.id_sede=$id_sede 
                      AND t_i.estado <> 'FINALIZADA'
		ORDER BY estado";
		return toba::db('rukaja')->consultar($sql);
	}

        /*
         * Esta operacion se utiliza en la operacion 'Novedades de Bedelia'.
         */
        function get_cantidad_material ($id_incidencia){
            $sql="SELECT *
                  FROM cantidad_material 
                  WHERE id_incidencia=$id_incidencia";
            
            return toba::db('rukaja')->consultar($sql);
        }


}
?>