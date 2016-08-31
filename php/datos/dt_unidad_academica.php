<?php
class dt_unidad_academica extends toba_datos_tabla
{
	function get_listado()
	{
		$sql = "SELECT
			    t_ua.sigla,
			    t_ua.descripcion
		        FROM
			    unidad_academica as t_ua
		        ORDER BY descripcion";
		return toba::db('rukaja')->consultar($sql);
	}
        
        function get_unidad_academica_mas_sede ($sigla, $id_sede){
            $sql="SELECT 
                      t_a.descripcion as facultad, t_s.descripcion as sede
                  FROM
                      unidad_academica t_a, sede t_s 
                  WHERE t_a.sigla='$sigla' 
                        AND t_s.id_sede=$id_sede";
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Solicitar/Reservar Aula. Permite obtener todos los 
         * establecimientos registrados en el sistema para iniciar un calculo masivo de horarios disponibles.
         */
        function get_unidades_academicas (){
            $sql="SELECT 
                      t_ua.sigla, t_ua.descripcion as establecimiento, 
                      t_s.id_sede, t_s.descripcion as sede
                  FROM
                      unidad_academica t_ua
                           JOIN sede t_s ON (t_ua.sigla=t_s.sigla)";
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Solicitar/Reservar Aula. Para obtener el establecimiento al que
         * pertenece un determinado usuario. 
         */
        function get_unidad_academica ($id_sede){
            $sql="SELECT 
                      t_s.descripcion as sede, t_ua.descripcion as establecimiento, t_s.sigla
                  FROM
                      sede t_s
                          JOIN unidad_academica t_ua ON (t_s.sigla=t_ua.sigla)
                  WHERE t_s.id_sede=$id_sede";
            return toba::db('rukaja')->consultar($sql);
        }

}

?>