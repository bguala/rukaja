<?php
class dt_asignacion extends toba_datos_tabla
{
	function get_listado()
	{
		$sql = "SELECT
			t_a.id_asignacion,
			t_a.finalidad,
			t_a.descripcion,
			t_a.hora_inicio,
			t_a.hora_fin,
			t_a.cantidad_alumnos,
			t_a.facultad,
			t_a.nro_doc,
			t_a.tipo_doc,
			t_a1.nombre as id_aula_nombre,
			t_a.modulo,
			t_ta.tipo as tipo_asignacion_nombre,
			t_p.id_periodo as id_periodo_nombre
		FROM
			asignacion as t_a	LEFT OUTER JOIN aula as t_a1 ON (t_a.id_aula = t_a1.id_aula)
			LEFT OUTER JOIN tipo_asignacion as t_ta ON (t_a.tipo_asignacion = t_ta.tipo)
			LEFT OUTER JOIN periodo as t_p ON (t_a.id_periodo = t_p.id_periodo)
		ORDER BY descripcion";
		return toba::db('rukaja')->consultar($sql);
	}





                
        /*
         * Esta funcion elimina asignaciones definitivas solapadas con asignaciones por periodo.
         * Las asignaciones por periodo tienen prioridad
         */
        function descartar_asignaciones_definitivas ($asig_periodo, $asig_definitiva){
            $longitud=count($asig_definitiva);
            $i=0;
            foreach ($asig_periodo as $periodo){
                while($i<$longitud){
                    
                    if($this->existe_inclusion($periodo,$asig_definitiva[$i])){
                        //borramos una asignacion definitiva si contiene a una por periodo.
                        //Las asignaciones por periodo tiene prioridad
                        $asig_definitiva[$i]=null;
                    }
                    $i += 1;
                }
                
                $i=0;
            }
        }
        
        /*
         * Devuelve true si una asignacion por periodo esta incluida en una definitiva.
         */
        function existe_inclusion ($periodo, $definitiva){
            return ((strcmp($periodo['aula'], $definitiva['aula'])==0) && 
                   (($periodo['hora_inicio'] >= $definitiva['hora_inicio']) && ($periodo['hora_inicio'] <= $definitiva['hora_fin'])) &&
                   ($periodo['hora_fin'] <= $definitiva['hora_fin']));
        }
        
        /*
         * Esta funcion realiza una union entre conjuntos. Periodo se debe pasar por referencia.
         */
        function unificar_asignaciones ($periodo, $definitiva){
            foreach ($definitiva as $clave=>$valor){
                if(isset($valor)){
                   $periodo[]=$valor; //agrega al final
                }
            }            
        }
        
        function get_unidades_academicas (){
            $sql="SELECT sigla, descripcion 
                  FROM unidad_academica  
                  WHERE sigla <> 'BIBLIO' ";
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_sedes ($sigla){
            $sql="SELECT descripcion, id_sede 
                  FROM sede 
                  WHERE sigla='$sigla'";
            return toba::db('rukaja')->consultar($sql);
        }      
                
        /*
         * Se usa para calcular horarios disponibles a partir de una fecha. 
         * @$fecha contiene una fecha ingresada por el usuario 
         */
        function get_periodos_activos ($dia, $cuatrimestre, $anio, $id_sede, $fecha){
            $sql_1="SELECT t_aula.nombre as aula,
                           t_a.id_aula,
                           t_a.hora_inicio,
                           t_a.hora_fin,
                           t_a.finalidad,
                           t_ua.descripcion,
                           (t_p.nombre || ' ' || t_p.apellido) as responsable
                           
                    FROM asignacion t_a 
                    JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                    JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                    JOIN unidad_academica t_ua ON (t_ua.sigla=t_a.facultad)
                    JOIN asignacion_definitiva t_ad ON (t_a.id_asignacion=t_ad.id_asignacion)
                    WHERE t_a.id_sede=$id_sede AND t_ad.nombre='$dia' AND t_ad.cuatrimestre=$cuatrimestre AND t_ad.anio=$anio";
            
            $asig_definitivas=toba::db('rukaja')->consultar($sql_1);
            
            $sql_2="SELECT t_aula.nombre as aula,
                           t_a.id_aula,
                           t_a.hora_inicio,
                           t_a.hora_fin,
                           t_a.finalidad,
                           t_ua.descripcion,
                           (t_p.nombre || ' ' || t_p.apellido) as responsable
                         
                    FROM asignacion t_a 
                    JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc) 
                    JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                    JOIN unidad_academica t_ua ON (t_a.facultad=t_ua.sigla)
                    JOIN asignacion_periodo t_pe ON (t_a.id_asignacion=t_pe.id_asignacion AND ($fecha BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin))
                    JOIN esta_formada t_f ON (t_pe.id_asignacion=t_f.id_asignacion)
                    WHERE t_aula.id_sede=$id_sede AND t_f.nombre='$dia' AND t_f.cuatrimestre=$cuatrimestre AND t_f.anio=$anio";
            
            $asig_periodo=toba::db('rukaja')->consultar($sql_2);
            
            $this->descartar_asignaciones_definitivas($asig_periodo, &$asig_definitivas);
            
            $this->unificar_asignaciones(&$asig_periodo, $asig_definitivas);
            
            return $asig_periodo;
            
        }
        
        //Deprecada
//        function get_asignaciones_por_cuatrimestre ($cuatrimestre, $anio, $id_sede){
//            $sql="SELECT t_a.finalidad as materia,
//                         t_a.id_asignacion,
//                         t_a.hora_inicio,
//                         t_a.hora_fin,
//                         t_aula.nombre as aula,
//                         t_aula.id_aula,
//                         t_d.nombre as dia
//                  FROM asignacion t_a 
//                  JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
//                  JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
//                  WHERE t_aula.id_sede=$id_sede AND t_d.cuatrimestre=$cuatrimestre AND t_d.anio=$anio AND (t_aula.nombre <> 'Sábado')";
//            
//            return toba::db('gestion_aulas')->consultar($sql);
//            
//        }
        
        /*
         * Esta funcion se utiliza en la operacion Calendario Comahue, devuelve todas las 
         * asignaciones cargadas para una fecha seleccionada
         */
        function get_asignaciones_por_fecha ($id_sede,$dia,$cuatrimestre,$anio,$fecha){
            $sql_1="SELECT t_a.finalidad,
                           t_a.id_asignacion,
                           t_a.hora_inicio, 
                           t_a.hora_fin, 
                           t_au.nombre as aula, 
                           t_a.facultad,
                           'Definitiva' as tipo
                    FROM asignacion t_a
                    JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                    JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
                    WHERE t_au.id_sede=$id_sede AND t_d.nombre='$dia' AND t_d.cuatrimestre=$cuatrimestre AND t_d.anio=$anio";
            
            $asig_definitivas=toba::db('rukaja')->consultar($sql_1);
            //AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin)
            $sql_2="SELECT t_a.finalidad,
                           t_a.id_asignacion,
                           t_a.hora_inicio,
                           t_a.hora_fin,
                           t_au.nombre as aula,
                           t_a.facultad,
                           'Periodo' as tipo
                    FROM asignacion t_a 
                    JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                    JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion)
                    JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))
                    WHERE t_au.id_sede=$id_sede AND t_f.nombre='$dia' AND t_f.cuatrimestre=$cuatrimestre AND t_f.anio=$anio";
            
            $asig_periodo=toba::db('rukaja')->consultar($sql_2);
            
            $this->descartar_asignaciones_definitivas($asig_periodo, &$asig_definitivas);
            
            $this->unificar_asignaciones(&$asig_periodo, $asig_definitivas);
            
            return $asig_periodo;
            
        }
        
                
        /*
         * Esta funcion se utiliza en la operacion Generar Solicitud.
         * Devuelve un cjto de asignaciones para calcular horarios disponibles en Unidades Academicas.
         */
        function get_asignaciones_solicitud ($id_sede, $dia, $cuatrimestre, $anio, $fecha){
            $sql_1="SELECT t_a.hora_inicio, t_a.hora_fin, t_au.nombre as aula, t_au.id_aula
                    FROM asignacion t_a 
                    JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                    JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion) 
                    WHERE t_au.id_sede={$id_sede} AND t_d.nombre='{$dia}' AND t_d.cuatrimestre=$cuatrimestre AND t_d.anio=$anio";
            
            $asig_definitiva=toba::db('rukaja')->consultar($sql_1);
                      
            //Debemos incluir la siguiente sentencia para considerar periodos
            //AND ('2015-09-10' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin) en 
            //JOIN asignacion_periodo ON ()
            $sql_2="SELECT t_a.hora_inicio, t_a.hora_fin, t_au.nombre as aula, t_au.id_aula
                    FROM asignacion t_a
                    JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)    
                    JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion)
                    JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion) 
                    WHERE t_au.id_sede={$id_sede} AND t_f.nombre='{$dia}' AND t_f.cuatrimestre=$cuatrimestre AND t_f.anio=$anio";
            
            $asig_periodo=toba::db('rukaja')->consultar($sql_2);
                       
            //eliminamos las asignaciones definitivas que estan solapadas con dos o mas asignaciones por periodo
            $this->descartar_asignaciones_definitivas($asig_periodo, &$asig_definitiva);
            
            $this->unificar_asignaciones(&$asig_periodo, $asig_definitiva);
            
            return $asig_periodo;
        }
        
        //-----------------------------------------------------------------------------------------------
        //---- 2016 -------------------------------------------------------------------------------------
        //-----------------------------------------------------------------------------------------------
        
        /*
         * Esta funcion se utiliza en la operacion Registrar Periodos, para verificar si un periodo posee
         * asignaciones. 
         */
        function get_asignaciones_por_periodo ($id_periodo){
            $sql="SELECT id_asignacion
                  FROM asignacion 
                  WHERE id_periodo=$id_periodo";
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Cargar Asignaciones, para llenar el combo tipo_asignacion.
         */
        function get_tipo_asignacion (){
            $sql="SELECT tipo
                  FROM tipo_asignacion";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Generar Solicitud, para llenar el combo tipo de la seccion 
         * Datos de la Solicitud. La diferencia con get_tipo_asignacion es que se devuelve los tipos de asignacion
         * excepto cursada,ademas agrega un campo otro para hacer una ext javascript en el formulario, de esta 
         * manera permitimos que el usuario registre un nuevo tipo en el sistema. 
         */
        function get_tipos (){
            $sql="SELECT tipo
                  FROM tipo_asignacion 
                  WHERE tipo <> 'CURSADA'";
            $tipos=toba::db('rukaja')->consultar($sql);
            $tipos[]=array('tipo'=>'OTRO');
            
            return ($tipos);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Generar Solicitud para cargar un combo llamado organizaciones
         * existentes. 
         */
        function get_organizaciones ($tipo){
            if(strcmp('Organizacion', $tipo)==0){
                $sql="SELECT nombre_org
                      FROM organizacion";
                return (toba::db('rukaja')->consultar($sql));
            }
            else{
                return array();
            }
        }
        
        
        /*
         * Esta funcion se utiliza en la operacion Calendario Comahue, permite obtener un cjto de asignaciones 
         * para empezar el calculo de horarios disponibles para una fecha en particular. 
         * @ id_periodo : absorbe a ( cuatrimestre, anio ) y se corresponde con un cuatrimestre.
         */
        function get_asignaciones_cuatrimestre ($id_sede, $dia, $id_periodo, $fecha){
            $sql_1="SELECT t_a.hora_inicio, t_a.hora_fin, t_au.nombre as aula, t_au.id_aula, t_au.capacidad
                    FROM asignacion t_a 
                    JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                    JOIN periodo t_p ON (t_a.id_periodo=t_p.id_periodo)
                    JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion) 
                    WHERE t_au.id_sede={$id_sede} AND t_d.nombre='$dia' AND t_p.id_periodo=$id_periodo";
                    
            $asig_definitiva=toba::db('rukaja')->consultar($sql_1);
            
            //Debemos incluir la siguiente sentencia para considerar periodos
            //AND ('2015-09-10' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin) en 
            //JOIN asignacion_periodo ON ()
            $sql_2="SELECT t_a.hora_inicio, t_a.hora_fin, t_au.nombre as aula, t_au.id_aula, t_au.capacidad
                    FROM asignacion t_a
                    JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)    
                    JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                    JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))
                    JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion) 
                    WHERE t_au.id_sede={$id_sede} AND t_f.nombre='$dia' AND t_per.id_periodo=$id_periodo AND t_f.fecha='$fecha'";
                    
            $asig_periodo=toba::db('rukaja')->consultar($sql_2);
            
            //eliminamos las asignaciones definitivas que estan solapadas con dos o mas asignaciones por periodo
            $this->descartar_asignaciones_definitivas($asig_periodo, &$asig_definitiva);
            
            $this->unificar_asignaciones(&$asig_periodo, $asig_definitiva);
            
            return $asig_periodo;
        }
        
        /*
         * Esta funcion se utiliza en la operacion  Cargar Asignaciones, para cargar el cuadro_asignaciones 
         * de la pantalla pant_asignacion.
         */
        function get_asignaciones_por_persona ($nro_doc, $id_periodo, $fecha){
            $sql="(SELECT t_a.finalidad as materia, t_a.hora_inicio, t_a.hora_fin, t_au.nombre as aula, t_d.nombre as dia
                  FROM asignacion t_a 
                  JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                  JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
                  WHERE t_a.nro_doc='$nro_doc' AND t_a.id_periodo=$id_periodo)
                      
                  UNION 
                  
                  (SELECT t_a.finalidad as materia, t_a.hora_inicio, t_a.hora_fin, t_au.nombre as aula, t_f.nombre as dia 
                  FROM asignacion t_a 
                  JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                  JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin)) 
                  JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion) 
                  WHERE t_a.nro_doc='$nro_doc' AND t_a.id_periodo=$id_periodo)";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Buscador de Aulas, para filtrar las aulas disponibles segun
         * hora_inicio - hora_fin. Se utiliza para obtener asignaciones por dia para empezar el calculo de 
         * horarios disponibles.
         */
        function get_asignaciones_definitivas_por_dia ($id_sede, $dia, $id_periodo){
            $sql="SELECT t_a.hora_inicio,
                         t_a.hora_fin,
                         t_au.nombre as aula,
                         t_au.id_aula,
                         t_au.capacidad
                  FROM asignacion t_a 
                  JOIN asignacion_definitiva t_ad ON (t_a.id_asignacion=t_ad.id_asignacion)
                  JOIN aula t_au ON (t_a.id_aula=t_au.id_aula) 
                  WHERE t_au.id_sede=$id_sede AND t_a.id_periodo=$id_periodo AND t_ad.nombre='$dia'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Calendario Comahue, para obtener todas las asignaciones por 
         * periodo que se corresponden con examenes finales.
         * @id_periodo : absorbe a (cuatrimestre, anio) y se corresponde con un examen final.
         */
        function get_asignaciones_examen_final ($id_sede, $dia, $id_periodo, $fecha){
            $sql="SELECT t_a.hora_inicio, t_a.hora_fin, t_au.nombre as aula, t_au.id_aula, t_au.capacidad
                    FROM asignacion t_a 
                    JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                    JOIN periodo t_p ON (t_a.id_periodo=t_p.id_periodo)
                    JOIN asignacion_periodo t_per ON (t_a.id_asignacion=t_per.id_asignacion AND ('$fecha' BETWEEN t_per.fecha_inicio AND t_per.fecha_fin))
                    JOIN esta_formada t_f ON (t_per.id_asignacion=t_f.id_asignacion)
                    WHERE t_au.id_sede=$id_sede AND t_f.nombre='$dia' AND t_a.id_periodo=$id_periodo AND t_f.fecha='$fecha'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Calendario Comahue, para implementar la opcion 
         * "horarios registrados" en el combo tipo.
         */
        function get_asignaciones_definitivas_por_fecha_cuatrimestre ($id_sede, $dia, $id_periodo){
            $sql="SELECT t_a.finalidad, t_a.hora_inicio, t_a.hora_fin, t_a.facultad, 
                         t_au.nombre as aula, t_au.capacidad, t_au.id_aula, t_au.capacidad, 
                         t_pe.nro_doc, t_pe.tipo_doc, t_pe.nombre || ' ' || t_pe.apellido as responsable,
                         'Definitiva' as tipo, t_a.cantidad_alumnos as cant_alumnos
                  FROM asignacion t_a 
                  JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                  JOIN persona t_pe ON (t_a.nro_doc=t_pe.nro_doc AND t_a.tipo_doc=t_pe.tipo_doc)
                  JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                  JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
                  WHERE t_au.id_sede=$id_sede AND t_d.nombre='$dia' AND t_a.id_periodo=$id_periodo 
                  ";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Calendario Comahue, para implementar la opcion 
         * "horasrios registrados" en el combo tipo.
         * En esta funcion se incluyen asig_periodo correspondientes a eventos o examenes parciales.
         */
        function get_asignaciones_periodo_por_fecha_cuatrimestre ($id_sede, $dia, $id_periodo, $fecha){
            $sql="SELECT t_a.finalidad, t_a.id_aula, t_au.nombre as aula, t_a.hora_inicio, t_a.hora_fin, 
                         t_a.tipo_asignacion, 
                         t_au.capacidad, 'Periodo' as tipo, t_a.cantidad_alumnos as cant_alumnos
                  FROM asignacion t_a 
                  JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                  JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                  JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))
                  JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion)
                  WHERE t_au.id_sede=$id_sede AND t_f.nombre='$dia' AND t_a.id_periodo=$id_periodo AND t_f.fecha='$fecha'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Calendario Comahue, para implementar la opcion
         * "horarios registrados" en el combo tipo.
         * En esta funcion se incluyen las asig_periodo que se corresponden con examenes finales, es enecesaria
         * porque las asig_periodo (examen parcial, evento) y las asig_periodo (examen final) pertenecen a 
         * distintos periodos. 
         */
        function get_asignaciones_periodo_por_fecha_examen ($id_sede, $dia, $id_periodo, $fecha){
            $sql="SELECT t_a.finalidad, t_a.hora_inicio, t_a.hora_fin, t_a.tipo_asignacion,
                         t_au.id_aula, t_au.capacidad, 'Periodo' as tipo, t_a.cantidad_alumnos as cant_alumnos
                  FROM asignacion t_a 
                  JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                  JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))
                  JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion)
                  JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                  WHERE t_au.id_sede=$id_sede AND t_f.nombre='$dia' AND t_a.id_periodo=$id_periodo AND t_f.fecha='$fecha'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion devuelve un cjto asignaciones a partir de una hora especifica. Se utiliza en la 
         * operacion Visualizacion de Usos Diarios.
         * Las asignaciones por periodo tienen prioridad sobre las asignaciones definitivas.
         */
        function get_asignaciones_cuatrimestre_por_hora ($id_sede, $dia, $id_periodo, $fecha, $hora){
            
            $sql_1="SELECT t_aula.nombre as aula, 
                         t_aula.id_aula,
                         t_aula.capacidad,
                         t_a.hora_inicio,
                         t_a.hora_fin,
                         t_a.finalidad,
                         t_ua.descripcion, 
                         (t_p.nombre || ' ' || t_p.apellido) as responsable 
                           
                  FROM asignacion t_a 
                  JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                  JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                  JOIN unidad_academica t_ua ON (t_ua.sigla=t_a.facultad)
                  JOIN asignacion_definitiva t_ad ON (t_a.id_asignacion=t_ad.id_asignacion)
                  JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                  WHERE t_aula.id_sede=$id_sede AND t_ad.nombre='$dia' AND t_a.id_periodo=$id_periodo AND "
                    . "( $hora BETWEEN t_a.hora_inicio AND t_a.hora_fin )";
            
            $asig_definitivas=toba::db('rukaja')->consultar($sql_1);
            
            //falta incluir fecha BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin
            $sql_2="SELECT t_aula.nombre as aula,
                           t_aula.id_aula,
                           t_aula.capacidad,
                           t_a.hora_inicio,
                           t_a.hora_fin,
                           t_a.finalidad,
                           t_ua.descripcion,
                           (t_p.nombre || ' ' || t_p.apellido) as responsable
                           
                    FROM asignacion t_a
                    JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                    JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                    JOIN unidad_academica t_ua ON (t_a.facultad=t_ua.sigla)
                    JOIN asignacion_periodo t_pe ON (t_a.id_asignacion=t_pe.id_asignacion AND ('$fecha' BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin))
                    JOIN esta_formada t_f ON (t_pe.id_asignacion=t_f.id_asignacion)
                    JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                    WHERE t_aula.id_sede=$id_sede AND t_f.nombre='$dia' AND t_a.id_periodo=$id_periodo 
                          AND ($hora BETWEEN t_a.hora_inicio AND t_a.hora_fin) AND t_f.fecha='$fecha' ";
            
            $asig_periodo=toba::db('rukaja')->consultar($sql_2);
            //print_r($asig_periodo);exit();
                       
            $this->descartar_asignaciones_definitivas($asig_periodo, &$asig_definitivas);
            
            $this->unificar_asignaciones(&$asig_periodo, $asig_definitivas);
            
            return $asig_periodo;
        }
        
        /*
         * Esta funcion permite obtener todas las asignaciones pertenecientes a un examen final a partir de 
         * una hora especifica. Se usa en Visualizacion de Usos Diarios. 
         */
        function get_asignaciones_examen_final_por_hora ($id_sede, $dia, $id_periodo, $fecha, $hora){
            $sql="SELECT t_aula.nombre as aula,
                           t_aula.id_aula,
                           t_aula.capacidad, 
                           t_a.hora_inicio,
                           t_a.hora_fin,
                           t_a.finalidad,
                           t_ua.descripcion,
                           (t_p.nombre || ' ' || t_p.apellido) as responsable
                           
                    FROM asignacion t_a
                    JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                    JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                    JOIN unidad_academica t_ua ON (t_a.facultad=t_ua.sigla)
                    JOIN asignacion_periodo t_pe ON (t_a.id_asignacion=t_pe.id_asignacion AND ('$fecha' BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin))
                    JOIN esta_formada t_f ON (t_pe.id_asignacion=t_f.id_asignacion)
                    JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                    WHERE t_aula.id_sede=$id_sede AND t_f.nombre='$dia' AND t_a.id_periodo=$id_periodo 
                          AND ($hora BETWEEN t_a.hora_inicio AND t_a.hora_fin) AND t_f.fecha='$fecha' ";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion devuelve el cjto de horarios ocupados en un aula especifica.
         * Se usa para calcular horarios disponibles en un aula, en la operacion Visualizacion de Usos Diarios.
         * Como debemos devolver las asignaciones para un aula en particular necesitamos el id_aula en lugar del
         * id_sede. 
         */
        function get_asignaciones_por_aula_cuatrimestre ($dia, $id_periodo, $fecha, $id_aula){
            $sql_1="SELECT t_aula.nombre as aula,
                           t_a.id_aula,
                           t_a.hora_inicio,
                           t_a.hora_fin,
                           t_a.finalidad,
                           t_ua.descripcion, 
                           (t_p.nombre || ' ' || t_p.apellido) as responsable 
                           
                    FROM asignacion t_a 
                    JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                    JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                    JOIN unidad_academica t_ua ON (t_ua.sigla=t_a.facultad)
                    JOIN asignacion_definitiva t_ad ON (t_a.id_asignacion=t_ad.id_asignacion)
                    WHERE t_a.id_aula=$id_aula AND t_ad.nombre='$dia' AND t_a.id_periodo=$id_periodo 
                     ";
            
            $asig_definitivas=toba::db('rukaja')->consultar($sql_1);
            
            //falta incluir fecha_actual BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin
            $sql_2="SELECT t_aula.nombre as aula,
                           t_a.id_aula,
                           t_a.hora_inicio,
                           t_a.hora_fin,
                           t_a.finalidad,
                           t_ua.descripcion,
                           (t_p.nombre || ' ' || t_p.apellido) as responsable
                           
                    FROM asignacion t_a
                    JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                    JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                    JOIN unidad_academica t_ua ON (t_a.facultad=t_ua.sigla)
                    JOIN asignacion_periodo t_pe ON (t_a.id_asignacion=t_pe.id_asignacion AND ('$fecha' BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin))
                    JOIN esta_formada t_f ON (t_pe.id_asignacion=t_f.id_asignacion)
                    WHERE t_a.id_aula=$id_aula AND t_f.nombre='$dia' AND t_a.id_periodo=$id_periodo AND 
                     t_f.fecha='$fecha' ";
            
            $asig_periodo=toba::db('rukaja')->consultar($sql_2);
            
            //eliminamos las asignaciones definitivas que esten solapadas con las asignaciones por 
            //periodo. Las asignaciones por periodo tienen prioridad.
            $this->descartar_asignaciones_definitivas($asig_periodo, &$asig_definitivas);
            
            //generamos un arreglo con todas las asignaciones por aula.
            $this->unificar_asignaciones(&$asig_periodo, $asig_definitivas);
            
            return $asig_periodo;
        }
        
        /*
         * Esta funcion devuelve las asignaciones examen final para un aula. Como debemos devolver las asignaciones para un aula en particular necesitamos el id_aula en lugar del
         * id_sede. Se utiliza en la operacion Visualizacion de Usos Diarios para calcular espacios 
         * disponibles.
         */
        function  get_asignaciones_por_aula_examen_final ($dia, $id_periodo, $fecha, $id_aula){
            $sql="SELECT t_aula.nombre as aula,
                           t_a.id_aula,
                           t_a.hora_inicio,
                           t_a.hora_fin,
                           t_a.finalidad,
                           t_ua.descripcion,
                           (t_p.nombre || ' ' || t_p.apellido) as responsable
                           
                    FROM asignacion t_a
                    JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                    JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                    JOIN unidad_academica t_ua ON (t_a.facultad=t_ua.sigla)
                    JOIN asignacion_periodo t_pe ON (t_a.id_asignacion=t_pe.id_asignacion AND ('$fecha' BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin))
                    JOIN esta_formada t_f ON (t_pe.id_asignacion=t_f.id_asignacion)
                    WHERE t_a.id_aula=$id_aula AND t_f.nombre='$dia' AND t_a.id_periodo=$id_periodo AND 
                     t_f.fecha='$fecha'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Cargar Asignaciones.El objetivo de esta funcion es devolver 
         * un cjto. de asignaciones, pertenecientes al periodo actual, para que se puedan editar o borrar.
         * El periodo actual se obtiene a partir de la fecha actual.
         */
        function get_asignaciones ($where, $id_periodo){
            //El JOIN con dia es necesario porque sino el where no nos sirve.
            $sql="(SELECT t_a.id_asignacion, t_a.nro_doc, t_a.tipo_doc, t_a.finalidad, t_a.hora_inicio, t_a.hora_fin, t_dia.nombre as dia, (t_p.nombre || ' ' || t_p.apellido) as responsable, 'Definitiva' as tipo
                   FROM asignacion t_a 
                   JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                   JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                   
                   JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion )
                   JOIN dia t_dia ON (t_d.nombre=t_dia.nombre)
                   WHERE ($where) AND t_a.id_periodo=$id_periodo) 
                   
                   UNION 
                   
                   (SELECT t_a.id_asignacion, t_a.nro_doc, t_a.tipo_doc, t_a.finalidad, t_a.hora_inicio, t_a.hora_fin, t_dia.nombre as dia, (t_p.nombre || ' ' || t_p.apellido) as responsable, 'Periodo' as tipo
                    FROM asignacion t_a 
                    JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                    JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                    
                    JOIN asignacion_periodo t_pe ON (t_a.id_asignacion=t_pe.id_asignacion)
                    JOIN esta_formada t_f ON (t_pe.id_asignacion=t_f.id_asignacion)
                    JOIN dia t_dia ON (t_f.nombre=t_dia.nombre)
                    WHERE ($where) AND t_a.id_periodo=$id_periodo)";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion Memorando por Fecha. Permite obtener las asignaciones para un
         * dia especifico de la semana cuando la fecha seleccionada cae dentro de un periodo cuatrimestre.
         */
        function get_asignaciones_memo_por_cuatrimestre ($dia, $periodo, $id_sede, $fecha){
//            $sql_1="SELECT t_a.finalidad,
//                           t_a.hora_inicio, 
//                           t_a.hora_fin, 
//                           t_au.nombre as aula, 
//                           t_a.facultad,
//                           (t_p.nombre || ' ' || t_p.apellido) as responsable
//                    FROM asignacion t_a
//                    JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
//                    JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
//                    JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
//                    WHERE t_au.id_sede=$id_sede AND t_d.nombre='$dia' AND t_a.id_periodo=$periodo ";
//            
//            $asig_definitivas=toba::db('gestion_aulas')->consultar($sql_1);
            
            $sql_2="SELECT t_a.id_asignacion, 
                           t_a.finalidad,
                           t_a.hora_inicio,
                           t_a.hora_fin,
                           t_au.nombre as aula,
                           t_a.facultad,
                           (t_pe.nombre || ' ' || t_pe.apellido) as responsable
                    FROM asignacion t_a 
                    JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                    JOIN persona t_pe ON (t_a.nro_doc=t_pe.nro_doc)
                    JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))
                    JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion )
                    WHERE t_au.id_sede=$id_sede AND t_f.nombre='$dia' AND t_a.id_periodo=$periodo AND t_f.fecha='$fecha'";
            
            $asig_periodo=toba::db('rukaja')->consultar($sql_2);
            
            //$this->descartar_asignaciones_definitivas($asig_periodo, &$asig_definitivas);
            
            //$this->unificar_asignaciones(&$asig_periodo, $asig_definitivas);
            
            foreach ($asig_periodo as $clave=>$valor){
                $sql="SELECT t_p.nombre, t_p.apellido
                      FROM persona t_p 
                      JOIN catedra t_c ON (t_p.nro_doc=t_c.nro_doc AND t_p.tipo_doc=t_c.tipo_doc)
                      JOIN asignacion t_a ON (t_a.id_asignacion=t_c.id_asignacion)
                      WHERE t_a.id_asignacion={$valor['id_asignacion']}";
                $asig_periodo['catedra']=toba::db('rukaja')->consultar($sql);     
            }
            
            return $asig_periodo;
        }
        
        /*
         * 
         */
        function get_asignaciones_memo_por_examen_final ($dia, $periodo, $id_sede, $fecha){           
            $sql_2="SELECT t_a.finalidad,
                           t_a.hora_inicio,
                           t_a.hora_fin,
                           t_au.nombre as aula,
                           t_a.facultad,
                           (t_pe.nombre || ' ' || t_pe.apellido) as responsable
                    FROM asignacion t_a 
                    JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                    JOIN persona t_pe ON (t_a.nro_doc=t_pe.nro_doc)
                    JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))
                    JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion )
                    WHERE t_au.id_sede=$id_sede AND t_f.nombre='$dia' AND t_a.id_periodo=$periodo AND t_f.fecha='$fecha'";
            
            return toba::db('rukaja')->consultar($sql_2);           
            
        }
        
        /*
         * Esta funcion se usa en la operacion Asignaciones por Dia. Permite devolver las asignaciones definitivas 
         * para un dia de la semana. Las asignaciones retornadas confeccionan el reporte.
         * @id_periodo = es el id_periodo del cuatrimestre elegido.
         * @dia = contiene un dia de la semana, es util para obtener las asignaciones correspondientes.
         */
        function get_asignaciones_por_dia ($id_periodo, $dia){
            $sql="SELECT t_a.hora_inicio, t_a.hora_fin, t_au.id_aula, t_a.facultad,
                         (t_a.finalidad || ' - ' || t_a.facultad || ' - ' || t_a.hora_inicio || ' a ' || t_a.hora_fin || ' - ' || t_a.cantidad_alumnos || 
                          ' ALUMNOS - ' || t_p.nombre || ' ' || t_p.apellido) as dato_celda 
                  FROM asignacion t_a 
                  JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                  JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
                  JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc AND t_a.tipo_doc=t_p.tipo_doc)
                  WHERE t_a.id_periodo=$id_periodo AND t_d.nombre='$dia'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
}
?>