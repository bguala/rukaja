<?php

require_once(toba_dir().'/proyectos/rukaja/php/api/Calendario.php');
require_once(toba_dir().'/proyectos/rukaja/php/api/HorariosDisponibles.php');

/*
 * Esta operacion permite cargar, en el sistema, asignaciones definitivas o periodicas. Esta operacion no permite 
 * el solapamiento de asignaciones en un mismo aula. Esta caracteristica se debe ver reflejada durante todo
 * un periodo academico. 
 */
class ci_cargar_asignaciones extends toba_ci
{
        protected $s__contador;
        protected $s__tipo;                             //guardamos el tipo de asignacion que elige el usuario
        protected $s__dia;                              //guardamos el dia elegido por el usuario en caso de seleccionar tipo Definitiva
        protected $s__datos_responsable;
        protected $s__datos_cuadro;                     //guarda una lista de personas (docentes y no docentes), se utiliza para cargar cuadro_personas de la pantalla pant_persona
        protected $s__datos_cuadro_asig;                //para cargar todas las asignaciones de un docente en un cuatrimestre
        
        protected $s__accion;                           //guardamos el tipo de accion elegida en los eventos del cuadro y en el boton cargar asignaciones del filtro
        protected $s__datos_cuadro_asignaciones;        //para cargar el cuadro de la pantalla pant_edicion
        protected $s__id_asignacion;                    //guardamos el id del registro seleccionado en la pantalla pant_edicion
        protected $s__datos_form;                       //guardamos datos para cargar al formulario form_asignacion
        protected $s__where;                            //guardamos el where que genera el filtro de la pantalla pant_edicion
        protected $s__horarios_disponibles;
        
        protected $s__aula_disponible;                  //guarda los datos enviados desde la operacion "aulas disponibles"
        protected $s__fechas=array();                   //guarda una lista de fechas asociadas a una asignacion_periodo
        protected $s__docentes=array();                 //guarda una listas de docentes filtrados para cargar el cuadro_docentes en la pantalla pant_catedra
        protected $s__datos_form_asignacion;            //guarda los datos cargados en el form_asignacion, sirve para mantener eñ estado del formulario cuando nos cambiamos a las pantallas pant_extra o pant_catedra
        protected $s__docentes_seleccionados=array();   //contiene los miembros del equipo de catedra
        protected $s__cargar_fechas;
        
        protected $s__id_sede;
        protected $s__fecha_consulta;
        protected $s__dia_consulta;
        protected $s__pantalla_actual;                  //guardamos la pantalla donde actualmente operamos. Es importante para retroceder.
        protected $s__periodo_analizado=false;
        protected $s__id_docente;
        protected $s__edicion;
        protected $s__hora_inicio;
        protected $s__hora_fin;
        protected $s__equipo_catedra;
                
        //Guardamos la cantidad de dias que forman a un mes. Se configura teniendo en cuenta anios bisiestos.
        protected $_meses=array(
            1 => 31, 2 => 0, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31
        );
    
        //Guardamos los dias de la semana, esto es util para listar los dias correctos de un periodo.
        protected $_dias=array(
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
        );
        
        //#5252C8
        //Secuencia de asignacion_periodo : asignacion_periodo_id_asignacion_seq
        //Secuencia de esta_formada :  esta_formada_id_asignacion_seq
        
        //-----------------------------------------------------------------------------------
        //---- Pant Edicion -----------------------------------------------------------------
        //-----------------------------------------------------------------------------------
                
        //---- Formulario -------------------------------------------------------------------
        
        /*
         * Este metodo nos permite guardar los datos transmitidos desde la operacion 'Calendario Comahue' 
         * a treves del vinculador.
         */
        function ini__operacion (){
            
            //Obtenemos el arreglo almacenado en la operacion "Calendario Comahue". Su formato es :
            //Array ('id_aula'=>x 'hora_inicio'=>y 'hora_fin'=>z, dia_semana=>w)
            $datos_ad=toba::memoria()->get_parametros();
            
            if(isset($datos_ad['id_aula'])){
                $this->s__accion="Vinculo";
                $this->s__aula_disponible=$datos_ad;
                $this->s__hora_inicio=$datos_ad['hora_inicio'];
                $this->s__hora_fin=$datos_ad['hora_fin'];
                //--Eliminamos la informacion guardada en el arreglo $_SESSION.
                toba::memoria()->limpiar_memoria();
                
            }
            
        }
                
	//---- Filtro -----------------------------------------------------------------------
        
        /*
         * Filtro para realizar busquedas de asignaciones en el sistema para el periodo actual.
         */
        function conf__filtro (toba_ei_filtro $filtro){
            $this->pantalla()->tab('pant_edicion')->activar();
            $this->pantalla()->tab('pant_asignacion')->desactivar();
            $this->pantalla()->tab('pant_extra')->desactivar();
            $this->pantalla()->tab('pant_catedra')->desactivar();
        }
        
        function evt__filtro__filtrar ($datos){
            
            if(count($datos)>0){
                $this->s__where=$this->dep('filtro')->get_sql_where('OR');    
            }
            
        }
                
        //---- Cuadro -----------------------------------------------------------------------
        
        /*
         * Este cuadro contiene todas las asignaciones que se quieren borrar o editar en el periodo actual.
         */
        function conf__cuadro (toba_ei_cuadro $cuadro){
            if(isset($this->s__where)){
                $cuadro->descolapsar();
                //--Obtenemos la sede para el usuario logueado.
                $this->s__id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
                
                $periodos=$this->dep('datos')->tabla('periodo')->get_periodos_academicos($this->s__id_sede);
                
                $cuadro->set_datos($this->procesar_periodo($periodos, "hr"));
                $cuadro->set_titulo("Listado de asignaciones");
            }
            else{
                $cuadro->colapsar();
            }
        }
            
        
        function evt__cuadro__editar ($datos){
            $this->s__accion="Editar";
            $this->s__id_asignacion=$datos['id_asignacion'];
            
            //--Guardamos el equipo de catedra para editarlo en la pantalla catedra.
            $this->s__docentes_seleccionados=$this->dep('datos')->tabla('persona')->get_catedra($this->s__id_asignacion);
            //--Conservamos una copia del equipo de catedra para hacer chequeos en procesar_edicion.
            $this->s__equipo_catedra=$this->s__docentes_seleccionados;
            
            //--Obtenemos las asignaciones de un docente.                 
            $this->obtener_asignacion($datos);
            
            //$this->s__nro_doc=$datos['nro_doc'];
            //$this->s__tipo_doc=$datos['tipo_doc'];
            
            //$this->obtener_asignaciones();
            //$this->s__tipo=$datos['tipo'];
            
            $this->set_pantalla('pant_asignacion');
        }
        
        /*
         * Esta funcion se utiliza para buscar asignaciones definitivas o periodicas, para su posterior edicion
         * o borrado. En la variable s__edicion queda guardada la asignacion correspondiente. Esta variable se 
         * utiliza para cargar el formulario 'form_asignacion' con datos por defecto.
         * @$datos: contiene el registro seleccionado del cuadro 'cuadro'.
         */
        function obtener_asignacion ($datos){
            switch($datos['tipo_asignacion']){
                case 'CURSADA'        : $asignacion_definitiva=$this->dep('datos')->tabla('asignacion')->get_asignacion_definitiva($datos['id_asignacion']);
                                        
                                        $this->s__edicion=$asignacion_definitiva;
                                        $this->s__tipo="Definitiva";
                                        $this->s__id_docente=$asignacion_definitiva[0]['id_responsable_aula'];
                                        break;
                                    
                case 'EXAMEN PARCIAL' : break;
                case 'EXAMEN FINAL'   : break;
                
                case 'EVENTO'         : 
                case 'CONSULTA'       : $asignacion_periodo=$this->dep('datos')->tabla('asignacion')->get_asignacion_periodo($datos['id_asignacion']);
                                        
                                        $this->s__edicion=$asignacion_periodo;
                                        $this->s__tipo="Periodo";
                                        $this->s__id_docente=$asignacion_periodo[0]['id_responsable_aula'];
                                        
                                        break;
            }
        }
        
        function evt__cuadro__borrar ($datos){
            $this->s__accion="Borrar";
            $this->s__id_asignacion=$datos['id_asignacion'];
            
            $this->obtener_asignacion($datos);
//            $this->s__tipo=$datos['tipo'];
//            //para obtener las asignaciones de un docente
//            $this->s__nro_doc=$datos['nro_doc'];
//            $this->s__tipo_doc=$datos['tipo_doc'];
//            $this->s__responsable_de_aula=$datos['responsable'];
            $this->set_pantalla('pant_asignacion');
        }
                
        /*
         * Devuelve todos los dias de la semana para cargar el combo tipo 
         */
        function dias_semana (){            
            $sql="SELECT nombre FROM dia ORDER BY orden";
            return toba::db('rukaja')->consultar($sql);
        }
        
        function obtener_dia ($dia_numerico){
            $dias=array( 
                         1 => 'Lunes', 
                         2 => 'Martes',
                         3 => 'Miércoles', 
                         4 => 'Jueves', 
                         5 => 'Viernes', 
                         6 => 'Sábado'
                );
                        
            return ($dias[$dia_numerico]);
        }
        
        /*
         * Metodo de consulta para cargar el combo periodo. Necesitamos usar el id_sede, por ese motivo se 
         * implementa en el ci.
         */
        function get_periodos_activos ($tipo_asignacion){
            //La fecha actual nos ayuda a pensar esto: podemos cargar asignaciones en esta misma fecha o mas 
            //adelante, entonces necesitamos los periodos registrados en el sistema posteriores a esta fecha 
            //o los que contiene a la misma.
            $fecha=date('Y-m-d');
            $anio_lectivo=date('Y');
            $id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
            switch($tipo_asignacion){
                case 'CURSADA'        : 
                case 'EXAMEN PARCIAL' :
                case 'EVENTO'         : //|| ' ( ' || t_p.fecha_inicio || ' - ' || t_p.fecha_fin || ' ) '
                case 'CONSULTA'       : $sql_1="SELECT t_p.id_periodo,
                                                       t_c.numero || ' ' || 'CUATRIMESTRE' || 
                                                       ' ( ' || extract(day from t_p.fecha_inicio) || '/' || 
                                                       extract(month from t_p.fecha_inicio) || '/' || extract(year from t_p.fecha_inicio) || ' - ' ||
                                                       extract(day from t_p.fecha_fin) || '/' || extract(month from t_p.fecha_fin) || '/' ||
                                                       extract(year from t_p.fecha_fin) || ' ) '  as descripcion 
                                                FROM periodo t_p 
                                                JOIN cuatrimestre t_c ON (t_p.id_periodo=t_c.id_periodo AND t_p.anio_lectivo=$anio_lectivo  
                                                 "
                                                . "AND t_p.id_sede=$id_sede)";
                                        $periodo=toba::db('rukaja')->consultar($sql_1);
                                        break;
                
                case 'EXAMEN FINAL'   : $sql_2="SELECT t_p.id_periodo,
                                                       'TURNO DE EXAMEN' || ' ' || t_ef.turno || ' ' || t_ef.numero || ' ' || 'LLAMADO' ||
                                                       ' ( ' || extract(day from t_p.fecha_inicio) || '/' || extract(month from t_p.fecha_inicio) ||
                                                       '/' || extract(year from t_p.fecha_inicio) || ' - ' ||
                                                       extract(day from t_p.fecha_fin) || '/' || extract(month from t_p.fecha_fin) || '/' ||
                                                       extract(year from t_p.fecha_fin) || ' ) ' as descripcion
                                                FROM periodo t_p 
                                                JOIN examen_final t_ef ON (t_p.id_periodo=t_ef.id_periodo AND t_p.anio_lectivo=$anio_lectivo  
                                                AND (('$fecha' <= t_p.fecha_inicio) OR ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))"
                                                . "AND t_p.id_sede=$id_sede)";
                                        $periodo=toba::db('rukaja')->consultar($sql_2);
                                        break;
                default : $sql_3="SELECT t_p.id_periodo,
                                         'CURSO DE INGRESO' || ' ' || t_ci.facultad || ' ' || t_ci.nombre || ' ( ' || 
                                         extract(day from t_p.fecha_inicio) || '/' || extract(month from t_p.fecha_inicio) || '/' ||
                                         extract(year from t_p.fecha_inicio) || ' - ' || extract(day from t_p.fecha_fin) || '/' ||
                                         extract(month from t_p.fecha_fin) || '/' || extract(year from t_p.fecha_fin) || ' ) ' as descripcion
                                  FROM periodo t_p 
                                  JOIN curso_ingreso t_ci ON (t_p.id_periodo=t_ci.id_periodo AND t_p.anio_lectivo=$anio_lectivo  
                                  AND (('$fecha' <= t_p.fecha_inicio) OR ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin)) "
                                  . "AND t_p.id_sede=$id_sede)";
                          $curso_ingreso=toba::db('rukaja')->consultar($sql_3);
                          break;
                
            }
            
            //$this->unificar_periodos(&$cuatrimestre, $examen_final);
            
            //$this->unificar_periodos(&$cuatrimestre, $curso_ingreso);
                       
            return $periodo;            
            
        }
        
        /*
         * Metodo de consulta para cargar el combo periodo. Necesitamos usar el id_sede.
         */
        function get_periodos_academicos (){
            //La fecha actual nos ayuda a pensar esto: podemos cargar asignaciones en esta misma fecha o mas 
            //adelante, entonces necesitamos los periodos registrados en el sistema posteriores a esta fecha 
            //o los que contiene a la misma.
            $fecha=date('Y-m-d');
            $anio_lectivo=date('Y');
            $id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
            
            $sql_1="SELECT t_p.id_periodo,
                           t_c.numero || ' ' || 'CUATRIMESTRE' as descripcion 
                    FROM periodo t_p 
                    JOIN cuatrimestre t_c ON (t_p.id_periodo=t_c.id_periodo AND t_p.anio_lectivo=$anio_lectivo  
                    AND (('$fecha' <= t_p.fecha_inicio) OR ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin)) "
                    . "AND t_p.id_sede=$id_sede)";
            $cuatrimestre=toba::db('rukaja')->consultar($sql_1);
                         
                
            $sql_2="SELECT t_p.id_periodo,
                           'TURNO DE EXAMEN' || ' ' || t_ef.turno || ' ' || t_ef.numero || ' ' || 'LLAMADO' as descripcion
                    FROM periodo t_p 
                    JOIN examen_final t_ef ON (t_p.id_periodo=t_ef.id_periodo AND t_p.anio_lectivo=$anio_lectivo  
                    AND (('$fecha' <= t_p.fecha_inicio) OR ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin))"
                    . "AND t_p.id_sede=$id_sede)";
            $examen_final=toba::db('rukaja')->consultar($sql_2);
                                        
            $sql_3="SELECT t_p.id_periodo,
                           'CURSO DE INGRESO' || ' ' || t_ci.facultad || ' ' || t_ci.nombre as descripcion
                    FROM periodo t_p 
                    JOIN curso_ingreso t_ci ON (t_p.id_periodo=t_ci.id_periodo AND t_p.anio_lectivo=$anio_lectivo  
                    AND (('$fecha' <= t_p.fecha_inicio) OR ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin)) "
                    . "AND t_p.id_sede=$id_sede)";
            $curso_ingreso=toba::db('rukaja')->consultar($sql_3);           
            
            $this->unificar_periodos(&$cuatrimestre, $examen_final);
            
            $this->unificar_periodos(&$cuatrimestre, $curso_ingreso);
                       
            return $cuatrimestre;            
            
        }
        
         /*
         * Esta funcion realiza una union de conjuntos.
         */
        function unificar_periodos ($periodo, $conjunto){
            foreach ($conjunto as $clave=>$valor){
                if(isset($valor)){
                   $periodo[]=$valor; //agrega al final
                }
            }            
        }
        
        function evt__volver (){
            switch($this->s__pantalla_actual){
                case "pant_asignacion" : $this->set_pantalla('pant_edicion'); break;
                case "pant_extra" :
                case "pant_catedra" : $this->set_pantalla('pant_asignacion'); break;
            }
            
        }
                
        //---- Cuadro Personas --------------------------------------------------------------
	function conf__cuadro_personas (toba_ei_cuadro $cuadro)
	{
            if(isset($this->s__datos_cuadro)){
                $cuadro->set_titulo("Listado de personas u organizaciones");
                //esta variable se carga en el evento filtrar de filtro_busqueda
                $cuadro->set_datos($this->s__datos_cuadro);
                
            }
            else{
                $cuadro->colapsar();
            }
	}

	function evt__cuadro_personas__seleccionar ($datos)
	{
            
            //$this->s__nro_doc=$datos['nro_doc'];
            //$this->s__tipo_doc=$datos['tipo_doc'];
            if(strcmp($datos['tipo_agente'], 'Org')==0){
                //Anulamos el apellido (-----) para no concatenarlo con el nombre de una organizacion en 
                //form_datos.
                $datos['apellido']=' ';
            }
            $this->s__datos_responsable=$datos;
            //$this->s__responsable_de_aula=$datos['nombre'].' '.$datos['apellido'];
            //Guardamos el id_docente o el id_organizacion. 
            $this->s__id_docente=$datos['id_responsable_aula'];
            //$this->obtener_asignaciones();
            $this->set_pantalla('pant_asignacion');
	}
        
        //---- Filtro Busqueda -------------------------------------------------------------------
        
        function conf__filtro_busqueda (toba_ei_filtro $filtro){
             
            $filtro->get_sql_clausulas();
            
            $this->s__id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
            
        }
        
        function evt__filtro_busqueda__filtrar (){
            //Verificamos si existen aulas administradas por el usuario actual.
            $aulas=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($this->s__id_sede);
            if(count($aulas)==0){
                $mensaje="No existen aulas registradas en el sistema.";
                toba::notificacion()->agregar($mensaje, 'info');
                return ;
            }
            
            //verificamos si el usuario ingreso datos de busqueda.
            if(count($this->dep('filtro_busqueda')->get_sql_clausulas())==0){
                $mensaje="No se admiten valores nulos para realizar busquedas en el sistema de personas u organizaciones";
                toba::notificacion()->agregar($mensaje, 'info');
                return; //finalizamos la ejecucion del programa.
            }
            
            //Es necesario que existan periodos academicos registrados en el sistema para cargar asignaciones.
            //$periodos=$this->dep('datos')->tabla('periodo')->get_listado(date('Y'), $this->s__id_sede);
            $periodos=$this->get_periodos_academicos();
            
            if(count($periodos)>0){
                if(!isset($this->s__aula_disponible)){
                    $this->s__accion="Registrar";
                }
                               
                                
                $atributos_filtro=$this->dep('filtro_busqueda')->get_sql_clausulas();
                $sql_docente="";
                //Creamos nuestro propio where sql. 
                foreach ($atributos_filtro as $clave=>$valor){
                    //La informacion especificada en el filtro puede involucrar a distintas entidades, entre ellas
                    //docentes u organizaciones.
                    switch ($clave){
                        case 'nombre'    : 
                        case 'apellido'  :
                        case 'nro_doc'   : $sql_docente .= (strlen($sql_docente)==0) ? $valor : (" OR ".$valor);
                                           break;
                    }
                }
                
                //Obtenemos responsables de aula para cargar el cuadro_personas.
                $personas=$this->dep('datos')->tabla('persona')->get_personas(strtoupper($sql_docente), $atributos_filtro['organizacion']);
                
                //$personas=$this->dep('datos')->tabla('persona')->get_personas(strtoupper($where));
            
                if(count($personas) == 0){
                    toba::notificacion()->error(utf8_decode("  No existen personas registradas en el sistema con los parámetros especificados.  "));
                }
                else{
                    $this->s__datos_cuadro=$personas;
                }
                //$this->set_pantalla('pant_persona');
            }
            else{
                $mensaje=" No existen períodos académicos registrados en el sistema. ";
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
            }
                        
        }
        
        function evt__filtro_busqueda__registrar (){
            $this->s__contador += 1;
        }
        
        //---- Formulario ------------------------------------------------------------------------
        
        /*
         * Nos permite registrar organizaciones en el sistema. 
         */
        function conf__formulario (toba_ei_formulario $form){
            //Otra forma de colapsar ei.
            if(($this->s__contador % 2)==0){
                $form->colapsar();
            }
            else{
                $form->descolapsar();
                $form->set_titulo("Formulario para registrar organizaciones en el sistema");
            }
            
        }
        
        function evt__formulario__alta ($datos){
            //Persistir en la tabla organizacion. Despues ir a la pantalla pant_asignacion.     
            $this->dep('datos')->tabla('organizacion')->nueva_fila($datos);
            $this->dep('datos')->tabla('organizacion')->sincronizar();
            $this->dep('datos')->tabla('organizacion')->resetear();
                        
            //$this->s__nro_doc="******************";
            //$this->s__tipo_doc="******************";
            $this->s__datos_responsable['nro_doc']="******************";
            $this->s__datos_responsable['tipo_doc']="******************";
            $this->s__id_docente=  recuperar_secuencia('organizacion_id_organizacion_seq');
            //$this->s__nombre=$datos['nombre'];
            //$this->s__apellido='';
            $this->s__datos_responsable['nombre']=$datos['nombre'];
            $this->s__datos_responsable['apellido']='';
            $this->set_pantalla('pant_asignacion');
        }
        
        function evt__formulario__cancelar (){
            $this->s__contador += 1;
        }
                
        //---------------------------------------------------------------------------------------
        //---- Pant_Asignacion ------------------------------------------------------------------
        //---------------------------------------------------------------------------------------
        
        function conf__pant_asignacion (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab('pant_edicion')->desactivar();
            //$this->pantalla()->tab('pant_persona')->ocultar();
            $this->pantalla()->tab('pant_extra')->desactivar();
            $this->pantalla()->tab('pant_catedra')->desactivar();
            $this->s__pantalla_actual="pant_asignacion";
        }
        
        //---- Form_Datos -----------------------------------------------------------------------
        
        function conf__form_datos (toba_ei_formulario $form){
//            $form->ef('tipo')->set_estado($this->s__tipo);
            switch($this->s__accion){
                case "Vinculo"   : 
                case "Registrar" : $form->ef('responsable')->set_estado($this->s__datos_responsable['nombre'] . ' ' . $this->s__datos_responsable['apellido']);
                                   $form->ef('nro_doc')->set_estado($this->s__datos_responsable['nro_doc']);
                                   $form->ef('tipo_doc')->set_estado($this->s__datos_responsable['tipo_doc']);
                                   break;               
                case "Borrar"    :
                case "Editar"    :
                case "Confirmar" :
                case "Cambiar"   : $form->set_datos($this->datos_responsable_de_aula());
                                   break;
            }
                        
        }
        
        function datos_responsable_de_aula (){
            $sql="SELECT t_p.nro_doc, t_p.tipo_doc, (t_p.nombre || ' ' || t_p.apellido) as responsable
                  FROM persona t_p 
                  JOIN asignacion t_a ON (t_p.nro_doc=t_a.nro_doc) 
                  WHERE t_a.id_asignacion={$this->s__id_asignacion}";
            $asignacion=toba::db('rukaja')->consultar($sql);
            
            return ($asignacion[0]);
        }
        
        /*
         * genera un arreglo con las aulas utilizadas en un dia especifico.
         * @espacios_concedidos contiene todos los espacios concedidos en las aulas de una Unidad Academica.  
         */
        function obtener_aulas ($espacios_concedidos){
            $aulas=array();
            $indice=0;
            foreach($espacios_concedidos as $clave=>$valor){
                $aula=array(); // indice => (aula, id_aula)
                $aula['aula']=$valor['aula'];
                $aula['id_aula']=$valor['id_aula'];
                //$aula=$valor['aula'];
                $existe=$this->existe($aulas, $aula);
                if(!$existe){
                    $aulas[$indice]=$aula;
                    $indice += 1;
                }
            }
            return $aulas;
        }
        
        /*
         * verifica si un aula ya se encuentra presente en la estructura aulas
         * @$aulas : contiene un cjto de aulas
         * @$aula : se verifica que si exista en aulas.
         */
        function existe ($aulas, $aula){
            $existe=FALSE;
            
            if(count($aulas) != 0){
                $indice=0;
                $longitud=count($aulas);
                while(($indice < $longitud) && !$existe){
                    //strcmp($aulas[$indice]['id_aula'], $aula['id_aula'])==0
                    $existe=($aulas[$indice]['id_aula'] == $aula['id_aula']) ? TRUE : FALSE;
                    $indice += 1;
                }
            }
            return $existe;
        }
        
        //---- Cuadro Asignaciones ----------------------------------------------------------------
        
        function conf__cuadro_asignaciones (toba_ei_cuadro $cuadro){
            //Este cuadro se actualiza por cada asignacion cargada.
            $this->obtener_asignaciones();
            if(count($this->s__datos_cuadro_asig) > 0){
                $cuadro->set_titulo("Asignaciones de {$this->s__datos_responsable['nombre']} {$this->s__datos_responsable['apellido']}");
                $cuadro->set_datos($this->s__datos_cuadro_asig);
            }
        }
        
        //---- Form Asignacion --------------------------------------------------------------------
        
        function conf__form_asignacion (toba_ei_formulario $form){
            //Conservamos los datos cargados por el usuario si nos cambiamos a la pantalla catedra.
            if(count($this->s__datos_form_asignacion)>0){
                $form->set_datos($this->s__datos_form_asignacion);
            }
            
            if(strcmp($this->s__accion, "Vinculo") != 0){
                $form->set_titulo("Formulario para {$this->s__accion} Asignaciones");
            }
                        
            switch($this->s__accion){
                case "Nop"       : break;
                case "Registrar" : break;
                case "Vinculo"   :                                   
                                   $form->colapsar();                                   
                                   break;
                case "Borrar"    : 
                case "Editar"    : 
                case "Cambiar"   : 
                case "Confirmar" : 
                                   switch ($this->s__tipo){
                                        case "Definitiva" : //--s__datos_form_asignacion conserva el estado actual del
                                                            //formulario. Permite restaurarlo cuando nos cambiamos a la 
                                                            //pantalla pant_catedra.
                                                            if(count($this->s__datos_form_asignacion)==0){                                                            
                                                                //--Cargamos lo justo y necesario para no daniar al formulario.
                                                                $datos=array(
                                                                    'tipo_asignacion' => $this->s__edicion[0]['tipo_asignacion'],
                                                                    'finalidad' => $this->s__edicion[0]['finalidad'],
                                                                    'descripcion' => $this->s__edicion[0]['descripcion'],
                                                                    'modulo' => $this->s__edicion[0]['modulo'],
                                                                    'id_periodo' => $this->s__edicion[0]['id_periodo'],
                                                                    'dia_semana' => $this->s__edicion[0]['dia_semana'],
                                                                    'hora_inicio' => $this->s__edicion[0]['hora_inicio'],
                                                                    'hora_fin' => $this->s__edicion[0]['hora_fin'],
                                                                    'facultad' => $this->s__edicion[0]['facultad'],
                                                                    'id_aula'   => $this->s__edicion[0]['id_aula'],
                                                                    'cantidad_alumnos' => $this->s__edicion[0]['cantidad_alumnos'],
                                                                    'tipo' => "Definitiva"
                                                                );

                                                                $form->set_datos($datos);
                                                                $form->ef('tipo_asignacion')->set_solo_lectura();
                                                            }
                                                            break;
                                                        
                                        case "Periodo"    : switch($this->s__edicion[0]['tipo_asignacion']){
                                                                case 'EXAMEN PARCIAL' : 
                                                                case 'EXAMEN FINAL'   : 
                                                                                        break;
                                                                
                                                                case 'CONSULTA'       : 
                                                                case 'EVENTO'         : $lista_dias=$this->dep('datos')->tabla('asignacion')->get_dias_periodo($this->s__edicion[0]['id_asignacion']);
                                                                                        
                                                                                        $datos=array(
                                                                                            'tipo_asignacion' => $this->s__edicion[0]['tipo_asignacion'],
                                                                                            'finalidad' => $this->s__edicion[0]['finalidad'],
                                                                                            'descripcion' => $this->s__edicion[0]['descripcion'],
                                                                                            'modulo' => $this->s__edicion[0]['modulo'],
                                                                                            'id_periodo' => $this->s__edicion[0]['id_periodo'],
                                                                                            'fecha_inicio' => $this->s__edicion[0]['fecha_inicio'],
                                                                                            'fecha_fin' => $this->s__edicion[0]['fecha_fin'],
                                                                                            'dias' => $this->obtener_dias_seleccionados($lista_dias),
                                                                                            'hora_inicio' => $this->s__edicion[0]['hora_inicio'],
                                                                                            'hora_fin' => $this->s__edicion[0]['hora_fin'],
                                                                                            'facultad' => $this->s__edicion[0]['facultad'],
                                                                                            'id_aula'   => $this->s__edicion[0]['id_aula'],
                                                                                            'cantidad_alumnos' => $this->s__edicion[0]['cantidad_alumnos'],
                                                                                            'tipo' => "Periodo"
                                                                                        );
                                                                                        $form->set_datos($datos);
                                                                                        //Otra forma de definir campos en solo lectura.
                                                                                        $form->set_solo_lectura(array('tipo_asignacion'));
                                                                                        break;
                                                            }
                                                            
                                                            break;
                                   }
                                                                     
                                   break;
                default :          toba::notificacion()->agregar("La variable accion esta vacia!", 'error');break;
            }
            
            
        }
                
        /*
         * 
         */
        function obtener_dias_seleccionados ($lista_dias){
            $dias=array();
            foreach ($lista_dias as $key=>$dia){
                $dias[]=$dia['nombre'];
            }
            
            return $dias;
        }
        
        
        /*
         * Boton aceptar del formulario form_asignacion.
         */
        function evt__form_asignacion__aceptar ($datos){
            
            switch($this->s__accion){
                case "Vinculo"      :      $this->procesar_vinculo($datos);break;  //Ok
                case "Registrar"    :      $this->procesar_carga($datos); break;   //Ok
                case "Borrar"       :      $this->procesar_delete($datos); break;  //Ok
                case "Editar"       :      $this->procesar_edicion($datos); break; //Ok
                case "Cambiar"      :      $this->procesar_cambio($datos); break;  //Por ahora no se implementa
                case "Confirmar"    :      $this->procesar_confirmacion($datos); break;
                default             :      toba::notificacion()->agregar("La variable accion esta vacia!", 'error'); break;
            }
            
        }
        
        /*
         * Esta funcion nos lleva a la pantalla extras, donde veremos un filtro y dos cuadros. 
         * Esta opcion se utiliza para eliminar las fechas o desplazar el horario de a una asignacion 
         * por periodo.
         */
        function evt__form_asignacion__agregar_dias (){
            $datos_form_asignacion=$this->dep('form_asignacion')->get_datos();
            
            $tipo=$datos_form_asignacion['tipo'];
            //debemos quitar este chequeo, debe hacerse en el cliente. Podriamos intentar anular el evento 
            //agregar_dias a traves de JS.
            if(strcmp($tipo, 'Definitiva')==0){
                $mensaje=" No se puede asociar fechas a asignaciones definitivas. ";
                toba::notificacion()->agregar($mensaje);              
            }
            else{
                $fecha_inicio=$datos_form_asignacion['fecha_inicio'];
                $fecha_fin=$datos_form_asignacion['fecha_fin'];
                if(isset($fecha_inicio) && isset($fecha_fin)){
                    $this->s__cargar_fechas=TRUE; //se debe poner en false cuando termina la operacion de carga
                    if(!isset($tipo)){
                        $datos_form_asignacion['tipo']='Periodo';
                    }
                    $this->s__datos_form_asignacion=$datos_form_asignacion;
                    $this->set_pantalla('pant_extra');
                }
                else{
                    toba::notificacion()->agregar(" Debe especificar fecha de inicio y fin. ");
                    
                }
                
            }
            
            //para restaurar el estado actual del formulario form_asignacion
            $this->s__datos_form_asignacion=$datos_form_asignacion;
            
        }
        
        /*
         * Nos lleva a la pantalla catedra, donde veremos un filtro y dos cuadros.
         * Esta opcion se utiliza para asociar un equipo de catedra con una asignacion definitva o por 
         * periodo.
         */
        function evt__form_asignacion__agregar_catedra (){
            $this->s__datos_form_asignacion=$this->dep('form_asignacion')->get_datos();
            //--Para no acumular docentes asociados a distintas asignaciones.
            if(strcmp($this->s__accion, "Editar") != 0)
                $this->s__docentes_seleccionados=array();
            
            $this->set_pantalla('pant_catedra');
        }
        
        //---- Form Vinculo ----------------------------------------------------------------------------
        
        function conf__form_vinculo (toba_ei_formulario $form){
            if(strcmp($this->s__accion, "Vinculo")==0){
                $form->set_titulo(utf8_decode("Formulario para Registrar Vínculos"));
                $form->descolapsar();
                //Cargamos informacion del vinculo.
                $form->set_datos($this->s__aula_disponible);
                
                $form->ef('fecha_inicio')->set_solo_lectura();              
                $form->ef('id_aula')->set_solo_lectura();               
                
                $form->ef('hora_inicio')->set_estado($this->s__aula_disponible['hora_inicio']);
                $form->ef('hora_fin')->set_estado($this->s__aula_disponible['hora_fin']);
            }else{
                $form->colapsar();
            }
        }
        
        function evt__form_vinculo__aceptar ($datos){
            $this->s__aula_disponible=$datos;
            $this->procesar_vinculo($datos);
        }
        
        function evt__form_vinculo__agregar_catedra ($datos){
            $this->s__aula_disponible=$datos;
            $this->set_pantalla('pant_catedra');
        }
        
        function registrar_asignacion ($datos){
                       
            //Evitamos guardar valores nulos en la base de datos, veremos si postgres admite conversion implicita
            //entre tipos de datos character varying e integer.
            $datos['nro_doc']=$this->s__datos_responsable['nro_doc'];
            $datos['tipo_doc']=$this->s__datos_responsable['tipo_doc'];        
            $datos['id_responsable_aula']=$this->s__id_docente;
            $datos['nombre']=$this->s__datos_responsable['nombre'];
            $datos['apellido']=$this->s__datos_responsable['apellido'];
            $datos['legajo']=$this->s__datos_responsable['legajo'];
            
            $this->dep('datos')->tabla('asignacion')->nueva_fila($datos);
            $this->dep('datos')->tabla('asignacion')->sincronizar();
            $this->dep('datos')->tabla('asignacion')->resetear();
            
        }
        
        function registrar_asignacion_definitiva ($datos){
            
            $secuencia=recuperar_secuencia('asignacion_id_asignacion_seq');
            $dato=array(
              'nombre' => $datos['dia_semana'], 
               
              'id_asignacion' => $secuencia,
            );
                                  
            $this->dep('datos')->tabla('asignacion_definitiva')->nueva_fila($dato);
            $this->dep('datos')->tabla('asignacion_definitiva')->sincronizar();
            $this->dep('datos')->tabla('asignacion_definitiva')->resetear();
        }
        
        function registrar_asignacion_periodo ($datos){
            
            $secuencia=recuperar_secuencia('asignacion_id_asignacion_seq');            
            
            //Registramos una tupla en la tabla asignacion_periodo
            $periodo=array(
                    'id_asignacion' => $secuencia,
                    'fecha_inicio' => $datos['fecha_inicio'],
                    'fecha_fin' => $datos['fecha_fin']
            );
            $this->dep('datos')->tabla('asignacion_periodo')->nueva_fila($periodo);
            $this->dep('datos')->tabla('asignacion_periodo')->sincronizar();
            $this->dep('datos')->tabla('asignacion_periodo')->resetear();
            
            //En esta seccion se guarda informacion en la tabla esta_formada (nombre, id_asignacion, fecha).
            //Esta funcion deberia funcionar para una fecha o una lista de fechas, y vamos a usar fechas con este
            //formato d-m-Y. Con la fecha podemos obtener el dia, gracias a la flexibilidad de la funcion date.
            //La estructura que enviamos es: array(0 => f1, ......, n => fn).
            $dias=$datos['dias'];
            foreach ($dias as $dia){
                $dato['nombre']=  utf8_decode($this->obtener_dia(date('N', strtotime($dia))));
                $dato['id_asignacion']=$secuencia;
                $dato['fecha']=$dia;
                $this->dep('datos')->tabla('esta_formada')->nueva_fila($dato);
                $this->dep('datos')->tabla('esta_formada')->sincronizar();
                $this->dep('datos')->tabla('esta_formada')->resetear();
            }
                        
        }
               
        function evt__form_asignacion__cancelar (){
            $this->dep('formulario')->colapsar();
            //$this->set_pantalla('pant_persona');
        }
        
        
        /*
         * Permite obtener todas las asignaciones de la persona durante un periodo academico, puede ser
         * cuatrimestre, examen final etc.
         * Tiene sentido tener esta funcion porque podemos llegar al cuadro asignaciones desde dos lugares 
         * diferentes : evt__cuadro__editar y evt__cuadro_personas__seleccionar. Ademas el cuadro asignaciones
         * se debe actualizar por cada insercion.
         */
        function obtener_asignaciones (){ 
            //Tiene cierto sentido usar la fecha actual, dado que el uso de las aulas es dinamico. 
            //Necesitamos saber como es la asignacion de horarios en un momento determinado.
            $fecha=  date('Y-m-d');
            
            $anio_lectivo=date('Y');
            
            $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($fecha, $anio_lectivo, $this->s__id_sede);
            
            //Analizamos el caso de dos periodos, cuando tenemos un turno de examen extraordinario incluido
            //en un cuatrimestre.
            $this->s__datos_cuadro_asig=array();
            foreach ($periodo as $clave=>$valor){
                //El nombre id_docente no es tan significativo, en el vamos a guardar el id_ de un responsable de aulas,
                //que puede ser un docente o una organizacion.
                $asignaciones=$this->dep('datos')->tabla('asignacion')->get_asignaciones_por_persona($this->s__id_docente, $valor['id_periodo'], $fecha);
                $this->unificar_conjuntos(&$this->s__datos_cuadro_asig, $asignaciones);
            }
            
        }
        
        /*
         * Esta funcion realiza una union de conjuntos.
         * @$periodo : se realiza pasaje de parametros por referencia.
         */
        function unificar_conjuntos ($periodo, $conjunto){
            foreach ($conjunto as $clave=>$valor){
                if(isset($valor)){
                   $periodo[]=$valor; //agrega al final
                }
            }            
        }
        
        //---- Metodos para cargar los popus ---------------------------------------------------
        
        function get_aula ($id_aula){
            $sql="SELECT nombre 
                  FROM aula 
                  WHERE id_aula=$id_aula";
            $aula=toba::db('rukaja')->consultar($sql);
            
            return ($aula[0]['nombre']);
        }
        
        function get_establecimiento ($sigla){
            $sql="SELECT descripcion
                  FROM unidad_academica 
                  WHERE sigla=".  quote($sigla);
            $establecimiento=toba::db('rukaja')->consultar($sql);
            
            return ($establecimiento[0]['descripcion']);
        }
        
        //--------------------------------------------------------------------------------------------
        //---- Metodos para procesar la accion elegida en los cuadros de la pantalla pant_edicion ----
        //--------------------------------------------------------------------------------------------
        
        /*
         * Permite cargar una asignacion definitiva o por periodo. 
         */
        function procesar_carga ($datos){
            //-- Check en sesion.
            $hora_inicio=toba::memoria()->get_dato_instancia(100);
            $hora_fin=toba::memoria()->get_dato_instancia(101);
            $tipo=toba::memoria()->get_dato_instancia(102);         
            
            //Hacemos un ultimo check en el server, verificamos si el usuario hizo un movimiento de horarios y no 
            //selecciono nuevamente un aula disponible. Si hacemos un movimiento de horarios no sabemos si ese 
            //mismo aula estara disponible. Y esto ocurre cuando el usuario modifica el horario y no abre el 
            //pop up aula.
            //En $datos traemos el ultimo movimiento de horarios realizado.
            if(!(($datos['hora_inicio']>=$hora_inicio && $datos['hora_inicio']<=$hora_fin) && $datos['hora_fin']<=$hora_fin)){
                $mensaje=" Acaba de realizar un movimiento de horarios y no seleccionó nuevamente un aula ";
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                toba::memoria()->limpiar_datos_instancia();
                return ;
            }
            
            switch($tipo){
                case 'Definitiva' : $dia=toba::memoria()->get_dato_instancia(103);
                                    
                                    if(!(strcmp($dia, $datos['dia_semana'])==0)){
                                        $mensaje="Acaba de cambiar el dia de la asignación y no seleccionó nuevamente un aula";
                                        toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                                        toba::memoria()->limpiar_datos_instancia();
                                        return ;
                                    }
                                    
                                    if(strcmp($this->s__accion, "Registrar")==0){
                                        $this->s__dia=$datos['dia_semana'];               
                                        //Si ejecutamos un script sql, con insert into, la secuencia no se actualiza, por lo tanto debemos
                                        //resetearla manualmente mediante select setval('secuencia', numero, 't').
                                        //recuperar_secuencia utiliza la funcion currval('secuencia') que devuleve el valor actual de la
                                        //misma. Cuando insertamos una tupla en una tabla, toba de alguna manera, usa la funcion 
                                        //nextval('secuencia') para obtener el proximo id, y asi evitar problemas con claves repetidas.
                                        $this->registrar_asignacion($datos);
                                        //Obtenemos el numero que utiliza postgres para garantizar unicidad en claves serials
                                        $secuencia= recuperar_secuencia('asignacion_id_asignacion_seq');
                                        
                                        $this->registrar_asignacion_definitiva($datos);

                                        $this->registrar_equipo_de_catedra($secuencia);
                                        
                                        toba::memoria()->limpiar_datos_instancia();
                                    }else{//En accion tenemos 'Editar'
                                        $this->procesar_edicion($datos);
                                    }
                                    
                                    break;
                                    
                                    
                                    
                case 'Periodo'    : 
                                    
                                    switch($datos['tipo_asignacion']){
                        
                                       case 'EXAMEN PARCIAL' : 
                                       case 'EXAMEN FINAL'   : $fecha_inicio=date('Y-m-d', strtotime(toba::memoria()->get_dato_instancia(103)));
                                                               
                                                               if(!strcmp($fecha_inicio, $datos['fecha_inicio'])==0){
                                                                   $mensaje="Acaba de cambiar la fecha de inicio y no seleccionó nuevamente un aula";
                                                                   toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                                                                   toba::memoria()->limpiar_datos_instancia();
                                                                   return ;
                                                               }
                                                               
                                                               if(strcmp($this->s__accion, "Registrar")==0){
                                                                   //Para evitar conflictos. La fecha es la misma.
                                                                   $fecha_fin=$datos['fecha_inicio'];
                                                                   //La lista de fechas se guarda en un arreglo asociativo.
                                                                   $dias=array(0 => $fecha_fin);
                                                                   $datos['fecha_fin']=$fecha_fin;
                                                                   $datos['dias']=$dias;
                                                                   
                                                                   $this->registrar_asignacion($datos);
                                                                   $secuencia= recuperar_secuencia('asignacion_id_asignacion_seq');
                                                                   
                                                                   $this->registrar_asignacion_periodo($datos);
                                                                   $this->registrar_equipo_de_catedra($secuencia);
                                                                   
                                                                   toba::memoria()->limpiar_datos_instancia();
                                                               }else{
                                                                   
                                                               }

                                                               break;
                                       case 'CONSULTA'       :
                                       case 'EVENTO'         : $fecha_inicio=date('Y-m-d', strtotime(toba::memoria()->get_dato_instancia(103)));
                                                               $fecha_fin=date('Y-m-d', strtotime(toba::memoria()->get_dato_instancia(104)));
                                                               $lista_dias=toba::memoria()->get_dato_instancia(105);
                                                                                                                              
                                                               if(!(strcmp($fecha_inicio, $datos['fecha_inicio'])==0 && strcmp($fecha_fin, $datos['fecha_fin'])==0)){
                                                                    $mensaje="Acaba de cambiar las fechas de inicio y fin y no seleccionó nuevamente un aula";
                                                                    toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                                                                    toba::memoria()->limpiar_datos_instancia();
                                                                    return ;
                                                               }
                                    
                                                                if(!($this->mismos_dias($datos['dias'], $lista_dias))){
                                                                    $mensaje="Acaba de modificar la lista de días elegidos y no seleccionó nuevamente un aula";
                                                                    toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                                                                    toba::memoria()->limpiar_datos_instancia();
                                                                    return ;
                                                                }
                                                               
                                                               if(strcmp($this->s__accion, "Registrar")==0){
                                                                                                              
                                                                   $hd=new HorariosDisponibles();
                                                                   $dias=$hd->get_dias($datos['fecha_inicio'], $datos['fecha_fin'], $datos['dias']);
                                                                                                                                      
                                                                   $datos['dias']=$dias;
                                                                                                                                      
                                                                   $this->registrar_asignacion($datos);
                                                                   $secuencia= recuperar_secuencia('asignacion_id_asignacion_seq');
                                                                   
                                                                   $this->registrar_asignacion_periodo($datos);
                                                                   $this->registrar_equipo_de_catedra($secuencia);
                                                                   toba::memoria()->limpiar_datos_instancia();
                                                               }else{
                                                                   
                                                               }

                                                               break;
                                    }
                                    
                                    break;
            }
            
        }
        
        /*
         * Esta funcion permite registrar el equipo de catedra asociado a una asignacion. Se persiste 
         * informacion en la tabla catedra.
         */
        function registrar_equipo_de_catedra ($secuencia){
            //Agregamos el equipo de catedra si existe.
            if(count($this->s__docentes_seleccionados)>0){
                foreach($this->s__docentes_seleccionados as $clave=>$docente){
                    
                    $docente['id_asignacion']=$secuencia;
                    
                    $this->dep('datos')->tabla('catedra')->nueva_fila($docente);
                    $this->dep('datos')->tabla('catedra')->sincronizar();
                    $this->dep('datos')->tabla('catedra')->resetear();
                }
                
                //Limpiamos el arreglo para no acumular resultados en busquedas sucesivas.
                $this->s__docentes_seleccionados=array();
                
                //Limpiamos el arreglo para no cargar datos por defecto en el formulario despues de seleccionar
                //un equipo de catedra y registrar la asignacion correspondiente en el sistema.
                $this->s__datos_form_asignacion=array();
            }
            
        }
        
        /*
         * Esta funcion psermite verificar si el usuario no modifio los dias de un periodo.
         * @$dias: contiene la ultima informacion cargada en un formulario.
         * @$lista_dias: contiene la ultima informacion guardada en sesion.
         * Si entre esta informacion existe incompatibilidad, estamos en problemas.
         * Devolvemos TRUE si ambas estructuras tienen los mismos dias.
         * 
         * No importa que existan dos bucles anidados porque el tamanio de la entrada es pequenio.
         */
        function mismos_dias ($dias, $lista_dias){
            $n=count($dias);
            $m=count($lista_dias);
            if($n != $m){
                return FALSE;
            }
            
            $i=0;
            $fin=TRUE;
            while($i<$n && $fin){
                $fin=$this->existe_coincidencia($dias[$i], $lista_dias);
                $i++;
            }
            
            return $fin;
        }
        
        function existe_coincidencia ($dia, $lista_dias){
            $i=0;
            $n=count($lista_dias);
            $fin=FALSE;
            while($i<$n && !$fin){
                if(strcmp($dia, $lista_dias[$i])==0){
                    $fin=TRUE;
                }
                $i++;
            }
            
            return $fin;
        }
        
        /*
         * Esta funcion devuelve false si alguna asignacion del periodo posee como horario la cadena 00:00:00, que 
         * denominamos nula. Esto quiere decir que no se realizo un analisis correcto del periodo
         */
        function existen_cadenas_nulas ($asignaciones){
            $i=0;
            $n=count($asignaciones);
            $fin=FALSE;
            while($i<$n && !$fin){
                $hora_inicio=$asignaciones[$i]['hora_inicio'];
                $hora_fin=$asignaciones[$i]['hora_fin'];
                
                $fin=(strcmp($hora_inicio, '00:00:00')==0 || strcmp($hora_fin, '00:00:00')==0) ? TRUE : FALSE;
                
                $i += 1;
            }
            
            return $fin;
        }
        
        /*
         * Esta funcion realiza verificaciones sobre hora y fecha
         */
        function validar_datos ($hora_inicio, $hora_fin, $fecha_inicio=null, $fecha_fin=null){
            
            if(!isset($fecha_inicio)){
                return ($hora_inicio < $hora_fin);
            }
            else{
                return (($hora_inicio < $hora_fin) && ($fecha_inicio < $fecha_fin));
            }
        }
        
        /*
         * Esta funcion gestiona un vinculo establecido entre las operaciones 'Calendario Comahue' y 
         * 'Cargar Asignaciones'. Realiza una validacion de  horas y produce la carga de datos en el sistema.
         * Si ocurre algun problema volvemos a autocompletar el formulario.
         */
        function procesar_vinculo ($datos){
            
            if(strcmp($datos['tipo_asignacion'], "CURSADA")==0){
                $mensaje="No se puede seleccionar el tipo de asignacion CURSADA porque es incompatible con "
                        . "una asignación por periodo. ";
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                return ;
            }
            
            $hora_inicio_datos="{$datos['hora_inicio']}:00";
            $hora_fin_datos="{$datos['hora_fin']}:00";
            
            if(($hora_inicio_datos < $hora_fin_datos) && (($hora_inicio_datos >= $this->s__hora_inicio) && ($hora_inicio_datos <= $this->s__hora_fin)) && ($hora_fin_datos <= $this->s__hora_fin)){
                            
               $this->registrar_asignacion($datos);
               
               $datos['fecha_fin']=$datos['fecha_inicio'];
               $datos['dias']=array($datos['fecha_inicio']);
               $this->registrar_asignacion_periodo($datos);
               
               $secuencia=  recuperar_secuencia('asignacion_id_asignacion_seq');
               $this->registrar_equipo_de_catedra($secuencia);
               $this->s__accion="Nop";
            }
            else{
                $this->s__aula_disponible['hora_inicio']=$this->s__hora_inicio;
                $this->s__aula_disponible['hora_fin']=$this->s__hora_fin;
                $mensaje=" El horario especificado no pertenece al rango disponible : {$this->s__hora_inicio} y {$this->s__hora_fin} hs ";
                toba::notificacion()->agregar($mensaje, 'info');
            }
        }
        
        /*
         * Eliminamos una asignacion definitiva o periodica.
         */
        function procesar_delete ($datos){
            try{
                $this->dep('datos')->tabla('asignacion')->cargar(array('id_asignacion'=>$this->s__id_asignacion));
                $asignacion=$this->dep('datos')->tabla('asignacion')->get();
                $this->dep('datos')->tabla('asignacion')->eliminar_fila($asignacion['x_dbr_clave']);
                $this->dep('datos')->tabla('asignacion')->sincronizar();
            }catch(toba_error $e){
                
            }
        }
        
        /*
         * Modificamos una asignacion existente, esto implica:
         * 1) Modificar las tablas asignacion y asignacion_definitiva.
         * 2) Modificar las tablas asignacion, asignacion_periodo y esta_formada. En este caso es mas conveniente 
         * borrar parte de la asignacion en la tabla esta_formada y volver a cargarla porque se pueden agregar o 
         * quitar dias del periodo.
         * 
         * @$datos: contiene los nuevos valores que queremos agregar en las tablas involucradas.
         * 
         */
        function procesar_edicion ($datos){
            
            //--Esta informacion se guarda en sesion si el usuario abre el pop-up aula.
            $hora_inicio=toba::memoria()->get_dato_instancia(100);
                        
            //--Si hay datos en sesion significa que el user intento modificar el horario, dia o aula. Es posible
            //reutilizar parte de los chequeos realizados en procesar_carga.
            if(isset($hora_inicio)){
                $hora_fin=toba::memoria()->get_dato_instancia(101);
                $tipo=toba::memoria()->get_dato_instancia(102);
                $dia=toba::memoria()->get_dato_instancia(103);
                
                //--Debemos verificar posibles movimientos de horario.
                if(!(($datos['hora_inicio']>=$hora_inicio && $datos['hora_inicio']<=$hora_fin) && $datos['hora_fin']<=$hora_fin)){
                    $mensaje=" Acaba de realizar un movimiento de horarios y no seleccionó nuevamente un aula ";
                    toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                    toba::memoria()->limpiar_datos_instancia();
                    return ;
                }
                
                if(!(strcmp($dia, $datos['dia_semana'])==0)){
                    $mensaje="Acaba de cambiar el dia de la asignación y no seleccionó nuevamente un aula";
                    toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                    toba::memoria()->limpiar_datos_instancia();
                    return ;
                }
                
                //--Guardamos en la bd la edicion realizada.
                $this->editar_asignacion($datos);
            }else{ //--No hay datos en sesion. Entonces debemos modificar la tupla existente y modificar el
                   //el equipo de catedra en el peor caso. Se llega a esta rama si el usuario en ppio modifica
                   //datos que no generan conflictos como finalidad, modulo, cantidad de alumnos etc.
                if(strcmp($this->s__tipo, "Definitiva")==0){ //--Tratamos una asignacion_definitiva.
                    
                    //--Si no hay datos en sesion debemos verificar que el contenido de s__edicion sea 
                    //equivalente a $datos. El problema es cuando el usuario cambia el periodo de la asignacion
                    //porque este combo no guarda datos en sesion.
                    if($datos['id_periodo'] != $this->s__edicion[0]['id_periodo']){
                        $mensaje=" No se puede cambiar el periodo académico sin especificar un nuevo espacio disponible "
                                ;
                        toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                        return ;
                    }
                    //--Si no hay datos en sesion, es decir el user no abrio el pop-up aula, debemos verificar 
                    //que el horario especificado en el formulario este contenido en el horario de s__edicion.
                    $hora_inicio="{$datos['hora_inicio']}:00";
                    $hora_fin="{$datos['hora_fin']}:00";
                    $id_aula=$datos['id_aula'];
                    if(!(($hora_inicio >= $this->s__edicion[0]['hora_inicio']) && 
                        ($hora_inicio <= $this->s__edicion[0]['hora_fin'])     &&
                        ($hora_fin <= $this->s__edicion[0]['hora_fin']))    ){
                        $mensaje="El horario $hora_inicio - $hora_fin hs debe estar incluido en {$this->s__edicion[0]['hora_inicio']} - {$this->s__edicion[0]['hora_fin']}";
                        toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                        return ;
                    }
                    
                    //--Guardamos en la bd la edicion realizada.
                    $this->editar_asignacion($datos);
                }
                
            }


//            }else{//Editamos una asignacion periodica.
//                //En asignacion y asignacion_periodo hacemos un set.
//                $this->dep('datos')->tabla('asignacion')->cargar(array('id_asignacion'=>$this->s__id_asignacion));
//                $asignacion=$this->dep('datos')->tabla('asignacion')->get();
//                $datos['nombre']=$asignacion['nombre'];
//                $datos['nombre']=$asignacion['apellido'];
//                $datos['legajo']=$asignacion['legajo'];
//                $datos['nro_doc']=$asignacion['nro_doc'];
//                $datos['tipo_doc']=$asignacion['tipo_doc'];
//                $datos['id_asignacion']=$this->s__id_asignacion;
//                $this->dep('datos')->tabla('asignacion')->set($datos);
//                $this->dep('datos')->tabla('asignacion')->sincronizar();
//                
//                $asignacion_periodo=array(
//                    'id_asignacion' => $this->s__id_asignacion,
//                    'fecha_inicio' => $datos['fecha_inicio'],
//                    'fecha_fin' =>  $datos['fecha_fin']
//                );
//                
//                $this->dep('datos')->tabla('asignacion_periodo')->cargar(array('id_asignacion'=>$this->s__id_asignacion));
//                $this->dep('datos')->tabla('asignacion_periodo')->set($asignacion_periodo);
//                $this->dep('datos')->tabla('asignacion_periodo')->sincronizar();                
//            }

            //--Eliminamos datos en sesion que pueden interferir en el proceso de edicion.
            toba::memoria()->limpiar_datos_instancia();
            $this->s__datos_form_asignacion=array();
            //$this->s__accion="Registrar";
            $this->set_pantalla('pant_edicion');
        }
        
        /*
         * Esta funcion guarda en la bd todos los cambios realizados en una asignacion. Incluye modificaciones 
         * en el equipo de catedra si es necesario.
         *
         */
        function editar_asignacion ($datos){
            //--Obtenemos la asignacion para conservar los datos freezados del responsable de aula.
            $this->dep('datos')->tabla('asignacion')->cargar(array('id_asignacion'=>$this->s__id_asignacion));
            $asignacion=$this->dep('datos')->tabla('asignacion')->get();
            $datos['nombre']=$asignacion['nombre'];
            $datos['apellido']=$asignacion['apellido'];
            $datos['legajo']=$asignacion['legajo'];
            $datos['nro_doc']=$asignacion['nro_doc'];
            $datos['tipo_doc']=$asignacion['tipo_doc'];
            $datos['id_asignacion']=$this->s__id_asignacion;
            $this->dep('datos')->tabla('asignacion')->set($datos);
            $this->dep('datos')->tabla('asignacion')->sincronizar(); 

            $asignacion_definitiva=array(
                'nombre' => $datos['dia_semana'],
                'id_asignacion' => $this->s__id_asignacion
            );

            //--Esta vez cargamos el datos_tabla con la asignacion_definitiva correspondiente.
            $this->dep('datos')->tabla('asignacion_definitiva')->cargar(array('id_asignacion'=>$this->s__id_asignacion));
            $this->dep('datos')->tabla('asignacion_definitiva')->set($asignacion_definitiva);
            $this->dep('datos')->tabla('asignacion_definitiva')->sincronizar();

            //--Guardamos el nuevo equipo de catedra, si no se realizo una edicion del mismo.
            $n=count($this->s__docentes_seleccionados);
            if($n>0){
                //--Contiene el equipo de catedra, si existe.
                if(count($this->s__equipo_catedra)>0){
                    //--Internamente esta funcion modifica el contenido del atributo unico_registro.
                    $this->dep('datos')->tabla('catedra')->set_tope_max_filas(count($this->s__equipo_catedra));
                    $this->dep('datos')->tabla('catedra')->cargar(array('id_asignacion'=>$this->s__id_asignacion));

                    $this->dep('datos')->tabla('catedra')->eliminar_todo();
                }
                //--Del cuadro docentes seleccionados nos falta el atributo id_asignacion necesario para
                //registrar el equipo de catedra.
                //print_r($this->s__id_asignacion);print_r("<br><br>");

                foreach ($this->s__docentes_seleccionados as $key=>$docente){
                    if(isset($docente)){
                        $docente['id_asignacion']=$this->s__id_asignacion;
                        $this->dep('datos')->tabla('catedra')->nueva_fila($docente);
                        $this->dep('datos')->tabla('catedra')->sincronizar();
                        $this->dep('datos')->tabla('catedra')->resetear();
                    }
                }
            }
        }
                        
        //---- Metodos para validar informacion ------------------------------------------------------
        
        /*
         * Esta funcion verifica si una hora de inicio y fin esta incluida en un horario disponible
         * calculado previamente.
         * Devuelve true si existe inclusion.
         */
        function verificar_inclusion_de_horario ($hora_inicio, $hora_fin){
            
            $longitud=count($this->s__horarios_disponibles);
            $i=0;
            $fin=FALSE;
            while (($i < $longitud) && !$fin){
                $asignacion=$this->s__horarios_disponibles[$i];
                
                $hora_inicio="$hora_inicio:00";
                $hora_fin="$hora_fin:00";
                $hora_inicio_asig=$asignacion['hora_inicio'];
                $hora_fin_asig=$asignacion['hora_fin'];
                if((($hora_inicio >= $hora_inicio_asig)) && (($hora_fin <= $hora_fin_asig))){
                    $fin=TRUE;
                }
                
                $i += 1;
            }
            
            return $fin;
        }
        
        //---------------------------------------------------------------------------------------------
        //---- Pant Extra -----------------------------------------------------------------------------
        //---------------------------------------------------------------------------------------------
        
        function conf__pant_extra (toba_ei_pantalla $pantalla){ 
            $this->pantalla()->tab('pant_edicion')->desactivar();
            //$this->pantalla()->tab('pant_persona')->desactivar();
            $this->pantalla()->tab('pant_asignacion')->desactivar();
            $this->pantalla()->tab('pant_catedra')->desactivar();
            $this->s__pantalla_actual="pant_extra";
        }
        
        //---- Form Fechas -----------------------------------------------------------------------------
        
        function conf__form_fechas (toba_ei_formulario $form){
            $fecha_inicio=$this->s__datos_form_asignacion['fecha_inicio'];
            $fecha_fin=$this->s__datos_form_asignacion['fecha_fin'];
            $fecha_inicio=date('d-m-Y', strtotime($fecha_inicio));
            $fecha_fin=date('d-m-Y', strtotime($fecha_fin));
            $form->ef('fecha_inicio')->set_estado($fecha_inicio);
            $form->ef('fecha_fin')->set_estado($fecha_fin);
        }
        
        /*
         * operar_sobre_fecha cumple con dos funciones :
         * a) verifica si una fecha existe en la estructura s__fechas, para ello debemos pasar como segundo 
         * parametro la letra f.
         * b) elimina una fecha existente en la estructura s__fechas, para ello debemos pasar como segundo 
         * parametro la letra e.
         */
        function operar_sobre_fecha ($fecha_calendario, $operacion){          
            
            $existe=FALSE;
            foreach ($this->s__fechas as $clave=>$fecha){
                // unset nos permite destruir una variable, eso quiere decir que borra su contenido y libera
                // el espacio que ocupa en memoria. El problema que tenemos es que al aplicar unset sobre 
                // eltos de un arreglo nos genera huecos en el mismo, lo que hace que una busqueda por indice
                // sea inapropiada.
                                
                if(strcmp($fecha['fecha'], $fecha_calendario)==0){
                    $existe=TRUE;
                    
                    if(strcmp($operacion, 'e')==0){
                        //$this->s__fechas[$longitud]=NULL;
                        unset($this->s__fechas[$clave]);
                    }
                }
                
            }
            
            return $existe;
        }
        
        //---- Cuadro Fechas ---------------------------------------------------------------------------
        
        function conf__cuadro_fechas (toba_ei_cuadro $cuadro){         
            $fecha_inicio=$this->s__datos_form_asignacion['fecha_inicio'];
            $fecha_fin=$this->s__datos_form_asignacion['fecha_fin'];
            
            $dias_seleccionados=$this->s__datos_form_asignacion['dias'];
            //obtenemos los dias que pertenecen al periodo
            $fechas=$this->get_dias($fecha_inicio, $fecha_fin, $dias_seleccionados);
            
            
            if(count($fechas)>0){
                $cuadro->set_datos($this->crear_estructura_cuadro($fechas));
            }
        }
        
        function evt__cuadro_fechas__seleccionar ($fecha_cuadro){
            //disparamos el calculo de horarios para la fecha seleccionada, teniendo en cuenta el aula elegida en
            //form_asignacion
        }
        
        function evt__cuadro_fechas__eliminar ($fecha_cuadro){
            $r=$this->operar_sobre_fecha($fecha_cuadro['fecha'], 'e');
        }
        
        function conf__cuadro_horarios_disponibles (toba_ei_cuadro $cuadro){
            
        }
        
        //----------------------------------------------------------------------------------------------
        //---- Pant Catedra ----------------------------------------------------------------------------
        //----------------------------------------------------------------------------------------------
        
        function conf__pant_catedra (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab('pant_extra')->desactivar();
            $this->pantalla()->tab('pant_asignacion')->desactivar();
            //$this->pantalla()->tab('pant_persona')->ocultar();
            $this->pantalla()->tab('pant_edicion')->desactivar();
            $this->s__pantalla_actual="pant_catedra";
        }
        
        //---- Filtro Docentes -------------------------------------------------------------------------
        
        
        function evt__filtro_docentes__filtrar ($datos){
            $where=$this->dep('filtro_docentes')->get_sql_where('OR');
            $this->s__docentes=$this->dep('datos')->tabla('persona')->get_docentes(strtoupper($where));
        }
                
        //---- Cuadro Docentes -------------------------------------------------------------------------
        
        /*
         * Este cuadro guarda a un cjto. de docentes, que son el resultado de una consulta en la db mocovi.
         */
        function conf__cuadro_docentes (toba_ei_cuadro $cuadro){
            if(count($this->s__docentes)>0){
                $cuadro->set_datos($this->s__docentes);
            }
        }
        
        function evt__cuadro_docentes__seleccionar ($docente_seleccionado){
            if(!($this->operar_sobre_docentes($docente_seleccionado, 'd'))){
                $this->s__docentes_seleccionados[]=$docente_seleccionado;
            }
        }
        
        /*
         * operar_sobre_docentes cumple con dos funciones :
         * a) verifica si un docente existe en la estructura s__docentes_seleccionados, para ello debemos pasar 
         * como segundo parametro la letra d.
         * b) elimina un docente existente en la estructura s__docentes_seleccionados, para ello debemos pasar
         * como segundo parametro la letra e.
         */
        function operar_sobre_docentes ($docente_seleccionado, $operacion){
            
            $existe=FALSE;
            foreach ($this->s__docentes_seleccionados as $clave=>$valor){
                $nro_doc=$valor['nro_doc'];
                $tipo_doc=$valor['tipo_doc'];
                
                if((strcmp($nro_doc, $docente_seleccionado['nro_doc'])==0) && (strcmp($tipo_doc, $docente_seleccionado['tipo_doc'])==0)){
                    $existe=TRUE;
                    
                    if(strcmp($operacion, 'e')==0){
                        //$this->s__docentes_seleccionados[$longitud]=NULL;
                        unset($this->s__docentes_seleccionados[$clave]);
                    }
                }
                
            }
            
            return $existe;
        }
        
        //---- Docentes Seleccionados ------------------------------------------------------------------
        
        function conf__docentes_seleccionados (toba_ei_cuadro $cuadro){
            if(count($this->s__docentes_seleccionados)>0){
                $cuadro->set_datos($this->s__docentes_seleccionados);
            }
        }
        
        function evt__docentes_seleccionados__eliminar ($docente_seleccionado){
            $this->operar_sobre_docentes($docente_seleccionado, 'e');
        }
        
        //----------------------------------------------------------------------------------------------
        //---- METODO AJAX -----------------------------------------------------------------------------
        //----------------------------------------------------------------------------------------------
        
        function ajax__guardar_horario_en_sesion ($parametros, toba_ajax_respuesta $respuesta){
            //genera un arror javascript, pero es util para ver el contenido de parametros
            //print_r($parametros); 
            //Este es el resultado del print_r :
            //Array
            //(
            //    [0] => 09:00
            //    [1] => 12:00
            //    [2] => nopar
            //    [3] => Definitiva
            //    [4] => Lunes
            //)
            //{"clave":"no alterar ajax"}
            //toba::vinculador()->get_url("gestion_aulas", 3533, array(0 => $parametros));
            
            //Primero guardamos los datos comunes. El indice 0 se reserva para el id_sede.
            toba::memoria()->set_dato_instancia(1, $parametros[0]); //hora_inicio
            toba::memoria()->set_dato_instancia(2, $parametros[1]); //hora_fin
            toba::memoria()->set_dato_instancia(3, $parametros[2]); //id_periodo
            toba::memoria()->set_dato_instancia(4, $parametros[3]); //tipo (Definitiva, Periodo)
            
            switch($parametros[3]){
                case 'Definitiva' : toba::memoria()->set_dato_instancia(5, $parametros[4]); //dia_semana
                                    break;
                case 'Periodo'    : 
                                    toba::memoria()->set_dato_instancia(5, $parametros[4]); //dias
                                    toba::memoria()->set_dato_instancia(6, $parametros[5]); //fecha_inicio
                                    
                                    toba::memoria()->set_dato_instancia(7, $parametros[6]); //fecha_fin
                                    
                                    toba::memoria()->set_dato_instancia(8,  $parametros[7]); //tipo_asignacion
                                    toba::memoria()->set_dato_instancia(9,  $parametros[8]); //Lunes
                                    toba::memoria()->set_dato_instancia(10, $parametros[9]); //Martes
                                    toba::memoria()->set_dato_instancia(11, $parametros[10]); //Miercoles
                                    toba::memoria()->set_dato_instancia(12, $parametros[11]); //Jueves
                                    toba::memoria()->set_dato_instancia(13, $parametros[12]); //Viernes
                                    toba::memoria()->set_dato_instancia(14, $parametros[13]); //Sabado.
                                    toba::memoria()->set_dato_instancia(15, $parametros[14]); //Domingo
                                    break;
            }
            
            
            $respuesta->set(array('clave' => 'no alterar ajax'));
                        
        }
        
        //----------------------------------------------------------------------------------------------
        //---- Funcion para procesar periodos ----------------------------------------------------------
        //----------------------------------------------------------------------------------------------
        
        function procesar_periodo ($periodos, $i){
            //Falta considerar curso_de_ingreso, pero es menos importante.
            $cuatrimestre=array();
            $ccu=array();
            $examen_final=array();
            $ex=array();
            foreach($periodos as $clave=>$valor){
                switch ($valor['tipo_periodo']){
                    case 'Cuatrimestre' : if(strcmp($i, "hd")==0){
                                            $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_cuatrimestre($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
                                          }else{//--Consideramos la rama de horarios registrados, "hr".
                                               $ccu=$this->dep('datos')->tabla('asignacion')->get_asignaciones($this->s__where, $valor['id_periodo']);
                                               //--Si no unimos cjtos. de asignaciones se pisan entre si.
                                               $this->unificar_conjuntos(&$cuatrimestre, $ccu);
                                          }
                                          
                                          break;
                                          
                    case 'Examen Final' : if(strcmp($i, "hd")==0){
                                            $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_examen_final($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
                                          }else{//Consideramos la rama de horarios registrados, "hr".
                                                $ex=$this->dep('datos')->tabla('asignacion')->get_asignaciones($this->s__where, $valor['id_periodo']);
                                                $this->unificar_conjuntos(&$examen_final, $ex);
                                          }
                                          break;
                }
            }
            
            if((count($cuatrimestre)>0) && (count($examen_final)>0)){
                $this->unificar_conjuntos(&$cuatrimestre, $examen_final);
                return $cuatrimestre;
            }
            
            if((count($cuatrimestre)>0) && (count($examen_final)==0)){
                return $cuatrimestre;
            }
            
            if((count($cuatrimestre)==0) && (count($examen_final)>0)){
                return $examen_final;
            }
            
            if((count($cuatrimestre)==0) && (count($examen_final)==0)){
                return array();
            }
        }
        
        //-------------------------------------------------------------------------------------------------
        //---- Funciones para obtener fechas de un periodo ------------------------------------------------
        //-------------------------------------------------------------------------------------------------
        
        /*
         * Esta funcion devuelve los dias que pertenecen a un periodo, formado por fecha_inicio y fecha_fin.
         */
        public function get_dias ($fecha_inicio, $fecha_fin, $dias_seleccionados){
            //aca debemos tratar el caso del mes de febrero, que puede tener 29 dias si el anio es bisiesto
            $anio=date('Y');
            $febrero=(($anio%400==0) || (($anio%4==0)&&($anio%100 != 0))) ? 29 : 28;
            $this->_meses[2]=$febrero;

            //obtenemos dia (01 a 31) y mes (01 a 12) con representacion numerica
            $dia_inicio=date('d', strtotime($fecha_inicio));
            $mes_inicio=date('m', strtotime($fecha_inicio));

            $dia_fin=date('d', strtotime($fecha_fin));
            $mes_fin=date('m', strtotime($fecha_fin));

            if($mes_inicio == $mes_fin){
                //con mes_inicio y mes_fin obtenemos la cantidad de dias que forman a dichos meses
                return $this->generar_dias($dia_inicio, $dia_fin, $mes_inicio, $mes_fin, 'mm', $dias_seleccionados, NULL);
            }
            else{
                $diff=$mes_fin - $mes_inicio;
                if($diff >= 2){ //tenemos meses intermedios entre el periodo seleccionado
                    //debemos decrementar una unidad de diff, para no repetir meses
                    return $this->generar_dias($dia_inicio, $dia_fin, $mes_inicio, $mes_fin, 'mnc', $dias_seleccionados, $this->obtener_meses_intermedios($mes_inicio, ($diff - 1)));
                }
                else{ //en esta rama diff posee el valor 1, lo que implica que existen meses contiguos
                    return $this->generar_dias($dia_inicio, $dia_fin, $mes_inicio, $mes_fin, 'mc', $dias_seleccionados, NULL);
                }
            }

        }

        /*
         * Esta funcion determina los meses intermedios entre un periodo. Se utiliza para representar a los meses
         * valores numericos de 1 a 12.
         */
        function obtener_meses_intermedios ($mes_inicio, $diff){
            $meses_intermedios=array();

            for($i=1; $i<=$diff; $i++){
                $mes_inicio += 1;
                $meses_intermedios[]=$mes_inicio;
            }

            return $meses_intermedios;
        }

        /*
         * La variable i puede contener :
         * mm = mismo mes. @meses_intermedios es NULL.
         * mc = meses contiguos. @meses_intermedios es NULL.
         * mnc = meses no contiguos.
         */
        function generar_dias ($dia_inicio, $dia_fin, $mes_inicio, $mes_fin, $i, $dias_seleccionados, $meses_intermedios){
            //guardamos los dias del periodo
            $dias=array();
            $anio=date('Y');
            switch($i){
                case 'mm' : while($dia_inicio <= $dia_fin){
                                $fecha=  date('d-m-Y', strtotime("$dia_inicio-$mes_inicio-$anio"));
                                                               
                                
                                if($this->es_dia_valido(date('N', strtotime($fecha)), $dias_seleccionados)){
                                    $dias[]=$fecha;
                                }

                                $dia_inicio += 1;
                            }

                            break;

                case 'mc' : $this->obtener_dias($dia_inicio, $mes_inicio, $this->_meses[intval($mes_inicio)], $dias_seleccionados, &$dias);
                            $this->obtener_dias(1, $mes_fin, $dia_fin, $dias_seleccionados, &$dias);

                            break;

                case 'mnc': //obtenemos los dias para dia_inicio y mes_inicio
                            $this->obtener_dias($dia_inicio, $mes_inicio, $this->_meses[intval($mes_inicio)], $dias_seleccionados, &$dias);
                            
                            //para los meses intermedios podemos obtener los dias sin problemas, avanzamos desde 1 
                            //hasta el ultimo dia del mes y realizamos el descarte adecuado.
                            foreach ($meses_intermedios as $clave=>$mes_i){ //mes_i contiene un valor entero
                                $this->obtener_dias(1, $mes_i, $this->_meses[$mes_i], $dias_seleccionados, &$dias);
                            }

                            //obtenemos los dias para dia_fin y mes_fin
                            $this->obtener_dias(1, $mes_fin, $dia_fin, $dias_seleccionados, &$dias);

                            break;
            }

            return $dias;
        }

        /*
         * @mes_inicial : contiene el numero de mes.
         * @mes : contiene la cantidad de dias de un mes.
         * 
         */
        function obtener_dias ($dia_inicial, $mes_inicial, $mes, $dias_seleccionados, $dias){
            //$dias=array();
            $anio=date('Y');
                    
                       
            for($i=$dia_inicial; $i<=$mes; $i++){
                
                $fecha=  date('d-m-Y', strtotime("$i-$mes_inicial-$anio"));
                                
                if($this->es_dia_valido(date('N', strtotime($fecha)), $dias_seleccionados)){
                    $dias[]=$fecha;
                }
            }

            //return $dias;
        }

        /*
         * @dia_inicio : contiene una representacion numerica de un dia de la semana, puede ser 1,....,7. Se obtiene
         * con date('N', fecha).
         */
        function es_dia_valido ($dia_inicio, $dias_seleccionados){
            $i=0;
            $n=count($dias_seleccionados);
            $fin=FALSE;
            while($i<$n && !$fin){
                //podemos obtener Lunes, Martes, .....
                $dia=$dias_seleccionados[$i];
                                
                if(strcmp(utf8_decode($this->_dias[$dia_inicio]), $dia)==0){
                    $fin=TRUE;
                }
                $i++;
            }
            return $fin;
        }
        
        /*
         * Esta funcion verifica, por cada fecha del periodo, si existe lugar en el aula seleccionada. Se tiene
         * en cuenta el horario especificado.
         * Para ello es necesario realizar calculos de horarios disponibles.
         * Si no llega a existir lugar en el aula cargamos una cadena 00:00:00 en lugar de un horario 
         * especifico. Lo ideal seria seleccionar el registro...... podemos utilizar la funcion seleccionar
         * perteneciente a la clase toba_ei_cuadro.
         * @aula : contiene el aula seleccionada en form_asignacion.
         */
        function crear_estructura_cuadro ($fechas){
            $cuadro=array();
            
            $this->s__id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
            
            toba::memoria()->limpiar_datos_instancia();
            //creamos un objeto HorariosDisponibles para realizar calculo de horarios disponibles
            $hd=new HorariosDisponibles();
            //obtenemos las aulas de una ua segun su sede.
            $aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($this->s__id_sede);
            
            foreach($fechas as $clave=>$fecha){
                //guardamos el id_sede en sesion para agregar la capacidad del aula al resultado
                toba::memoria()->set_dato_instancia(0, $this->s__id_sede);
                //guardamos la fecha de consulta para extraer asig. en procesar_periodo
                $this->s__fecha_consulta=date('Y-m-d', strtotime($fecha));
                $this->s__dia_consulta=utf8_decode($this->obtener_dia(date('N', strtotime($this->s__fecha_consulta))));
                //con la fecha obtenemos los periodos academicos correspondientes
                $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($fecha, date('Y', strtotime($fecha)), $this->s__id_sede);
                //con el periodo obtenemos las asignaciones, hd significa 'horarios disponibles'.
                //Pero necesitamos las asignaciones para un aula, esta optimizacion queda para mas adelante
                $asignaciones=$this->procesar_periodo($periodo, "hd");
                //obtenemos las aulas que actualmente estan ocupadas
                $aulas=$this->obtener_aulas($asignaciones);
                
                $this->s__horarios_disponibles=$hd->calcular_horarios_disponibles($aulas, $aulas_ua, $asignaciones);
                
                $hora_inicio=$this->s__datos_form_asignacion['hora_inicio'];
                $hora_fin=$this->s__datos_form_asignacion['hora_fin'];
                $datos=array();
                
                if(!$this->existe_inclusion($hora_inicio, $hora_fin, &$datos)){
                    $hora_inicio="00:00:00";
                    $hora_fin="00:00:00";
                }
                
                //creamos la estructura del cuadro_fechas, si no existen horarios cargamos una cadena 00:00:00
                $datos['fecha']=$fecha;
                //necesitamos usar strtotime para que nos devuelva el dia adecuado
                $datos['dia']=utf8_decode($this->_dias[date('N', strtotime($fecha))]);
                $datos['hora_inicio']=$hora_inicio;
                $datos['hora_fin']=$hora_fin;
                
                $cuadro[]=$datos;
                
                //debemos resetear el arreglo para no acumular resultados
                $this->s__horarios_disponibles=array();
            }
            
            return $cuadro;
        }
        
        /*
         * Esta funcion devuelve true si un horario esta incluido en un horario disponible.
         */
        function existe_inclusion ($hora_inicio, $hora_fin, $cuadro){
            $i=0;
            $n=count($this->s__horarios_disponibles);
            $fin=FALSE;
            while($i<$n && !$fin){
                $horario=$this->s__horarios_disponibles[$i];
                if(($hora_inicio >= $horario['hora_inicio'] && $hora_inicio <= $horario['hora_fin']) && ($hora_fin <= $horario['hora_fin'])){
                    $fin=TRUE;
                    $cuadro['aula']=$horario['aula'];
                    $cuadro['id_aula']=$horario['id_aula'];
                }
                $i++;
            }
            
            return $horario;
        }
                       

}

?>