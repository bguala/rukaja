<?php
class dt_tipo_asignacion extends toba_datos_tabla
{
	function get_descripciones()
	{
		$sql = "SELECT tipo, tipo FROM tipo_asignacion ORDER BY tipo";
		return toba::db('rukaja')->consultar($sql);
	}




}
?>