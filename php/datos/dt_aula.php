<?php
class dt_aula extends toba_datos_tabla
{
	function get_descripciones()
	{
		$sql = "SELECT 
                            id_aula, nombre
                        FROM
                            aula
                        ORDER BY nombre";
		return toba::db('rukaja')->consultar($sql);
	}
        
        /*
         * quote le asigna comillas a una expresion para que pueda ser utilizada en una consulta
         */
        function get_listado($where)
	{
            $sql = "SELECT
                        t_a.id_aula,
                        t_a.capacidad,
                        t_a.nombre as aula,
                        t_a.ubicacion
                    FROM
                        aula as t_a

                    WHERE
                         $where
                    ORDER BY nombre";
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_aulas ($id_sede){
            $sql="SELECT 
                      t_a.id_aula, 
                      t_a.capacidad,
                      t_a.nombre as aula,
                      t_a.ubicacion,
                      t_t.descripcion as id_tipo_nombre,
                      t_s.descripcion as id_sede_nombre 
                  FROM
                      aula t_a
                          JOIN tipo t_t ON (t_a.id_tipo=t_t.id_tipo) 
                          JOIN sede t_s ON (t_a.id_sede=t_s.id_sede)
                  WHERE t_a.id_sede=$id_sede 
                        AND (NOT t_a.eliminada)
                  ORDER BY nombre";
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_aulas_por_sede ($id_sede){
            $sql="SELECT 
                      nombre as aula, id_aula, capacidad
                  FROM 
                      aula 
                  WHERE id_sede=$id_sede 
                        AND (NOT eliminada)";
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_unidades_academicas (){
            $sql="SELECT 
                      t_ua.sigla, t_ua.descripcion
                  FROM 
                      unidad_academica t_ua 
                  WHERE t_ua.sigla <> 'BIBLIO' ";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_sedes ($sigla){
            $sql="SELECT 
                      t_s.id_sede, t_s.descripcion
                  FROM
                      sede t_s 
                  WHERE t_s.sigla='$sigla'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_dias_semana (){
            $fecha=  getdate();
            $anio=$fecha['year'];
            
            $sql="SELECT
                      nombre
                  FROM
                      dia 
                  WHERE anio=$anio";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_aulas_con_capacidad ($id_sede, $capacidad){
            $sql="SELECT 
                      nombre as aula, capacidad, id_aula
                  FROM
                      aula 
                  WHERE id_sede=$id_sede 
                        AND (capacidad >= $capacidad) AND (NOT eliminada)";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion devuelve las aulas de una sede con su capacidad.
         * Se utiliza en la operacion Generar Solicitud.
         */
        function get_aulas_mas_capacidad ($id_sede){
            $sql="SELECT 
                      id_aula, capacidad
                  FROM
                      aula
                  WHERE id_sede=$id_sede 
                        AND (NOT eliminada)";
            return toba::db('rukaja')->consultar($sql);
        }


}
?>