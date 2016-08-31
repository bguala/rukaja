<?php
class dt_sede extends toba_datos_tabla
{
	function get_descripciones()
	{
		$sql = "SELECT 
                            id_sede,
                            descripcion 
                        FROM 
                            sede
                        ORDER BY descripcion
                        
                        ";
		return toba::db('rukaja')->consultar($sql);
	}
        
        /*
         * Esta funcion se utiliza para cargar el combo sede en cascada.
         */
        function get_sedes ($sigla){
            $sql="SELECT 
                      id_sede,
                      descripcion
                  FROM 
                      sede 
                  WHERE 
                       sigla='$sigla'
                           
                   ";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en todas las operaciones del sistema. Se utiliza para recuperar
         * el id_sede necesario para hacer consultas sql.
         */
        function get_id_sede (){
            $sql="SELECT 
                      id_sede
                  FROM
                      sede 
                  ";
            
            $sql=toba::perfil_de_datos()->filtrar($sql);
            
            $id_sede=toba::db('rukaja')->consultar($sql);
            
            return ($id_sede[0]['id_sede']);
        }

}

?>