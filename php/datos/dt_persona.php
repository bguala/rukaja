<?php
class dt_persona extends toba_datos_tabla
{
	function get_listado()
	{
		$sql = "SELECT
			    t_p.nro_doc,
			    t_p.tipo_doc,
			    t_p.nombre,
			    t_p.apellido,
			    t_p.telefono,
			    t_p.correo_electronico,
			    t_p.domicilio,
			    t_p.ciudad
                        FROM
                            persona as t_p
                        ORDER BY nombre";
		return toba::db('rukaja')->consultar($sql);
	}
        
        /*
         * Esta funcion se utiliza para cargar el cuadro_docentes en la operacion 'Cargar Asignaciones' y 
         * 'Solicitar Aula'.
         */
        function get_docentes ($where){
            $sql="SELECT 
                      nro_docum as nro_doc,
                      tipo_docum as tipo_doc,
                      nombre,
                      apellido,
                      legajo,
                      id_docente 
                  FROM
                      docente 
                  WHERE $where";
            
            return toba::db('mocovi')->consultar($sql);
            
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Solicitar Aula' para editar una solicitud.
         */
        function get_datos_docente ($legajo){
            $sql="SELECT *
                  FROM 
                      docente 
                  WHERE legajo=$legajo";
            return toba::db('mocovi')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Solicitar Aula' para editar una solicitud.
         */
        function get_datos_organizacion ($id_organizacion){
            $sql="SELECT *
                  FROM 
                      organizacion 
                  WHERE id_organizacion=$id_organizacion";
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza para buscar personas u organizaciones registradas en el sistema, pueden ser 
         * docentes, no docentes, entidades externas. Se usa en la operacion Cargar Asignaciones para cargar 
         * el cuadro_personas de la pantalla pant_edicion.
         * @org : puede ser NULL ya que puede no estar presente en el arreglo de clausulas sql. Por eso utilizamos
         * isset para hacer el chequeo.
         */
        function get_personas ($docente, $org){
            $registros=array();
            if(strlen($docente)>0 && !isset($org)){//$docente posee datos, $org es null.
                $sql_docente="SELECT 
                                  nombre,
                                  apellido,
                                  legajo,
                                  nro_docum as nro_doc,
                                  tipo_docum as tipo_doc,
                                  id_docente as id_responsable_aula,
                                  'Docente' as tipo_agente
                              FROM
                                  docente 
                              WHERE $docente";
                      
                $registros=toba::db('mocovi')->consultar($sql_docente);
            }else{
                if(strlen($docente)==0 && isset($org)){//Solamente traemos datos en $org.
                    $sql_org="SELECT 
                                  nombre,
                                  ' '        as apellido,
                                  '--------' as legajo,
                                  '--------' as nro_doc,
                                  '--------' as tipo_doc,
                                  id_organizacion as id_responsable_aula,
                                  Org' as tipo_agente
                              FROM
                                  organizacion 
                              WHERE $org";
                    $registros=toba::db('rukaja')->consultar($sql_org);
                }else{
                    if(strlen($docente)>0 && isset($org)){//Traemos datos en ambas variables.
                        $sql_docente="SELECT 
                                          nombre,
                                          apellido,
                                          legajo,
                                          nro_docum as nro_doc,
                                          tipo_docum as tipo_doc,
                                          id_docente as id_responsable_aula,
                                          'Docente' as tipo_agente
                                      FROM
                                          docente 
                                      WHERE $docente";
                      
                        $registros=toba::db('mocovi')->consultar($sql_docente);
                        
                        $sql_org="SELECT 
                                      nombre,
                                      '--------' as apellido,
                                      '--------' as legajo,
                                      '--------' as nro_doc,
                                      '--------' as tipo_doc,
                                      id_organizacion as id_responsable_aula,
                                      'Org' as tipo_agente
                                  FROM
                                      organizacion 
                                  WHERE $org";
                        $organizaciones=toba::db('rukaja')->consultar($sql_org);
                        //Reemplazamos a la union sql.
                        $this->unificar_registros(&$registros, $organizaciones);
                    }
                }
                
            }
            return $registros;
        }
        
        /*
         * Esta funcion realiza una union entre conjuntos. Periodo se debe pasar por referencia.
         */
        function unificar_registros ($periodo, $definitiva){
            foreach ($definitiva as $clave=>$valor){
                if(isset($valor)){
                   $periodo[]=$valor; //Agrega al final.
                }
            }            
        }
        
        function get_equipo_de_catedra($id_asignacion){
            $sql="SELECT 
                      t_c.nombre || ' ' || t_c.apellido as miembro
                  FROM 
                      catedra t_c
                  WHERE t_c.id_asignacion=$id_asignacion";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_catedra ($id_asignacion){
            $sql="SELECT *, 'MIEMBRO' as tipo
                  FROM 
                      catedra  
                  WHERE id_asignacion=$id_asignacion";
            
            return toba::db('rukaja')->consultar($sql);
        }
                      
                
}
?>