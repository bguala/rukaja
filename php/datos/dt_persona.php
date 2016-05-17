<?php
class dt_persona extends toba_datos_tabla
{
	function get_listado()
	{
		$sql = "SELECT
			t_p.nro_doc,
			t_p.tipo_doc,
			t_p.nombre,
			t_p.apellido,
			t_p.telefono,
			t_p.correo_electronico,
			t_p.domicilio,
			t_p.ciudad
		FROM
			persona as t_p
		ORDER BY nombre";
		return toba::db('rukaja')->consultar($sql);
	}

        
        function get_correo_electronico ($id_sede){
            $sql="SELECT t_p.correo_electronico 
                  FROM persona t_p 
                  JOIN administrador t_a ON (t_p.nro_doc=t_a.nro_doc AND t_p.tipo_doc=t_a.tipo_doc AND t_a.id_sede=$id_sede)";
            $correo=toba::db('rukaja')->consultar($sql);
            
            return ($correo[0]['correo_electronico']);
        }
        
        /*
         * Esta funcion se utiliza para cargar el cuadro_docentes en la operacion Cargar Asignaciones.
         */
        function get_docentes ($where){
            $sql="SELECT t_p.nro_doc,
                         t_p.tipo_doc,
                         t_p.nombre,
                         t_p.apellido,
                         t_d.legajo
                  FROM persona t_p 
                  JOIN docente t_d ON (t_p.nro_doc=t_d.nro_doc AND t_p.tipo_doc=t_d.tipo_doc)
                  WHERE $where";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza para buscar personas registradas en el sistema, pueden ser docentes y 
         * no docentes. Se usa en la operacion Cargar Asignaciones para cargar el cuadro_personas de la 
         * pantalla pant_persona.
         */
        function get_personas ($where){
            
            $sql="(SELECT t_p.nombre,
                         t_p.apellido,
                         t_p.nro_doc,
                         t_p.tipo_doc
                  FROM persona t_p
                  JOIN docente t_d ON (t_p.nro_doc=t_d.nro_doc AND t_p.tipo_doc=t_d.tipo_doc)
                  WHERE $where)
                      
                  UNION 
                  
                  (SELECT t_p.nombre,
                          t_p.apellido,
                          t_p.nro_doc,
                          t_p.tipo_doc
                  FROM persona t_p
                  WHERE $where)";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza para obtener la sede a la que pertenece el usuario logueado en la 
         * sesion actual.
         */
        function get_sede_para_usuario_logueado ($nombre_usuario){
            $sql="SELECT id_sede
                  FROM administrador 
                  WHERE nombre_usuario='$nombre_usuario'";
            $sede=toba::db('rukaja')->consultar($sql);
            
            return ($sede[0]['id_sede']);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Ver Solicitudes, para obtener los datos del emisor de
         * correo electronico.
         */
        function get_datos_emisor ($nombre_usuario){
            $sql="SELECT (t_p.nombre || t_p.apellido) as responsable, t_ua.descripcion as establecimiento
                  FROM administrador t_a
                  JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                  JOIN sede t_s ON (t_a.id_sede=t_s.id_sede)
                  JOIN unidad_academica t_ua ON (t_s.sigla=t_ua.sigla)
                  WHERE t_a.nombre_usuario='$nombre_usuario'";
            
            return (toba::db('rukaja')->consultar($sql));
        }
        
        /*
         * 
         */
        function get_datos_responsable_aulas ($id_sede){
            $sql="SELECT t_p.nombre, t_p.apellido
                  FROM persona t_p 
                  JOIN administrador t_a ON (t_p.nro_doc=t_a.nro_doc AND t_p.tipo_doc=t_a.tipo_doc) 
                  WHERE t_a.id_sede=$id_sede";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
}
?>