<?php
class dt_sede extends toba_datos_tabla
{
	function get_descripciones()
	{
		$sql = "SELECT id_sede, descripcion FROM sede ORDER BY descripcion";
		return toba::db('rukaja')->consultar($sql);
	}
        
        function get_sedes ($sigla){
            $sql="SELECT id_sede, descripcion
                  FROM sede 
                  WHERE sigla='$sigla'";
            
            return toba::db('rukaja')->consultar($sql);
        }       

}

?>