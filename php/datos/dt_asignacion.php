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
                        asignacion as t_a
                            LEFT OUTER JOIN aula as t_a1 ON (t_a.id_aula = t_a1.id_aula)
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
         * Devuelve true si una asignacion por periodo esta incluida en una definitiva. Verificamos los siguiente 
         * casos:
         * a) Inclusion completa de un horario.
         * Pero faltan inclusiones parciales, teniendo en cuenta hora_inicio y hora_fin.
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
            $sql="SELECT 
                      sigla, descripcion 
                  FROM
                      unidad_academica  
                  WHERE sigla <> 'BIBLIO' ";
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_sedes ($sigla){
            $sql="SELECT 
                      descripcion, id_sede 
                  FROM
                      sede 
                  WHERE sigla='$sigla'";
            return toba::db('rukaja')->consultar($sql);
        }      
                
        /*
         * Se usa para calcular horarios disponibles a partir de una fecha. 
         * @$fecha contiene una fecha ingresada por el usuario 
         */
        function get_periodos_activos ($dia, $cuatrimestre, $anio, $id_sede, $fecha){
            $sql_1="SELECT 
                        t_aula.nombre as aula,
                        t_a.id_aula,
                        t_a.hora_inicio,
                        t_a.hora_fin,
                        t_a.finalidad,
                        t_ua.descripcion,
                        (t_p.nombre || ' ' || t_p.apellido) as responsable
                           
                    FROM 
                        asignacion t_a 
                            JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                            JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                            JOIN unidad_academica t_ua ON (t_ua.sigla=t_a.facultad)
                            JOIN asignacion_definitiva t_ad ON (t_a.id_asignacion=t_ad.id_asignacion)
                    WHERE t_a.id_sede=$id_sede 
                          AND t_ad.nombre='$dia' 
                          AND t_ad.cuatrimestre=$cuatrimestre 
                          AND t_ad.anio=$anio";
            
            $asig_definitivas=toba::db('rukaja')->consultar($sql_1);
            
            $sql_2="SELECT 
                        t_aula.nombre as aula,
                        t_a.id_aula,
                        t_a.hora_inicio,
                        t_a.hora_fin,
                        t_a.finalidad,
                        t_ua.descripcion,
                        (t_p.nombre || ' ' || t_p.apellido) as responsable
                         
                    FROM
                        asignacion t_a 
                            JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc) 
                            JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                            JOIN unidad_academica t_ua ON (t_a.facultad=t_ua.sigla)
                            JOIN asignacion_periodo t_pe ON (t_a.id_asignacion=t_pe.id_asignacion AND ($fecha BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin))
                            JOIN esta_formada t_f ON (t_pe.id_asignacion=t_f.id_asignacion)
                    WHERE t_aula.id_sede=$id_sede 
                          AND t_f.nombre='$dia' 
                          AND t_f.cuatrimestre=$cuatrimestre 
                          AND t_f.anio=$anio";
            
            $asig_periodo=toba::db('rukaja')->consultar($sql_2);
            
            $this->descartar_asignaciones_definitivas($asig_periodo, &$asig_definitivas);
            
            $this->unificar_asignaciones(&$asig_periodo, $asig_definitivas);
            
            return $asig_periodo;
            
        }
                
        /*
         * Esta funcion se utiliza en la operacion 'Calendario Comahue', devuelve todas las 
         * asignaciones cargadas para una fecha seleccionada
         */
        function get_asignaciones_por_fecha ($id_sede,$dia,$cuatrimestre,$anio,$fecha){
            $sql_1="SELECT 
                        t_a.finalidad,
                        t_a.id_asignacion,
                        t_a.hora_inicio, 
                        t_a.hora_fin, 
                        t_au.nombre as aula, 
                        t_a.facultad,
                        'Definitiva' as tipo
                    FROM
                        asignacion t_a
                            JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                            JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
                    WHERE t_au.id_sede=$id_sede 
                          AND t_d.nombre='$dia' 
                          AND t_d.cuatrimestre=$cuatrimestre 
                          AND t_d.anio=$anio";
            
            $asig_definitivas=toba::db('rukaja')->consultar($sql_1);
            //AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin)
            $sql_2="SELECT 
                        t_a.finalidad,
                        t_a.id_asignacion,
                        t_a.hora_inicio,
                        t_a.hora_fin,
                        t_au.nombre as aula,
                        t_a.facultad,
                        'Periodo' as tipo
                    FROM
                        asignacion t_a 
                            JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                            JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion)
                            JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))
                    WHERE t_au.id_sede=$id_sede 
                          AND t_f.nombre='$dia' 
                          AND t_f.cuatrimestre=$cuatrimestre 
                          AND t_f.anio=$anio";
            
            $asig_periodo=toba::db('rukaja')->consultar($sql_2);
            
            $this->descartar_asignaciones_definitivas($asig_periodo, &$asig_definitivas);
            
            $this->unificar_asignaciones(&$asig_periodo, $asig_definitivas);
            
            return $asig_periodo;
            
        }
        
                
        /*
         * Esta funcion se utiliza en la operacion 'Solicitar Aula'.
         * Devuelve un cjto de asignaciones para calcular horarios disponibles en Unidades Academicas.
         */
        function get_asignaciones_solicitud ($id_sede, $dia, $cuatrimestre, $anio, $fecha){
            $sql_1="SELECT 
                        t_a.hora_inicio, t_a.hora_fin, t_au.nombre as aula, t_au.id_aula
                    FROM
                        asignacion t_a 
                            JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                            JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion) 
                    WHERE t_au.id_sede={$id_sede} 
                          AND t_d.nombre='{$dia}' 
                          AND t_d.cuatrimestre=$cuatrimestre 
                          AND t_d.anio=$anio";
            
            $asig_definitiva=toba::db('rukaja')->consultar($sql_1);
                      
            //Debemos incluir la siguiente sentencia para considerar periodos
            //AND ('2015-09-10' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin) en 
            //JOIN asignacion_periodo ON ()
            $sql_2="SELECT 
                        t_a.hora_inicio, t_a.hora_fin, t_au.nombre as aula, t_au.id_aula
                    FROM
                        asignacion t_a
                            JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)    
                            JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion)
                            JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion) 
                    WHERE t_au.id_sede={$id_sede} 
                          AND t_f.nombre='{$dia}' 
                          AND t_f.cuatrimestre=$cuatrimestre 
                          AND t_f.anio=$anio";
            
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
         * Esta funcion se utiliza en la operacion 'Registrar Periodos', para verificar si un periodo posee
         * asignaciones. Si el periodo posee asinaciones, no lo podemos eliminar del sistema.
         */
        function get_asignaciones_por_periodo ($id_periodo){
            $sql="SELECT 
                      id_asignacion
                  FROM
                      asignacion 
                  WHERE id_periodo=$id_periodo";
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Cargar Asignaciones', para llenar el combo tipo_asignacion.
         */
        function get_tipo_asignacion (){
            $sql="SELECT 
                      tipo
                  FROM
                      tipo_asignacion";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Solicitar Aula', para llenar el combo 'tipo' de la seccion 
         * Datos de la Solicitud. La diferencia con get_tipo_asignacion es que se devuelven todos los tipos de 
         * asignacion excepto cursada,ademas agrega un campo 'otro' para hacer una ext javascript en el formulario,
         * de esta manera permitimos que el usuario registre un nuevo tipo en el sistema. 
         */
        function get_tipos (){
            $sql="SELECT 
                      tipo
                  FROM
                      tipo_asignacion 
                  WHERE tipo <> 'CURSADA'";
            $tipos=toba::db('rukaja')->consultar($sql);
            $tipos[]=array('tipo'=>'OTRO');
            
            return ($tipos);
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Solicitar Aula' para cargar un combo llamado 'organizaciones
         * existentes'. 
         */
        function get_organizaciones ($tipo){
            if(strcmp('Organizacion', $tipo)==0){
                $sql="SELECT 
                          nombre_org
                      FROM
                          organizacion";
                return (toba::db('rukaja')->consultar($sql));
            }
            else{
                return array();
            }
        }
        
        
        /*
         * Esta funcion se utiliza en la operacion 'Calendario Comahue', permite obtener un cjto de asignaciones 
         * para empezar el calculo de horarios disponibles para una fecha en particular. 
         * @ id_periodo : absorbe a ( cuatrimestre, anio ) y se corresponde con un cuatrimestre.
         * Devuelve asignaciones definitivas y periodicas asociadas al cuatrimestre. Se hacen los descartes
         * necesarios.
         */
        function get_asignaciones_cuatrimestre ($id_sede, $dia, $id_periodo, $fecha){
            $sql_1="SELECT 
                        t_a.hora_inicio, t_a.hora_fin, t_au.nombre as aula, t_au.id_aula, t_au.capacidad
                    FROM
                        asignacion t_a 
                            JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                            JOIN periodo t_p ON (t_a.id_periodo=t_p.id_periodo)
                            JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion) 
                    WHERE t_au.id_sede={$id_sede} 
                          AND t_d.nombre='$dia' 
                          AND t_p.id_periodo=$id_periodo";
                    
            $asig_definitiva=toba::db('rukaja')->consultar($sql_1);
            
            //Debemos incluir la siguiente sentencia para considerar periodos
            //AND ('2015-09-10' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin) en 
            //JOIN asignacion_periodo ON ()
            $sql_2="SELECT 
                        t_a.hora_inicio, t_a.hora_fin, t_au.nombre as aula, t_au.id_aula, t_au.capacidad
                    FROM
                        asignacion t_a
                            JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)    
                            JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                            JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))
                            JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion) 
                    WHERE t_au.id_sede={$id_sede} 
                          AND t_f.nombre='$dia' 
                          AND t_per.id_periodo=$id_periodo 
                          AND t_f.fecha='$fecha'";
                    
            $asig_periodo=toba::db('rukaja')->consultar($sql_2);
            
            //eliminamos las asignaciones definitivas que estan solapadas con dos o mas asignaciones por periodo
            //$this->descartar_asignaciones_definitivas($asig_periodo, &$asig_definitiva);
            
            $this->unificar_asignaciones(&$asig_periodo, $asig_definitiva);
            
            return $asig_periodo;
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Cargar Asignaciones', para cargar el cuadro_asignaciones 
         * de la pantalla pant_asignacion con todas las asignaciones definitivas o periodicas de un docente
         * registrado en el sistema. Se tiene en cuenta la fecha actual para obtener periodos academicos.
         * @$id_responsable: contiene el id de un docente u organizacion.
         */
        function get_asignaciones_por_persona ($id_responsable, $id_periodo, $fecha){
            $sql="(SELECT 
                       t_a.finalidad as materia, t_a.hora_inicio, t_a.hora_fin, 
                       t_au.nombre as aula, t_d.nombre as dia, 'Definitiva' as tipo_asignacion
                   FROM 
                       asignacion t_a 
                          JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                          JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
                   WHERE t_a.id_responsable_aula=$id_responsable AND t_a.id_periodo=$id_periodo)
                      
                   UNION 
                  
                  (SELECT 
                       t_a.finalidad as materia, t_a.hora_inicio, t_a.hora_fin, 
                       t_au.nombre as aula, t_f.nombre as dia, 'Periodica' as tipo_asignacion 
                   FROM
                       asignacion t_a 
                          JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                          JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin)) 
                          JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion) 
                  WHERE t_a.id_responsable_aula=$id_responsable 
                        AND t_a.id_periodo=$id_periodo)";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Buscador de Aulas', para filtrar las aulas disponibles segun
         * hora_inicio - hora_fin. Se utiliza para obtener asignaciones por dia para empezar el calculo de 
         * horarios disponibles.
         */
        function get_asignaciones_definitivas_por_dia ($id_sede, $dia, $id_periodo){
            //Vamos a hacer una prueba usando un join con periodo, para hacer busquedas generales.
            $sql="SELECT 
                      t_a.hora_inicio,
                      t_a.hora_fin,
                      t_au.nombre as aula,
                      t_au.id_aula,
                      t_au.capacidad
                  FROM
                      asignacion t_a 
                          JOIN asignacion_definitiva t_ad ON (t_a.id_asignacion=t_ad.id_asignacion)
                          JOIN aula t_au ON (t_a.id_aula=t_au.id_aula) 
                  WHERE t_au.id_sede=$id_sede 
                        AND t_a.id_periodo=$id_periodo 
                        AND t_ad.nombre='$dia'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Calendario Comahue', para obtener todas las asignaciones por 
         * periodo que se corresponden con examenes finales.
         * @id_periodo : absorbe a (cuatrimestre, anio) y se corresponde con un examen final.
         */
        function get_asignaciones_examen_final ($id_sede, $dia, $id_periodo, $fecha){
            $sql="SELECT 
                      t_a.hora_inicio, t_a.hora_fin, t_au.nombre as aula, t_au.id_aula, t_au.capacidad
                  FROM
                      asignacion t_a 
                          JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                          JOIN periodo t_p ON (t_a.id_periodo=t_p.id_periodo)
                          JOIN asignacion_periodo t_per ON (t_a.id_asignacion=t_per.id_asignacion AND ('$fecha' BETWEEN t_per.fecha_inicio AND t_per.fecha_fin))
                          JOIN esta_formada t_f ON (t_per.id_asignacion=t_f.id_asignacion)
                  WHERE t_au.id_sede=$id_sede 
                        AND t_f.nombre='$dia' 
                        AND t_a.id_periodo=$id_periodo 
                        AND t_f.fecha='$fecha'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Calendario Comahue', para implementar la opcion 
         * "horarios registrados" en el combo tipo.
         */
        function get_asignaciones_definitivas_por_fecha_cuatrimestre ($id_sede, $dia, $id_periodo){
            
            $sql="SELECT 
                      t_a.finalidad, t_a.hora_inicio, t_a.hora_fin, t_a.facultad, t_a.id_asignacion,
                      t_au.nombre as aula, t_au.capacidad, t_au.id_aula, t_au.capacidad, 
                      t_a.nro_doc, t_a.tipo_doc, t_a.nombre || ' ' || t_a.apellido as responsable,
                      'Definitiva' as tipo, t_a.cantidad_alumnos as cant_alumnos, t_d.nombre as dia
                  FROM
                      asignacion t_a 
                           JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                           JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                           JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
                  WHERE t_au.id_sede=$id_sede 
                        AND t_d.nombre='$dia' 
                        AND t_a.id_periodo=$id_periodo 
                  ";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Calendario Comahue', para implementar la opcion 
         * "horarios registrados" en el combo tipo.
         * En esta funcion se incluyen asig_periodo correspondientes a eventos o examenes parciales.
         */
        function get_asignaciones_periodo_por_fecha_cuatrimestre ($id_sede, $dia, $id_periodo, $fecha){
            $sql="SELECT 
                      t_a.finalidad, t_a.id_aula, t_au.nombre as aula, t_a.hora_inicio, t_a.hora_fin, 
                      t_a.tipo_asignacion, t_a.id_asignacion,
                      t_au.capacidad, 'Periodo' as tipo, t_a.cantidad_alumnos as cant_alumnos
                  FROM
                      asignacion t_a 
                          JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                          JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                          JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))
                          JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion)
                  WHERE t_au.id_sede=$id_sede 
                        AND t_f.nombre='$dia' 
                        AND t_a.id_periodo=$id_periodo 
                        AND t_f.fecha='$fecha'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Calendario Comahue', para implementar la opcion
         * "horarios registrados" en el combo tipo.
         * En esta funcion se incluyen las asig_periodo que se corresponden con examenes finales, es enecesaria
         * porque las asig_periodo (examen parcial, evento) y las asig_periodo (examen final) pertenecen a 
         * distintos periodos. 
         */
        function get_asignaciones_periodo_por_fecha_examen ($id_sede, $dia, $id_periodo, $fecha){
            $sql="SELECT 
                      t_a.finalidad, t_a.hora_inicio, t_a.hora_fin, t_a.tipo_asignacion, t_a.id_asignacion,
                      t_au.id_aula, t_au.capacidad, 'Periodo' as tipo, t_a.cantidad_alumnos as cant_alumnos
                  FROM
                      asignacion t_a 
                           JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                           JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))
                           JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion)
                           JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                  WHERE t_au.id_sede=$id_sede 
                        AND t_f.nombre='$dia' 
                        AND t_a.id_periodo=$id_periodo 
                        AND t_f.fecha='$fecha'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion devuelve un cjto asignaciones a partir de una hora especifica. Se utiliza en la 
         * operacion 'Visualizacion de Usos Diarios'.
         * Las asignaciones por periodo tienen prioridad sobre las asignaciones definitivas.
         */
        function get_asignaciones_cuatrimestre_por_hora ($id_sede, $dia, $id_periodo, $fecha, $hora){
            
            $sql_1="SELECT 
                        t_aula.nombre as aula, 
                        t_aula.id_aula,
                        t_aula.capacidad,
                        t_a.hora_inicio,
                        t_a.hora_fin,
                        t_a.finalidad,
                        t_ua.descripcion, 
                        (t_p.nombre || ' ' || t_p.apellido) as responsable 
                           
                  FROM 
                      asignacion t_a 
                           JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                           JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                           JOIN unidad_academica t_ua ON (t_ua.sigla=t_a.facultad)
                           JOIN asignacion_definitiva t_ad ON (t_a.id_asignacion=t_ad.id_asignacion)
                           JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                  WHERE t_aula.id_sede=$id_sede AND t_ad.nombre='$dia' AND t_a.id_periodo=$id_periodo AND "
                    . "( $hora BETWEEN t_a.hora_inicio AND t_a.hora_fin )";
            
            $asig_definitivas=toba::db('rukaja')->consultar($sql_1);
            
            //falta incluir fecha BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin
            $sql_2="SELECT 
                        t_aula.nombre as aula,
                        t_aula.id_aula,
                        t_aula.capacidad,
                        t_a.hora_inicio,
                        t_a.hora_fin,
                        t_a.finalidad,
                        t_ua.descripcion,
                        (t_p.nombre || ' ' || t_p.apellido) as responsable
                           
                    FROM
                        asignacion t_a
                            JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                            JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                            JOIN unidad_academica t_ua ON (t_a.facultad=t_ua.sigla)
                            JOIN asignacion_periodo t_pe ON (t_a.id_asignacion=t_pe.id_asignacion AND ('$fecha' BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin))
                            JOIN esta_formada t_f ON (t_pe.id_asignacion=t_f.id_asignacion)
                            JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                    WHERE t_aula.id_sede=$id_sede 
                          AND t_f.nombre='$dia' 
                          AND t_a.id_periodo=$id_periodo 
                          AND ($hora BETWEEN t_a.hora_inicio AND t_a.hora_fin) 
                          AND t_f.fecha='$fecha' ";
            
            $asig_periodo=toba::db('rukaja')->consultar($sql_2);
                                   
            $this->descartar_asignaciones_definitivas($asig_periodo, &$asig_definitivas);
            
            $this->unificar_asignaciones(&$asig_periodo, $asig_definitivas);
            
            return $asig_periodo;
        }
        
        /*
         * Esta funcion permite obtener todas las asignaciones pertenecientes a un examen final a partir de 
         * una hora especifica. Se usa en Visualizacion de Usos Diarios. 
         */
        function get_asignaciones_examen_final_por_hora ($id_sede, $dia, $id_periodo, $fecha, $hora){
            $sql="SELECT 
                      t_aula.nombre as aula,
                      t_aula.id_aula,
                      t_aula.capacidad, 
                      t_a.hora_inicio,
                      t_a.hora_fin,
                      t_a.finalidad,
                      t_ua.descripcion,
                      (t_p.nombre || ' ' || t_p.apellido) as responsable
                           
                  FROM
                      asignacion t_a
                           JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                           JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                           JOIN unidad_academica t_ua ON (t_a.facultad=t_ua.sigla)
                           JOIN asignacion_periodo t_pe ON (t_a.id_asignacion=t_pe.id_asignacion AND ('$fecha' BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin))
                           JOIN esta_formada t_f ON (t_pe.id_asignacion=t_f.id_asignacion)
                           JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                  WHERE t_aula.id_sede=$id_sede 
                        AND t_f.nombre='$dia' 
                        AND t_a.id_periodo=$id_periodo 
                        AND ($hora BETWEEN t_a.hora_inicio AND t_a.hora_fin) 
                        AND t_f.fecha='$fecha' ";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion devuelve el cjto de horarios ocupados en un aula especifica.
         * Se usa para calcular horarios disponibles en un aula, en la operacion Visualizacion de Usos Diarios.
         * Como debemos devolver las asignaciones para un aula en particular necesitamos el id_aula en lugar del
         * id_sede. 
         */
        function get_asignaciones_por_aula_cuatrimestre ($dia, $id_periodo, $fecha, $id_aula){
            $sql_1="SELECT 
                        t_aula.nombre as aula,
                        t_a.id_aula,
                        t_a.hora_inicio,
                        t_a.hora_fin,
                        t_a.finalidad,
                        t_ua.descripcion, 
                        (t_p.nombre || ' ' || t_p.apellido) as responsable 
                           
                    FROM
                        asignacion t_a 
                            JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                            JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                            JOIN unidad_academica t_ua ON (t_ua.sigla=t_a.facultad)
                            JOIN asignacion_definitiva t_ad ON (t_a.id_asignacion=t_ad.id_asignacion)
                    WHERE t_a.id_aula=$id_aula 
                          AND t_ad.nombre='$dia' 
                          AND t_a.id_periodo=$id_periodo 
                     ";
            
            $asig_definitivas=toba::db('rukaja')->consultar($sql_1);
            
            $sql_2="SELECT 
                        t_aula.nombre as aula,
                        t_a.id_aula,
                        t_a.hora_inicio,
                        t_a.hora_fin,
                        t_a.finalidad,
                        t_ua.descripcion,
                        (t_p.nombre || ' ' || t_p.apellido) as responsable
                           
                    FROM
                        asignacion t_a
                            JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                            JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                            JOIN unidad_academica t_ua ON (t_a.facultad=t_ua.sigla)
                            JOIN asignacion_periodo t_pe ON (t_a.id_asignacion=t_pe.id_asignacion AND ('$fecha' BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin))
                            JOIN esta_formada t_f ON (t_pe.id_asignacion=t_f.id_asignacion)
                    WHERE t_a.id_aula=$id_aula 
                          AND t_f.nombre='$dia' 
                          AND t_a.id_periodo=$id_periodo 
                          AND t_f.fecha='$fecha' ";
            
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
            $sql="SELECT
                      t_aula.nombre as aula,
                      t_a.id_aula,
                      t_a.hora_inicio,
                      t_a.hora_fin,
                      t_a.finalidad,
                      t_ua.descripcion,
                      (t_p.nombre || ' ' || t_p.apellido) as responsable
                           
                  FROM
                      asignacion t_a
                           JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                           JOIN aula t_aula ON (t_a.id_aula=t_aula.id_aula)
                           JOIN unidad_academica t_ua ON (t_a.facultad=t_ua.sigla)
                           JOIN asignacion_periodo t_pe ON (t_a.id_asignacion=t_pe.id_asignacion AND ('$fecha' BETWEEN t_pe.fecha_inicio AND t_pe.fecha_fin))
                           JOIN esta_formada t_f ON (t_pe.id_asignacion=t_f.id_asignacion)
                  WHERE t_a.id_aula=$id_aula 
                        AND t_f.nombre='$dia' 
                        AND t_a.id_periodo=$id_periodo 
                        AND t_f.fecha='$fecha'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Cargar Asignaciones'.El objetivo de esta funcion es devolver 
         * un cjto. de asignaciones, pertenecientes al periodo actual, para que se puedan editar o borrar.
         * El periodo actual se obtiene a partir de la fecha actual.
         */
        function get_asignaciones ($where, $id_periodo){
            //El JOIN con dia es necesario porque sino el where no nos sirve.
            $sql="( SELECT t_a.id_asignacion, t_a.nro_doc, t_a.tipo_doc, 
                           t_a.finalidad, t_a.hora_inicio, t_a.hora_fin, t_dia.nombre as dia, 
                           (t_a.nombre || ' ' || t_a.apellido) as responsable, 'Definitiva' as tipo,
                           t_a.tipo_asignacion, t_per.anio_lectivo
                    FROM 
                        asignacion t_a 
                            JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                                        
                            JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                            JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion )
                            JOIN dia t_dia ON (t_d.nombre=t_dia.nombre)
                    WHERE ($where) 
                          AND t_a.id_periodo=$id_periodo) 
                   
                    UNION 
                   
                   (SELECT t_a.id_asignacion, t_a.nro_doc, t_a.tipo_doc, 
                           t_a.finalidad, t_a.hora_inicio, t_a.hora_fin, t_dia.nombre as dia, 
                           (t_a.nombre || ' ' || t_a.apellido) as responsable, 'Periodo' as tipo,
                           t_a.tipo_asignacion, t_per.anio_lectivo
                    FROM 
                        asignacion t_a 
                            JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                                        
                            JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                            JOIN asignacion_periodo t_pe ON (t_a.id_asignacion=t_pe.id_asignacion)
                            JOIN esta_formada t_f ON (t_pe.id_asignacion=t_f.id_asignacion)
                            JOIN dia t_dia ON (t_f.nombre=t_dia.nombre)
                    WHERE ($where) 
                          AND t_a.id_periodo=$id_periodo)";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Memorando por Fecha'. Permite obtener las asignaciones para un
         * dia especifico de la semana cuando la fecha seleccionada cae dentro de un periodo cuatrimestre.
         */
        function get_asignaciones_memo_por_cuatrimestre ($dia, $periodo, $id_sede, $fecha){
            
            $sql_2="SELECT 
                        t_a.id_asignacion, 
                        t_a.finalidad,
                        t_a.hora_inicio,
                        t_a.hora_fin,
                        t_au.nombre as aula,
                        t_a.facultad,
                        (t_pe.nombre || ' ' || t_pe.apellido) as responsable
                    FROM 
                        asignacion t_a 
                            JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                            JOIN persona t_pe ON (t_a.nro_doc=t_pe.nro_doc)
                            JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))
                            JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion )
                    WHERE t_au.id_sede=$id_sede 
                          AND t_f.nombre='$dia' 
                          AND t_a.id_periodo=$periodo 
                          AND t_f.fecha='$fecha'";
            
            $asig_periodo=toba::db('rukaja')->consultar($sql_2);
                        
            return $asig_periodo;
        }
        
        /*
         * @$fecha: contiene una fecha seleccionada de un calendario.
         */
        function get_asignaciones_memo_por_examen_final ($dia, $periodo, $id_sede, $fecha){           
            $sql_2="SELECT 
                        t_a.finalidad,
                        t_a.hora_inicio,
                        t_a.hora_fin,
                        t_au.nombre as aula,
                        t_a.facultad,
                        (t_pe.nombre || ' ' || t_pe.apellido) as responsable
                    FROM
                        asignacion t_a 
                            JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                            JOIN persona t_pe ON (t_a.nro_doc=t_pe.nro_doc)
                            JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))
                            JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion )
                    WHERE t_au.id_sede=$id_sede 
                          AND t_f.nombre='$dia' 
                          AND t_a.id_periodo=$periodo 
                          AND t_f.fecha='$fecha'";
            
            return toba::db('rukaja')->consultar($sql_2);           
            
        }
        
        /*
         * Esta funcion se usa en la operacion 'Asignaciones por Dia'. Permite devolver las asignaciones definitivas 
         * para un dia de la semana. Las asignaciones retornadas confeccionan el reporte.
         * @id_periodo = es el id_periodo del cuatrimestre elegido. Si no estan TODOS los datos presentes, el 
         * string dato_celda se devuelve vacio. 
         * @dia = contiene un dia de la semana, es util para obtener las asignaciones correspondientes.
         */
        function get_asignaciones_por_dia ($id_periodo, $dia){
            
            $sql="SELECT t_a.hora_inicio, t_a.hora_fin, t_au.id_aula, t_a.facultad,
                         (t_a.finalidad || ' - ' || t_a.facultad || ' - ' || t_a.hora_inicio || ' a ' || t_a.hora_fin || ' - ' || t_a.cantidad_alumnos || 
                          ' ALUMNOS - ' || t_a.nombre || ' ' || t_a.apellido) as dato_celda 
                  FROM 
                      asignacion t_a 
                            JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                            JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
                  
                  WHERE t_a.id_periodo=$id_periodo 
                        AND t_d.nombre='$dia'";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * En ppio esta operacion se utilizaria en 'Buscador de Aulas o Seleccionar Aula'. Ayuda a verificar que 
         * no se produzca solapamiento de asignaciones.
         */
        function get_asignaciones_periodo_ ($id_sede, $dia){
            //Obtenemos la fecha actual para obtener todas las asignaciones periodicas
            //que pueden estar solapadas con la asignacion que se quiere registrar.
            $fecha_actual=date('Y-m-d');
            $anio=date('Y');
            //Si no filtramos por periodo, hacemos una busqueda general (que es lo que necesitamos) teniendo
            //en cuenta todos los periodos academicos, cuatrimestre, examen final etc.
            $sql="SELECT t_a.hora_inicio,
                         t_a.hora_fin,
                         t_a.id_aula,
                         t_au.nombre as aula,
                         t_au.capacidad
                         
                  FROM 
                      asignacion t_a 
                           JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                           JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                           JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND t_p.fecha_inicio>='$fecha_actual')
                           JOIN esta_formada t_ef ON (t_p.id_asignacion=t_ef.id_asignacion)
                  WHERE t_au.id_sede=$id_sede 
                        AND t_ef.nombre='$dia' 
                        AND t_per.anio_lectivo=$anio";
            
            return toba::db('rukaja')->consultar($sql);
            
        }
        
        /*
         * Esta funcion se utiliza en la operacion 'Seleccionar Aula'. Permite obtener todas las asignaciones 
         * periodicas petenecientes a un turno de examen. Utilizamos la fecha_actual para filtrar turnos de 
         * examenes.
         * @$id_periodo: contiene el id_ de un turno de examen incluido en un cuatrimestre.
         */
        function get_asignaciones_examen_final_ ($id_sede, $dia, $id_periodo){
            $fecha_actual=date('Y-m-d');
            $sql="SELECT t_a.hora_inicio, t_a.hora_fin, t_a.id_aula, 
                         t_au.nombre as aula, t_f.fecha
                  FROM 
                      asignacion t_a 
                           JOIN aula t_au ON (t_a.id_aula=t_au.id_aula) 
                           JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                           JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ((t_p.fecha_inicio >= '$fecha_actual') OR ('$fecha_actual' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin)))
                           JOIN esta_formada t_ef ON (t_p.id_asignacion=t_ef.id_asignacion AND t_ef.nombre='$dia')
                  WHERE t_au.id_sede=$id_sede 
                        AND t_a.id_periodo=$id_periodo";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Se utiliza en la operacion 'Buscador de Aulas o Seleccionar Aula'. Permite obtener las asignaciones
         * periodicas para un cuatrimestre. Para obtener estas asignaciones necesitamos usar la fecha_actual
         * filtramos todas las asignaciones por periodo cuya fecha_inicio sea mayor a fecha_actual o 
         * que contengan a fecha_actual.
         * @$id_periodo: contiene el id_ que identifica a un cuatrimestre.
         */
        function get_asignaciones_periodo_cuatrimestre ($id_sede, $dia, $id_periodo){
            $fecha_actual=date('Y-m-d');
            $sql="SELECT t_a.hora_inicio, t_a.hora_fin, t_a.id_aula, 
                         t_au.nombre as aula, t_ef.fecha
                  FROM 
                       asignacion t_a
                            JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                            JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                            JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion AND ((t_p.fecha_inicio >= '$fecha_actual') OR ('$fecha_actual' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin)))
                            JOIN esta_formada t_ef ON (t_p.id_asignacion=t_ef.id_asignacion)
                  WHERE t_au.id_sede=$id_sede 
                        AND t_ef.nombre='$dia' 
                        AND t_a.id_periodo=$id_periodo";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_asignacion_definitiva ($id_asignacion){
            $sql="SELECT t_a.*, t_au.nombre as aula, t_d.nombre as dia_semana, 
                         'Definitiva' as tipo
                  FROM
                      asignacion t_a 
                           JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                           JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                           JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
                  WHERE t_a.id_asignacion=$id_asignacion";
            
            return toba::db('rukaja')->consultar($sql);
            
        }
        
        function get_asignacion_periodo ($id_asignacion){
            $sql="SELECT 
                      t_a.*, t_au.nombre as aula, t_p.*, 'Periodo' as tipo
                  FROM
                      asignacion t_a 
                           JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                           JOIN periodo t_per ON (t_a.id_periodo=t_per.id_periodo)
                           JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion)
                  WHERE t_a.id_asignacion=$id_asignacion";
                
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_dias_periodo ($id_asignacion){
            $sql="SELECT DISTINCT t_f.nombre
                  FROM 
                      esta_formada t_f 
                  WHERE t_f.id_asignacion=$id_asignacion";
            
            return toba::db('rukaja')->consultar($sql);
        }
        
        function get_titular ($id_asignacion){
            $sql="SELECT 
                      nombre, apellido, nro_doc, tipo_doc, legajo, 'TITULAR' as tipo
                  FROM
                      asignacion
                  WHERE id_asignacion=$id_asignacion";
            
            return toba::db('rukaja')->consultar($sql);
        }
}
?>