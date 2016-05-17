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
            $sql="SELECT t_a.descripcion as facultad, t_s.descripcion as sede
                  FROM unidad_academica t_a, sede t_s 
                  WHERE t_a.sigla='$sigla' AND t_s.id_sede=$id_sede";
            return toba::db('rukaja')->consultar($sql);
        }

}

?>