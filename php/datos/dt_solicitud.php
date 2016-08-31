<?php
class dt_solicitud extends toba_datos_tabla
{
        /*
         * Modificamos la consulta generada por el framework.
         * 
         * Esta funcion se utiliza en la operacion Ver Solicitudes, para ver los pedidos de aula en un
         * establecimiento en particular. Existen dos tipos de reservas/solicitudes/pedidos de aula: las que 
         * nosotros hicimos a otra dependencia, o la que nos hicieron desde otras dependencias.
         * 
         * Si elegimos la opcion 'Solicitudes de aula realizadas a otras dependencias' debemos usar en la consulta
         * sql el id_sede del usuario logueado. Si utilizamos el atributo id_sede_origen de la tabla solicitud
         * estamos obteniendo todas las solicitudes que nosotros hicimos en otros establecimientos.
         * Enviamos todas las solicitudes en estado pendiente o finalizada, para su posterior edicion.
         * 
         * Si elegimos la opcion 'Solicitudes realizadas en su dependencia' tambien debemos usar en la consulta sql
         * el id_sede del usuario logueado. Entonces:
         * @$id_sede : contiene el id_sede del usuario logueado. Si utilizamos el atributo id_sede de la tabla 
         * solicitud estamos obteniendo las solicitudes que nos hicieron desde otras dependencias.
         * El id_sede_origen y el establecimiento obtenidos equivalen a saber quien hizo el pedido de aula.
         * @$fecha: contiene la fecha actual.
         * Estas solicitudes se pueden conceder o no, segun la disponibilidad horaria.
         *         
         */
	function get_solicitudes($id_sede, $fecha, $tipo)
	{
            //Armamos los where correspondientes.
            if($tipo == 1){ //Solicitudes a otras dependencias.
                $where_a=" t_s.id_sede=t_s1.id_sede AND t_s.id_sede_origen=$id_sede --id_sede_origen
                           AND t_s.fecha>='$fecha' AND t_a.id_aula=t_s.id_aula ";

            }else{ //Solicitudes en nuestra dependencia.
                $where_a=" t_s.id_sede=t_s1.id_sede AND t_s.id_sede=$id_sede
                           AND t_s.fecha>='$fecha' AND t_a.id_aula=t_s.id_aula AND (t_s.estado <> 'FINALIZADA') ";

            }

            $sql = "(SELECT
                            t_s.*, t_ua.descripcion as establecimiento, t_s1.descripcion as sede,
                            t_a.id_aula, t_a.nombre as aula, t_s.fecha as fecha_fin

                     FROM
                            solicitud as t_s,
                            sede as t_s1,
                            unidad_academica t_ua,
                            aula t_a

                     WHERE
                            $where_a 
                            AND (t_s.tipo = 'UNICO')
                            AND (t_s1.sigla=t_ua.sigla)
                     ORDER BY nombre) UNION 

                    (SELECT
                            t_s.*, t_ua.descripcion as establecimiento, t_s1.descripcion as sede,
                            t_a.id_aula, t_a.nombre as aula, t_me.fecha_fin

                     FROM
                            solicitud as t_s,
                            sede as t_s1,
                            solicitud_multi_evento as t_me,
                            unidad_academica t_ua,
                            aula t_a

                     WHERE
                            $where_a 
                            AND t_me.id_solicitud=t_s.id_solicitud 
                            AND (t_s.tipo = 'MULTI') 
                            AND (t_s1.sigla=t_ua.sigla)
                     ORDER BY nombre)


                    ";

            return toba::db('rukaja')->consultar($sql);
	}
                
//        function get_listado_denuncias ($id_sede){
//            $sql="SELECT t_s.nombre, 
//                         t_s.apellido, 
//                         t_s.id_solicitud,
//                         t_s.fecha, 
//                         t_s.hora_inicio, 
//                         t_s.hora_fin, 
//                         t_s.id_aula, 
//                         t_s1.descripcion 
//                         FROM solicitud t_s, sede t_s1 
//                         WHERE t_s.id_sede=$id_sede AND $id_sede=t_s1.id_sede AND t_s.estado='Pendiente' AND (NOT t_s.tipo)";
//            return toba::db('rukaja')->consultar($sql);
//        }
        
        /*
         * Esta funcion se utiliza en la operacion Ver Solicitudes.
         */
        function get_datos_solicitud ($id_solicitud){
            $sql="SELECT *
                  FROM 
                      solicitud 
                  WHERE id_solicitud=$id_solicitud
                      
                  ";
            $solicitud=toba::db('rukaja')->consultar($sql);
            
            return ($solicitud[0]);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Ver Solicitudes, para obtener datos basicos de una solicitud.
         * Se usa para armar un reporte pdf.
         */
        function get_datos_basicos_solicitud ($id_solicitud){
            $sql="SELECT 
                      finalidad, nombre, apellido
                  FROM 
                      solicitud 
                  WHERE id_solicitud=$id_solicitud
                      
                  ";
            
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
        
        /*
         * Esta funcion permite obtener la lista de fechas pertenecientes a un periodo. Se utiliza en la
         * operacion Ver Solicitudes.
         */
        function get_lista_fechas ($id_solicitud){
            $sql="SELECT * 
                  FROM 
                      multi_evento 
                  WHERE id_solicitud=$id_solicitud
                      
                  ";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_lista_dias ($id_solicitud){
            $sql="SELECT DISTINCT nombre
                  FROM 
                      multi_evento 
                  WHERE id_solicitud=$id_solicitud
                      
                  ";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_datos_multi ($id_solicitud){
            $sql="SELECT 
                      fecha_fin
                  FROM
                      solicitud_multi_evento 
                  WHERE id_solicitud=$id_solicitud
                      
                  ";
            
            $fecha=toba::db('rukaja')->consultar($sql);
            
            return ($fecha[0]['fecha_fin']);
        }

}
?>