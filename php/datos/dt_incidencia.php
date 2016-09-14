<?php
class dt_incidencia extends toba_datos_tabla
{
	function get_listado()
	{
		$sql = "SELECT
			t_i.id_incidencia,
			t_i.fecha,
			t_i.hora,
			t_i.estado
		FROM
			incidencia as t_i
		ORDER BY estado";
		return toba::db('rukaja')->consultar($sql);
	}

}

?>