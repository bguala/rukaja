<?php
class dt_administrador extends toba_datos_tabla {
    
    function get_correo_electronico ($id_sede){
        $sql="SELECT correo_electronico
              FROM administrador 
              WHERE id_sede=$id_sede";
        
        return toba::db('rukaja')->consultar($sql);
    }
    
    function get_email ($nombre_usuario){
        $sql="SELECT correo_electronico
              FROM administrador 
              WHERE nombre_usuario='$nombre_usuario'";
        
        return toba::db('rukaja')->consultar($sql);
    }
	function get_listado()
	{
		$sql = "SELECT
			t_a.nombre_usuario,
			t_s.descripcion as id_sede_nombre,
			t_a.id_administrador,
			t_a.correo_electronico,
			t_a.nombre,
			t_a.apellido
		FROM
			administrador as t_a,
			sede as t_s
		WHERE
				t_a.id_sede = t_s.id_sede
		ORDER BY nombre";
		return toba::db('rukaja')->consultar($sql);
	}

}
?>