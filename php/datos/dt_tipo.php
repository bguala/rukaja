<?php
class dt_tipo extends toba_datos_tabla
{
	function get_descripciones()
	{
		$sql = "SELECT id_tipo, descripcion FROM tipo ORDER BY descripcion";
		return toba::db('rukaja')->consultar($sql);
	}









}
?>