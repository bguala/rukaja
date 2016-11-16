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
                  FROM 
                      organizacion 
                  WHERE $where";
            
            return toba::db('rukaja')->consultar($sql);
            
        }
        
        /*
         * Esta funcion se utiliza en la operacion Solicitar/Reservar Aula para verificar si una organizacion 
         * ya existe en el sistema.
         */
        function get_organizacion ($nombre_org, $telefono_org, $email_org){
            $sql="SELECT *
                  FROM 
                      organizacion 
                  WHERE nombre='$nombre_org' 
                        AND telefono='$telefono_org' 
                        AND email='$email_org'";
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion permite obtener los datos de una organizacion a partir de su id_ para autocompletar un 
         * formulario utilizado para editar.
         */
        function get_organizacion_id ($id_){
            $sql="SELECT *
                  FROM
                      organizacion
                  WHERE id_organizacion=$id_";
            
            return toba::db('rukaja')->consultar($sql);
        }

}
?>