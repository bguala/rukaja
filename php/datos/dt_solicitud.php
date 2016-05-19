<?php
class dt_solicitud extends toba_datos_tabla
{
        /*
         * Esta funcion se utiliza en la operacion Ver Solicitudes, para ver los pedidos de aula en un
         * establecimiento en particular.
         */
	function get_listado_solicitudes($id_sede)
	{
		$sql = "SELECT
			t_s.id_solicitud,
			t_s.nombre,
			t_s.apellido,
                        t_s.legajo,
			t_s.fecha,
                        t_s.facultad,
			t_s.capacidad,
			t_s.finalidad,
			t_s.hora_inicio,
			t_s.hora_fin,
			t_s.id_aula,
                        t_s.tipo,
                        t_s.id_sede,
			t_s1.descripcion as id_sede_nombre
		FROM
			solicitud as t_s,
			sede as t_s1
		WHERE
		        t_s.id_sede=t_s1.id_sede AND t_s.estado='Pendiente' AND t_s.tipo AND t_s.id_sede=$id_sede 
		ORDER BY nombre";
                
		return toba::db('rukaja')->consultar($sql);
	}
        
        function get_listado_denuncias ($id_sede){
            $sql="SELECT t_s.nombre, 
                         t_s.apellido, 
                         t_s.id_solicitud,
                         t_s.fecha, 
                         t_s.hora_inicio, 
                         t_s.hora_fin, 
                         t_s.id_aula, 
                         t_s1.descripcion 
                         FROM solicitud t_s, sede t_s1 
                         WHERE t_s.id_sede=$id_sede AND $id_sede=t_s1.id_sede AND t_s.estado='Pendiente' AND (NOT t_s.tipo)";
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Ver Solicitudes.
         */
        function get_datos_solicitud ($id_solicitud){
            $sql="SELECT *
                  FROM solicitud 
                  WHERE id_solicitud=$id_solicitud";
            $solicitud=toba::db('rukaja')->consultar($sql);
            
            return ($solicitud[0]);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Ver Solicitudes, para obtener datos basicos de una solicitud.
         * Se usa para armar un reporte pdf.
         */
        function get_datos_basicos_solicitud ($id_solicitud){
            $sql="SELECT finalidad, nombre, apellido
                  FROM solicitud 
                  WHERE id_solicitud=$id_solicitud";
            
            return (toba::db('rukaja')->consultar($sql));
        }


	function get_listado()
	{
		$sql = "SELECT
			t_s.id_solicitud,
			t_s.nombre,
			t_s.apellido,
			t_s.fecha,
			t_s.capacidad,
			t_s.finalidad,
			t_s.hora_inicio,
			t_s.hora_fin,
			t_s.tipo,
			t_s1.descripcion as id_sede_nombre,
			t_s.estado,
			t_s.legajo,
			t_s.id_aula,
			t_s.facultad
		FROM
			solicitud as t_s,
			sede as t_s1
		WHERE
				t_s.id_sede = t_s1.id_sede
		ORDER BY nombre";
		return toba::db('rukaja')->consultar($sql);
	}

}
?>