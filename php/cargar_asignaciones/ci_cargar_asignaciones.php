<?php

require_once(toba_dir().'/proyectos/rukaja/php/api/Calendario.php');
require_once(toba_dir().'/proyectos/rukaja/php/api/HorariosDisponibles.php');

class ci_cargar_asignaciones extends toba_ci
{
        protected $s__contador;
        protected $s__tipo;                             //guardamos el tipo de asignacion que elige el usuario
        protected $s__dia;                              //guardamos el dia elegido por el usuario en caso de seleccionar tipo Definitiva
        protected $s__nro_doc;                          //guardamos el nro_doc de la persona para cargar el formulario form_datos
        protected $s__tipo_doc;                         //guardamos el tipo_doc de la persona para cargar el formulario form_datos
        protected $s__nombre;
        protected $s__apellido;
        protected $s__datos_cuadro;                     //guarda una lista de personas (docentes y no docentes), se utiliza para cargar cuadro_personas de la pantalla pant_persona
        protected $s__datos_cuadro_asig;                //para cargar todas las asignaciones de un docente en un cuatrimestre
        protected $s__error;                            //guardamos los datos que generan conflictos en el sistema
        protected $s__accion;                           //guardamos el tipo de accion elegida en los eventos del cuadro y en el boton cargar asignaciones del filtro
        protected $s__datos_cuadro_asignaciones;        //para cargar el cuadro de la pantalla pant_edicion
        protected $s__id_asignacion;                    //guardamos el id del registro seleccionado en la pantalla pant_edicion
        protected $s__datos_form;                       //guardamos datos para cargar al formulario form_asignacion
        protected $s__where;                            //guardamos el where que genera el filtro de la pantalla pant_edicion
        protected $s__horarios_disponibles;
        protected $s__responsable_de_aula;
        protected $s__aula_disponible;                  //guarda los datos enviados desde la operacion "aulas disponibles"
        protected $s__fechas=array();                   //guarda una lista de fechas asociadas a una asignacion_periodo
        protected $s__docentes=array();                 //guarda una listas de docentes filtrados para cargar el cuadro_docentes en la pantalla pant_catedra
        protected $s__datos_form_asignacion;            //guarda los datos cargados en el form_asignacion, sirve para mantener eñ estado del formulario cuando nos cambiamos a las pantallas pant_extra o pant_catedra
        protected $s__docentes_seleccionados=array();   //contiene los miembros del equipo de catedra
        protected $s__cargar_fechas;
        protected $s__calendario;
        protected $s__id_sede;
        protected $s__fecha_consulta;
        protected $s__dia_consulta;
        
        //guardamos la cantidad de dias que forman a un mes. Se configura teniendo en cuenta anios bisiestos
        protected $_meses=array(
            1 => 31, 2 => 0, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31
        );
    
        //guardamos los dias de la semana, esto es util para listar los dias correctos de un periodo
        protected $_dias=array(
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
        );
        
        //#5252C8
        //secuencia de asignacion_periodo : asignacion_periodo_id_asignacion_seq
        //secuencia de esta_formada :  esta_formada_id_asignacion_seq
        
        //-----------------------------------------------------------------------------------
        //---- Pant Edicion -----------------------------------------------------------------
        //-----------------------------------------------------------------------------------
                
        //---- Formulario -------------------------------------------------------------------
        
        /*
         * Este metodo nos permite cambiarnos de pantalla cuando utilizamos el vinculador entre 
         * "aulas disponibles" y "cargar asignaciones"
         */
        function ini__operacion (){
            
            //obtenemos el arreglo almacenado en la operacion "aulas disponibles". Su formato es :
            //Array ('id_aula'=>x 'hora_inicio'=>x 'hora_fin'=>x)
            $datos_ad=toba::memoria()->get_parametros();
            //esta condicion es fundamental para no quedarnos en la misma pantalla
            if(isset($datos_ad['id_aula'])){
                $this->s__accion="Vinculo";
                $this->s__aula_disponible=$datos_ad;
                
                //eliminamos la informacion guardada en el arreglo $_SESSION
                toba::memoria()->limpiar_memoria();
                //$this->set_pantalla('pant_persona');
            }else
                print_r("Ejecutamos sin problemas ini operacion");
            //$this->set_pantalla('pant_asignacion');
        }

//	function conf__formulario(toba_ei_formulario $form)
//	{          
//            $form->colapsar();                  
//	}

	//function evt__formulario__alta($datos)
	//{
//                $this->s__contador=0;
//                $this->s__tipo=$datos['tipo'];
//                print_r($datos);
//                if(strcmp($this->s__tipo, 'Definitiva')==0){
//                    $this->s__dia=$datos['dia'];
//                }
//                
//                $this->set_pantalla('pant_persona');
	//}

	//---- Filtro -----------------------------------------------------------------------
        
        function conf__filtro (toba_ei_filtro $filtro){
            print_r("Se ejecuta conf filtro <br>");
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
         * Este cuadro contiene todas asignaciones que se quieren borrar o editar en el periodo actual.
         */
        function conf__cuadro (toba_ei_cuadro $cuadro){
            if(isset($this->s__where)){
                $cuadro->descolapsar();
                $fecha=date('Y-m-d');
                $anio_lectivo=date('Y');
                $periodos=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($fecha, $anio_lectivo);
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
            //para obtener las asignaciones de un docente
            $this->s__nro_doc=$datos['nro_doc'];
            $this->s__tipo_doc=$datos['tipo_doc'];
            $this->s__responsable_de_aula=$datos['responsable'];
            $this->obtener_asignaciones();
            $this->s__tipo=$datos['tipo'];
            
            $this->set_pantalla('pant_asignacion');
        }
        
        function evt__cuadro__borrar ($datos){
            $this->s__accion="Borrar";
            $this->s__id_asignacion=$datos['id_asignacion'];
            $this->s__tipo=$datos['tipo'];
            //para obtener las asignaciones de un docente
            $this->s__nro_doc=$datos['nro_doc'];
            $this->s__tipo_doc=$datos['tipo_doc'];
            $this->s__responsable_de_aula=$datos['responsable'];
            $this->set_pantalla('pant_asignacion');
        }
        
//        function evt__cuadro__confirmar ($datos){
//            $this->s__accion="Confirmar";
//            $this->s__id_asignacion=$datos['id_asignacion'];
//            $this->s__tipo=$datos['tipo'];
//            
//            $this->set_pantalla('pant_asignacion');
//        }
//        
//        function evt__cuadro__cambiar ($datos){
//            $this->s__accion="Cambiar";
//            $this->s__id_asignacion=$datos['id_asignacion'];
//            $this->s__tipo=$datos['tipo'];
//
//            $this->set_pantalla('pant_asignacion');
//        }
        
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
        
        function evt__volver (){
            $this->set_pantalla('pant_edicion');
        }
        
        
        //---- Pant Persona -----------------------------------------------------------------
        
//        function conf__pant_persona (toba_ei_pantalla $pantalla){
//            $this->pantalla()->tab('pant_edicion')->desactivar();
//            $this->pantalla()->tab('pant_asignacion')->desactivar();
//            $this->pantalla()->tab('pant_extra')->desactivar();
//            $this->pantalla()->tab('pant_catedra')->desactivar();
//        }
                
        //---- Cuadro Personas --------------------------------------------------------------
	function conf__cuadro_personas (toba_ei_cuadro $cuadro)
	{
            //$cuadro->set_datos($this->dep('datos')->tabla('asignacion')->get_listado());
            if(isset($this->s__datos_cuadro)){
                $cuadro->set_titulo("Listado de personas ");
                $cuadro->set_datos($this->s__datos_cuadro);
                
            }
            else{
                $cuadro->colapsar();
            }
	}

	function evt__cuadro_personas__seleccionar ($datos)
	{
                        
            $this->s__nro_doc=$datos['nro_doc'];
            $this->s__tipo_doc=$datos['tipo_doc'];
            $this->s__responsable_de_aula=$datos['nombre'].' '.$datos['apellido'];
            //$this->obtener_asignaciones();
            $this->set_pantalla('pant_asignacion');
	}
        
        //---- Filtro Busqueda -------------------------------------------------------------------
        
        function conf__filtro_busqueda (toba_ei_filtro $filtro){
            //print_r("Este es el perfil de datos: <br>");
            //print_r(toba::usuario()->get_perfil_datos());
            //$filtro->generar_html(); nos genera un nuevo ei pero desplazado
            print_r("Se ejecuta conf filtro busqueda <br>");
        }
        
        function evt__filtro_busqueda__filtrar (){
            
            $periodos=$this->dep('datos')->tabla('periodo')->get_listado(date('Y'));
            
            if(count($periodos)>0){
                $this->s__accion="Registrar";
                
                $where=$this->dep('filtro_busqueda')->get_sql_where('OR');
            
                $personas=$this->dep('datos')->tabla('persona')->get_personas(strtoupper($where));
            
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
        
        function evt__filtro_busqueda__agregar (){
            $this->s__contador += 1;
        }
        
        //---- Formulario --------------------------------------------------------------------
        //En rukaja form_persona se llama formulario, si no cambiamos en nombre se dañan los filtros, filtro y filtro_busqueda
        function conf__formulario (toba_ei_formulario $form){
            print_r("Se ejecua con formulario <br>");
            if(($this->s__contador % 2)==0){
                $form->colapsar();
            }
            else{
                $form->descolapsar();
                $form->set_titulo("Formulario para registrar personas en el sistema");
            }
            
        }
        
        function evt__formulario__alta ($datos){
            //persistir en persona y/o docente. Despues ir a la pantalla pant_asignaciones
            
            $this->dep('datos')->tabla('persona')->nueva_fila($datos);
            $this->dep('datos')->tabla('persona')->sincronizar();
            
            if(strcmp($datos['tipo'], 'Docente')==0){
                $docente=array(
                    'nro_doc' => $datos['nro_doc'],
                    'tipo_doc' => $datos['tipo_doc'],
                    'legajo' => $datos['legajo'],
                    'titulo' => $datos['titulo']
                );
                $this->dep('datos')->tabla('docente')->nueva_fila($docente);
                $this->dep('datos')->tabla('docente')->sincronizar();
                
            }
            
            $this->s__nro_doc=$datos['nro_doc'];
            $this->s__tipo_doc=$datos['tipo_doc'];
            $this->s__nombre=$datos['nombre'];
            $this->s__apellido=$datos['apellido'];
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
        }
        
        //---- Form_Datos -----------------------------------------------------------------------
        
        function conf__form_datos (toba_ei_formulario $form){
//            $form->ef('tipo')->set_estado($this->s__tipo);
            switch($this->s__accion){
                case "Vinculo"   : 
                case "Registrar" : $form->ef('responsable')->set_estado($this->s__responsable_de_aula);
                                   $form->ef('nro_doc')->set_estado($this->s__nro_doc);
                                   $form->ef('tipo_doc')->set_estado($this->s__tipo_doc);
                                   break;               
                case "Borrar"    :
                case "Editar"    :
                case "Confirmar" :
                case "Cambiar"   : $form->set_datos($this->datos_responsable_de_aula());
                                   break;
            }
            
//            if(strcmp($this->s__tipo, 'Definitiva')==0){
//                $form->ef('dia')->set_estado($this->s__dia);
//            }
//            else{
//                $form->desactivar_efs('dia');
//            }
            
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
         * esta funcion permite cargar al combo aulas
         */
        function obtener_aulas_x (){
            //Hay que tener en cuenta el usuario que se loguea
//            $nombre_usuario=toba::usuario()->get_id();
//            $sql="SElECT t_a.nombre, t_a.id_aula 
//                  FROM aula t_a 
//                  JOIN administrador t_admin ON (t_a.id_sede=t_admin.id_sede)
//                  WHERE t_admin.nombre_usuario='$nombre_usuario'";
            $sql="SELECT nombre, id_aula FROM aula WHERE (NOT eliminada)";
            return toba::db('rukaja')->consultar($sql);
            
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
        
        /*
         * esta funcion permite cargar al combo facultades
         */
        function obtener_facultades (){
            
            //$nombre_usuario=toba::usuario()->get_nombre();
            //$sql="SELECT t_s.sigla FROM sede t_s, administrador t_a WHERE t_a.nombre_usuario=$nombre_usuario AND t_s.id_sede=t_a.id_sede";
            //$sql="SELECT t_ua.sigla, t_ua.descripcion FROM unidad_academica t_ua, sede t_s JOIN administrador t_a ON (t_a.nombre_usuario=$nombre_usuario) JOIN (t_a.id_sede=t_s.id_sede) WHERE t_s.sigla=t_ua.id_sede";
            $sql="SELECT sigla, descripcion FROM unidad_academica WHERE sigla <> 'RECT'";
            return toba::db('rukaja')->consultar($sql);
            
        }
        
        //---- Cuadro Asignaciones ----------------------------------------------------------------
        
        function conf__cuadro_asignaciones (toba_ei_cuadro $cuadro){
            //este cuadro se debe actualizar por cada asignacion cargada
            $this->obtener_asignaciones();
            if(count($this->s__datos_cuadro_asig) > 0){
                $cuadro->set_titulo("Asignaciones de {$this->s__responsable_de_aula} ");
                $cuadro->set_datos($this->s__datos_cuadro_asig);
            }
        }
        
        //---- Form Asignacion --------------------------------------------------------------------
        
        function conf__form_asignacion (toba_ei_formulario $form){
            
//            $fin="Por Periodo";
//            if(strcmp($this->s__tipo, 'Definitiva')==0){
//                $fin="Definitivas";
//                $desactivar=array(
//                    'fecha_inicio',
//                    'fecha_fin',
//                    'dias'
//                );
//                $form->desactivar_efs($desactivar);
//            }
            
            if(count($this->s__datos_form_asignacion)>0){
                $form->set_datos($this->s__datos_form_asignacion);
            }
            
            if(strcmp($this->s__accion, "Vinculo") != 0){
                $form->set_titulo("Formulario para {$this->s__accion} Asignaciones");
            }
            else{
                $form->set_titulo("Formulario para Registrar Asignaciones");
            }
            
            switch($this->s__accion){
                case "Nop"       : break;
                case "Registrar" : break;
                case "Vinculo"   : $form->set_datos($this->s__aula_disponible);
                                   $form->set_solo_lectura(array('id_aula', 'dia_semana'));
                                   break;
                case "Borrar"    : 
                case "Editar"    : 
                case "Cambiar"   : 
                case "Confirmar" : $this->configurar_formulario($form); break;
                default :          toba::notificacion()->agregar("La variable accion esta vacia!", 'error');break;
            }
            //$form->agregar_evento('aceptar');
        }
                
        /*
         * Dispara la carga del formulario form_asignacion con datos por defecto
         */
        function configurar_formulario (toba_ei_formulario $form){
            switch ($this->s__tipo){
                case "Definitiva" : $this->cargar_form_definitivo($form); break;
                case "Periodo"    : $this->cargar_form_periodo($form); break;
            }
        }
        
        function cargar_form_definitivo (toba_ei_formulario $form){
           $efs=array(
               'tipo',
               'fecha_inicio',
               'fecha_fin',
               'dias',
           );
           $form->set_efs_obligatorios($efs, FALSE);
           $form->desactivar_efs($efs);
           
           $sql="SELECT t_a.*, 'Definitiva' as tipo, t_d.nombre as dia_semana
                 FROM asignacion t_a 
                 JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                 JOIN persona t_p ON (t_a.nro_doc=t_p.nro_doc)
                 JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
                 WHERE t_a.id_asignacion={$this->s__id_asignacion}";
                 
           $asignacion=toba::db('rukaja')->consultar($sql);      
           
           $form->ef('tipo')->set_estado($asignacion[0]['tipo']);
           $form->ef('dia_semana')->set_estado($asignacion[0]['dia']);
           $form->set_datos($asignacion[0]);
            
        }
        
        function cargar_form_periodo (toba_ei_formulario $form){
            $efs=array('tipo','dia_semana');
            $form->set_efs_obligatorios($efs, FALSE);
            $form->desactivar_efs($efs);
            
            $sql="SELECT * , t_f.nombre as dia
                  FROM asignacion t_a 
                  JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
                  JOIN persona t_pe ON (t_a.nro_doc=t_pe.nro_doc)
                  JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion)
                  JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion)
                  WHERE t_a.id_asignacion={$this->s__id_asignacion}";
                  
            $asignaciones=toba::db('rukaja')->consultar($sql);
            
            $dias=array();
            foreach ($asignaciones as $clave=>$valor){
                $dias[]=$valor['dia'];
            }
            
            $form->ef('dias')->set_estado($dias);
            $form->ef('tipo')->set_estado($this->s__tipo);
            $form->set_datos($asignaciones[0]);
            
        }
        
        /*
         * Boton aceptar del formulario form_asignacion
         */
        function evt__form_asignacion__aceptar ($datos){
            print_r($this->s__accion);
            switch($this->s__accion){
                case "Vinculo"      :      $this->procesar_vinculo($datos);break;
                case "Registrar"    :      $this->procesar_carga($datos); break;
                case "Borrar"       :      $this->procesar_delete($datos); break;
                case "Editar"       :      $this->procesar_edicion($datos); break;
                case "Cambiar"      :      $this->procesar_cambio($datos); break;
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
            print_r($datos_form_asignacion);
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
            $this->set_pantalla('pant_catedra');
        }
        
        function registrar_asignacion ($datos){
            
            $datos['nro_doc']=$this->s__nro_doc;
            $datos['tipo_doc']=$this->s__tipo_doc;        
            $this->dep('datos')->tabla('asignacion')->nueva_fila($datos);
            $this->dep('datos')->tabla('asignacion')->sincronizar();
            $this->dep('datos')->tabla('asignacion')->resetear();
            
        }
        
        function registrar_asignacion_definitiva ($datos){
            $cuatrimestre=$this->obtener_cuatrimestre();
            $fecha=  getdate();
            $secuencia=recuperar_secuencia('asignacion_id_asignacion_seq');
            
            $dato=array(
              'nombre' => $datos['dia_semana'], 
              'cuatrimestre' => $cuatrimestre, 
              'anio' => $fecha['year'], 
              'id_asignacion' => $secuencia,
            );
                                  
            $this->dep('datos')->tabla('asignacion_definitiva')->nueva_fila($dato);
            $this->dep('datos')->tabla('asignacion_definitiva')->sincronizar();
            $this->dep('datos')->tabla('asignacion_definitiva')->resetear();
        }
        
        function registrar_asignacion_periodo ($datos){
            $cuatrimestre=$this->obtener_cuatrimestre();
            $fecha=  getdate();
            $secuencia=recuperar_secuencia('asignacion_id_asignacion_seq');
            $dato=array(
              
              'cuatrimestre' => $cuatrimestre,
              'anio' => $fecha['year'],
              
            );
            
            $periodo=array(
                    'id_asignacion' => $secuencia,
                    'fecha_inicio' => $datos['fecha_inicio'],
                    'fecha_fin' => $datos['fecha_fin']
            );
            $this->dep('datos')->tabla('asignacion_periodo')->nueva_fila($periodo);
            $this->dep('datos')->tabla('asignacion_periodo')->sincronizar();
            $this->dep('datos')->tabla('asignacion_periodo')->resetear();
            
            //en esta seccion se guarda informacion en la tabla esta_formada
            $dias=$datos['dias'];
            foreach ($dias as $dia){
                $dato['nombre']=$dia;
                $dato['id_asignacion']=$secuencia;
                $this->dep('datos')->tabla('esta_formada')->nueva_fila($dato);
                $this->dep('datos')->tabla('esta_formada')->sincronizar();
                $this->dep('datos')->tabla('esta_formada')->resetear();
            }
                        
        }
        
        /*
         * Devuelve true si una asignacion definitiva se puede cargar en el sistema.
         * @datos contiene una asignacion definitiva.
         */
        function existe_definitiva ($datos){
            $cuatrimestre=$this->obtener_cuatrimestre();
            $fecha=  getdate();
            $anio=$fecha['year'];
            //$resultado=FALSE;
            //en la fecha actual no debe existir ninguna asignacion por periodo o parte de la misma
            //incluir en JOIN asignacion_periodo AND ($fecha_actual BETWEEN t_p.fecha_inicio AND t_p.fecha_fin)
            $fecha_actual=date('y-m-d');
//            $sql="(SELECT t_a.id_asignacion, t_a.finalidad, t_a.hora_inicio, t_a.hora_fin 
//                   FROM asignacion t_a 
//                   JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
//                   JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
//                   WHERE t_d.nombre='{$this->s__dia}' AND t_d.cuatrimestre=$cuatrimestre AND t_d.anio=$anio AND t_au.id_aula={$datos['id_aula']} AND ('{$datos['hora_inicio']}' BETWEEN t_a.hora_inicio AND t_a.hora_fin) AND ('{$datos['hora_fin']}' BETWEEN t_a.hora_inicio AND t_a.hora_fin)) 
//                      
//                   UNION 
//                  
//                  (SELECT t_a.id_asignacion, t_a.finalidad, t_a.hora_inicio, t_a.hora_fin 
//                   FROM asignacion t_a
//                   JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
//                   JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion )
//                   JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion)    
//                   WHERE t_f.nombre='{$this->s__dia}' AND t_f.cuatrimestre=$cuatrimestre AND t_f.anio=$anio AND t_au.id_aula={$datos['id_aula']} AND ('{$datos['hora_inicio']}' BETWEEN t_a.hora_inicio AND t_a.hora_fin) AND ('{$datos['hora_fin']}' BETWEEN t_a.hora_inicio AND t_a.hora_fin))                
//                   ";
//            $asignacion=toba::db('gestion_aulas')->consultar($sql);
            $asignaciones=$this->dep('datos')->tabla('asignacion')->get_asignaciones_por_aula($datos['dia_semana'], $cuatrimestre, $anio, $datos['id_aula']);
            
            //print_r($asignaciones);exit();
            
            //el formato de este arreglo es : indice => array('id_aula'=>x 'aula'=>y). La función solamente
            //requiere el id_aula
            $aulas=array(array('id_aula'=>$datos['id_aula']));
                        
            //obtenemos un arreglo con todos los horarios disponibles
            $horarios_disponibles=$this->obtener_horarios_disponibles($aulas, $asignaciones);
            
//            print_r("<br><br> Estos son los horarios disponibles : <br><br>");
//            print_r($this->s__horarios_disponibles);exit();
            return ($this->verificar_inclusion_de_horario($datos['hora_inicio'], $datos['hora_fin']));
                //$resultado=TRUE;
            //}
            
            //return $resultado;
        }
                
        /*
         * Esta funcion dispara el calculo de horarios disponibles
         */
        function obtener_horarios_disponibles ($aulas, $horarios_ocupados){
            //$horarios_disponibles=array();
            foreach ($aulas as $clave=>$aula){
                //obtenemos los horarios ocupados para un aula especifica
                //$horarios_ocupados_por_aula=$this->obtener_horarios_ocupados_por_aula($aula, $horarios_ocupados);
                //print_r(gettype($horarios_ocupados_por_aula));
                //$aula no es necesario, quitar mas adelante
                
                //obtenemos todos los horarios ocupados y disponibles
                $horarios=$this->calcular_espacios_disponibles($aula, $horarios_ocupados);
                
                $horarios_depurados=$this->depurar_horarios($horarios, $aula);
                
                //$horarios_disponibles[]=$horarios_depurados;
            }
            
            //return $horarios_disponibles;
        }
        
        /*
         * devuelve los horarios ocupados por aula
         * @$horarios_ocupados contiene todos los horarios ocupados por aula
         */
        function obtener_horarios_ocupados_por_aula ($aula, $horarios_ocupados){
            $fin=FALSE;
            $i=0;
            $longitud=count($horarios_ocupados);
            
            while ($i<$longitud && !$fin){
                $elemento=$horarios_ocupados[$i];
                if(strcmp($elemento[0]['id_aula'], $aula['id_aula']) == 0){
                    $fin=TRUE;
                }
                
                $i += 1;
            }
            
            return $elemento; // indice => Array ( 0 => Array(), 1 => Array() ... )
        }
        
        /*
         * Devuelve un arreglo con los horarios disponibles para un aula x
         * @horarios contiene los horarios ocupados y disponibles para un aula 
         * TRUE indica horario disponible
         * FALSE indica horario ocupado 
         */
        function depurar_horarios ($horarios, $aula){
            $horarios_disponibles=array();
            $indice=0;
            $longitud=count($horarios);
            $indice_horario=0;
            //guarda un horario disponible con el formato (hora_inicio, hora_fin, aula)
            $horario=array();
            $hora_fin="";
            while($indice_horario < $longitud){
                if($horarios[$indice_horario][1]){
                    
                    $hora_inicio=$horarios[$indice_horario][0];
                    $horario['hora_inicio']=$hora_inicio;

                    //aca no hay que acumular el retorno
                    $indice_horario = $this->obtener_horario($indice_horario, $horarios, &$hora_fin);
                    $horario['hora_fin']=$hora_fin;
                    $horario['aula']=$aula['aula'];
                    $horario['id_aula']=$aula['id_aula'];
                    $horarios_disponibles[$indice]=$horario;
                    //los eltos se agregan al final del arreglo
                    $this->s__horarios_disponibles[]=$horario;
                    $indice += 1;
                }
                else{
                    $indice_horario += 1;
                }
            }
            return $horarios_disponibles;
        }
        
        function obtener_horario ($indice_horario, $horarios, $hora_fin){
            
            $longitud=count($horarios);
            $fin=FALSE;
            while(($indice_horario < $longitud) && !$fin){
                
                if(!$horarios[$indice_horario][1]){
                    $hora_fin=$horarios[$indice_horario][0];

                    $fin=TRUE;
                }
                $indice_horario += 1;
            }
            if((($indice_horario - 1)<$longitud) && $horarios[($indice_horario-1)][1]){
                $hora_fin=$horarios[($indice_horario-1)][0];
            }
            return $indice_horario;
        }
        
        /*
         * calcula los espacios disponibles en un aula (se tiene en cuenta el dia)
         * @espacios es un arreglo con todos los horarios ocupados en un aula x (se tiene en cuenta el dia).
         * Espacios tambien puede contener NULL, en este caso el foreach no genera ningun error al trabajar con la variable
         * nula. Lo que devolvemos en este caso es un arreglo con los horarios de 8 a 22 marcados en FALSE
         * lo cual es correcto.
         */
        function calcular_espacios_disponibles ($aula, $espacios){
            
                        
            //creo un arreglo con todos los horarios de cursado por dia
            $horarios=$this->crear_horarios();
            $longitud=count($horarios);
            foreach ($espacios as $clave=>$espacio){
                $indice=0; //debe ir ahi porque el arreglo no esta ordenado
                $fin=FALSE;
                while(($indice < $longitud) && !$fin){
                    
                    if(strcmp(($horarios[$indice][0]), ($espacio['hora_inicio'])) == 0){
//                        print_r(strcmp(($horarios[$indice][0]), ($espacio['hora_inicio'])));

                        
                        //para que el arreglo horarios pueda ser modificado en la rutina eliminar_horarios
                        //hay que realizar un pasaje de parametros por referencia (&horarios)
                        $this->eliminar_horario(&$horarios, $indice, $longitud, $espacio['hora_fin']);
                        
                        $fin=TRUE;
                        
                        //para volver a recorrer todo el arreglo de  nuevo en la proxima iteracion.
                        //Evita conflictos si el arreglo no esta ordenado.
                        $indice=0;
                    }
                    else{
                        $indice += 1;
                    }
                }
            }
            return $horarios;
        }
        
        /*
         * @horarios contiene los horarios de clase para un dia de semana
         * @indice contiene la posicion desde donde hay que borrar horarios
         * @longitud contiene la cantidad de eltos que posee el arreglo horarios 
         * @hora_fin indica un tope para borrar horarios
         */
        function eliminar_horario ($horarios, $indice, $longitud, $hora_fin){
            $fin=FALSE;
            while(($indice < $longitud) && !$fin){
                
                
                //asignando false indicamos que un espacio ya esta ocupado
                $horarios[$indice][1]=FALSE;
                if(strcmp(($horarios[$indice][0]), $hora_fin) == 0){
                    $fin=TRUE;
                }
                $indice += 1;
            }
            
            return $indice;
        }
        
        /*
         * crea un arreglo con los horarios disponbles 
         */
        function crear_horarios (){
            $hora=8;
            $indice=0;
            $prefijo="";
            $horarios=array();
            while($hora <= 22){
                
                $prefijo=($hora <= 9) ? "0".$hora : $hora ;
                
                $horarios[$indice]=array(
                    0 => "$prefijo:00:00",
                    1 => TRUE
                );
                $indice += 1;
                //replica, para obtener los horarios disponibles
                $horarios[$indice]=array(
                    0 => "$prefijo:00:00",
                    1 => TRUE
                );
                $indice += 1;
                $horarios[$indice]=array(
                    0 => "$prefijo:15:00",
                    1 => TRUE
                );
                $indice += 1;
                $horarios[$indice]=array(
                    0 => "$prefijo:30:00",
                    1 => TRUE
                );
                $indice += 1;
                //replica, para obtener los horarios disponibles
                $horarios[$indice]=array(
                    0 => "$prefijo:30:00",
                    1 => TRUE
                );
                $indice += 1;
                $horarios[$indice]=array(
                    0 => "$prefijo:45:00",
                    1 => TRUE
                );
                
                $indice += 1;
                $hora += 1;
                
            }
            
            return $horarios;
        }
        
        /*
         * Devuelve true si una asignacion por perido se puede cargar en el sistema.
         * El horario puede estar ocupado por parte de una asignacion por perido o una asignacion 
         * definitiva. Para realizar con exito la validacion hay que utilizar las fechas del periodo que
         * se quiere registrar.
         * @datos contiene una asignacion por periodo.
         */
        function existe_periodo ($datos){
            //obtenemos los dias del periodo con el siguiente formato: 
            // dias => Array( indice => DiaSeleccionado); Indice empieza en cero
            $dias=$datos['dias']; 
            $cuatrimestre=$this->obtener_cuatrimestre();
            $fecha=  getdate();
            $anio=$fecha['year'];
            $resultado=TRUE;
            $i=0;
            $longitud=count($dias);
            //Incluir en el join de asignacion_periodo : AND (t_p.fecha_inicio BETWEEN '{$datos['fecha_inicio']}' AND '{$datos['fecha_fin']}')
            while (($i < $longitud) && $resultado){
//                $sql="(SELECT t_a.id_asignacion, t_a.finalidad, t_a.hora_inicio, t_a.hora_fin  
//                       FROM asignacion t_a 
//                       JOIN aula t_au ON (t_a.id_aula=t_au.id_aula) 
//                       JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion  ) 
//                       JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion) 
//                       WHERE t_f.nombre='{$dias[$i]}' AND t_f.cuatrimestre=$cuatrimestre AND t_f.anio=$anio AND t_au.id_aula={$datos['id_aula']} AND ('{$datos['hora_inicio']}' BETWEEN t_a.hora_inicio AND t_a.hora_fin) AND ('{$datos['hora_fin']}' BETWEEN t_a.hora_inicio AND t_a.hora_fin) ) 
//                       
//                       UNION 
//                       
//                      (SELECT t_a.id_asignacion, t_a.finalidad, t_a.hora_inicio, t_a.hora_fin 
//                       FROM asignacion t_a 
//                       JOIN aula t_au ON (t_a.id_aula=t_au.id_aula) 
//                       JOIN asignacion_definitiva t_d ON (t_a.id_asignacion=t_d.id_asignacion)
//                       WHERE t_d.nombre='{$dias[$i]}' AND t_d.cuatrimestre=$cuatrimestre AND t_d.anio=$anio AND t_a.id_aula={$datos['id_aula']} AND ('{$datos['hora_inicio']}' BETWEEN t_a.hora_inicio AND t_a.hora_fin) AND ('{$datos['hora_fin']}' BETWEEN t_a.hora_inicio AND t_a.hora_fin) )";
//                $asignacion=toba::db('gestion_aulas')->consultar($sql);
                $asignaciones=$this->dep('datos')->tabla('asignacion')->get_asignaciones_por_aula($dias[$i], $cuatrimestre, $anio, $datos['id_aula']);
                
                print_r("<br><br>Estas son las asignaciones para el dia {$dias[$i]} : <br>");
                foreach ($asignaciones as $clave=>$valor){
                    print_r("{$valor['hora_inicio']} {$valor['hora_fin']}");
                }
                //el formato de este arreglo es : indice => array('id_aula'=>x 'aula'=>y). La función solamente
                //requiere el id_aula
                $aulas=array(array('id_aula'=>$datos['id_aula']));
                        
                //obtenemos un arreglo con todos los horarios disponibles
                $horarios_disponibles=$this->obtener_horarios_disponibles($aulas, $asignaciones);
                
                if(!$this->verificar_inclusion_de_horario($datos['hora_inicio'], $datos['hora_fin'])){
                    $resultado=FALSE;
                }
                print_r("<br><br> Este es el valor de resultado : $resultado <br><br>");
                $this->s__horarios_disponibles=array();
                $i += 1;
            }
            
            return $resultado;
        }
        
        /*
         * devuelve false si el contenido de $datos no existe en la BD. (NO SE USA)
         */
//        function existe ($datos){
//            print_r("<br><br>Este es el contenido de datos : <br><br>");
//            print_r($datos);
//            $cuatrimestre=$this->obtener_cuatrimestre();
//            $fecha=date('Y-m-d'); // con Y mayuscula obtenemos el año completo, 2015
//            print_r($fecha);
//            $fecha2=  getdate();
//            $anio=$fecha2['year'];
//            print_r($anio);
//            if(strcmp($this->s__tipo, "Definitiva")==0){
//                $sql=" 
//                      
//                      UNION 
//                  
//                      SELECT t_a.id_asignacion 
//                      FROM asignacion t_a
//                      JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
//                      JOIN asignacion_periodo t_p ON (t_a.id_asignacion=t_p.id_asignacion and ('$fecha' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin)) 
//                      JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion)
//                      WHERE t_f.nombre='{$this->s__dia}' AND t_f.cuatrimestre=$cuatrimestre AND t_f.anio={$fecha['year']} AND t_au.id_aula={$datos['id_aula']} AND t_a.hora_inicio='{$datos['hora_inicio']}'";
//                $definitiva=toba::db('gestion_aulas')->consultar($sql);  
//                
//                print_r("<br><br>Esta es la asignacion : <br><br>");
//                print_r($definitiva);exit();
//                return ((count($definitiva)>0) ? true : false);
//                  
//            }
//            else{
//                  "UNION 
//                  
//                   SELECT t_a.id_asignacion 
//                   FROM asignacion t_a
//                   JOIN aula t_au ON (t_a.id_aula=t_au.id_aula)
//                   JOIN asignacion_periodo t_d ON (t_a.id_asignacion=t_p.id_asignacion and ('' BETWEEN t_p.fecha_inicio AND t_p.fecha_fin)) 
//                   JOIN esta_formada t_f ON (t_p.id_asignacion=t_f.id_asignacion)
//                   WHERE t_f.nombre={}";
//                  $periodo=toba::db('gestion_aulas')->consultar($sql);
//            }
//            
//        }
        
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
        function obtener_asignaciones (){ //HAY QUE LLEVARLO AL DATOS_TABLA
            //tiene cierto sentido usar la fecha actual, dado que el uso de las aulas puede ser dinamico. 
            //Necesitamos saber como es la asignacion de horarios en un momento determinado.
            $fecha=  date('Y-m-d');
            
            $anio_lectivo=date('Y');
            
            $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($fecha, $anio_lectivo);
            
            //analizamos el caso de dos periodos, cuando tenemos un turno de examen extraordinario incluido
            //en un cuatrimestre.
            $this->s__datos_cuadro_asig=array();
            foreach ($periodo as $clave=>$valor){
                $asignaciones=$this->dep('datos')->tabla('asignacion')->get_asignaciones_por_persona($this->s__nro_doc, $valor['id_periodo'], $fecha);
                $this->unificar_conjuntos(&$this->s__datos_cuadro_asig, $asignaciones);
            }
            
        }
        
        /*
         * Esta funcion realiza una union de conjuntos.
         * @ $periodo : se realiza pasaje de parametros por referencia.
         */
        function unificar_conjuntos ($periodo, $conjunto){
            foreach ($conjunto as $clave=>$valor){
                if(isset($valor)){
                   $periodo[]=$valor; //agrega al final
                }
            }            
        }
        
        /*
         * copia de seguridad del contenido del metodo evt__form_asignacion__cargar
         */
        function backup ($datos){
            //verificamos si la asignacion ya existe
            //if(!$this->existe($datos)){
            //persistir en asignacion, asignacion_definitiva y asignacion_periodo
            //hay que inferir a que cuatrimestre pertenece la asignacion
            
            
            $datos['nro_doc']=$this->s__nro_doc;
            $datos['tipo_doc']=$this->s__tipo_doc;        
            $this->dep('datos')->tabla('asignacion')->nueva_fila($datos);
            $this->dep('datos')->tabla('asignacion')->sincronizar();
            $this->dep('datos')->tabla('asignacion')->resetear();
            $cuatrimestre=$this->obtener_cuatrimestre();
            $fecha=  getdate();
            $dato=array(
              
              'cuatrimestre' => $cuatrimestre,
              'anio' => $fecha['year'],
              
            );
            $secuencia=recuperar_secuencia('asignacion_id_asignacion_seq');
            if(strcmp($this->s__tipo, 'Definitiva')==0){
                $dato['id_asignacion']=$secuencia;
                $dato['nombre'] = $this->s__dia;
                $this->dep('datos')->tabla('asignacion_definitiva')->nueva_fila($dato);
                $this->dep('datos')->tabla('asignacion_definitiva')->sincronizar();
                $this->dep('datos')->tabla('asignacion_definitiva')->resetear();
            }
            else{ 
                $periodo=array(
                    'id_asignacion' => $secuencia,
                    'fecha_inicio' => $datos['fecha_inicio'],
                    'fecha_fin' => $datos['fecha_fin']
                );
                $this->dep('datos')->tabla('asignacion_periodo')->nueva_fila($periodo);
                $this->dep('datos')->tabla('asignacion_periodo')->sincronizar();
                $this->dep('datos')->tabla('asignacion_periodo')->resetear();
                //en esta seccion se guarda informacion en la relacion esta_formada
                $dias=$datos['dias'];
                foreach ($dias as $dia){
                    $dato['nombre']=$dia;
                    $dato['id_asignacion']=$secuencia;
                    $this->dep('datos')->tabla('esta_formada')->nueva_fila($dato);
                    $this->dep('datos')->tabla('esta_formada')->sincronizar();
                    $this->dep('datos')->tabla('esta_formada')->resetear();
                }
            }
//            }
//            else{
//                toba::notificacion()->agregar("No es posible registrar la asignación porque ya existe", 'error');
//            }
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
        
        //---- Metodos para procesar la accion elegida en el cuadro de la pantalla pant_edicion
        
        /*
         * Permite cargar una asignacion definitiva o por periodo. 
         */
        function procesar_carga ($datos){
            
            if(strcmp($datos['tipo'], "Definitiva")==0){
                $this->s__dia=$datos['dia_semana'];
                
                //if($this->validar_datos($datos['hora_inicio'], $datos['hora_fin'])){
                    //if($this->existe_definitiva($datos)){
                        $secuencia=  recuperar_secuencia('asignacion_id_asignacion_seq');
                        $this->registrar_asignacion($datos);
                        $this->registrar_asignacion_definitiva($datos);
                        //agregamos el equipo de catedra si existe
                        if(count($this->s__docentes_seleccionados)>0){
                            foreach($this->s__docentes_seleccionados as $clave=>$docente){
                                $catedra=array(
                                    'id_asignacion' => $secuencia,
                                    'nro_doc' => $docente['nro_doc'],
                                    'tipo_doc' => $docente['tipo_doc']
                                );
                                
                                $this->dep('datos')->tabla('catedra')->nueva_fila($catedra);
                                $this->dep('datos')->tabla('catedra')->sincronizar();
                                $this->dep('datos')->tabla('catedra')->resetear();
                            }
                        }
                    //}
//                    else{
//                        $mensaje=" Está intentando solapar asignaciones ";
//                        //$mensaje="Error Horario Repetido {$this->s__error}";
//                        toba::notificacion()->agregar(utf8_d_seguro($mensaje));
//                    }
//                }
//                else{
//                    $mensaje=" La hora de inicio debe ser menor a la hora de fin ";
//                    toba::notificacion()->agregar($mensaje);
//                }
            }
            else{
                if($this->validar_datos($datos['hora_inicio'], $datos['hora_fin'], $datos['fecha_inicio'], $datos['fecha_fin'])){
                    if($this->existe_periodo($datos)){
                        $this->registrar_asignacion($datos);
                        $this->registrar_asignacion_periodo($datos);
                    }
                    else{
                        $mensaje=" Está intentando solapar asignaciones ";
                        toba::notificacion()->agregar(utf8_decode($mensaje), $nivel);
                    }
                }
                else{
                    $mensaje=" Datos inconsistentes en la fecha u hora ";
                    toba::notificacion()->agregar($mensaje, $nivel);
                }
            }
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
         * Esta funcion gestiona un vinculo establecido entre operaciones. Realiza una validacion de 
         * horas y produce la carga de datos en el sistema
         */
        function procesar_vinculo ($datos){
            $hora_inicio=$this->s__aula_disponible['hora_inicio'];
            $hora_inicio_datos="{$datos['hora_inicio']}:00";
            $hora_fin=$this->s__aula_disponible['hora_fin'];
            $hora_fin_datos="{$datos['hora_fin']}:00";
            
            if(($hora_inicio_datos < $hora_fin_datos) && (($hora_inicio_datos >= $hora_inicio) && ($hora_inicio_datos <= $hora_fin)) && ($hora_fin_datos <= $hora_fin)){
               $this->procesar_carga($datos);
               $this->s__accion="Nop";
            }
            else{
                $mensaje=" El horario especificado no pertenece al rango disponible : $hora_inicio y $hora_fin hs ";
                toba::notificacion()->agregar($mensaje);
            }
        }
        
        /*
         * eliminamos una asignacion definitiva o por periodo
         */
        function procesar_delete ($datos){
            
            $this->dep('datos')->tabla('asignacion')->cargar(array('id_asignacion'=>$this->s__id_asignacion));
            $asignacion=$this->dep('datos')->tabla('asignacion')->get();
            $this->dep('datos')->tabla('asignacion')->eliminar_fila($asignacion['x_dbr_clave']);
            $this->dep('datos')->tabla('asignacion')->sincronizar();
        }
        
        /*
         * modificamos una asignacion existente
         */
        function procesar_edicion ($datos){
            
            $this->dep('datos')->tabla('asignacion')->cargar(array('id_asignacion'=>$this->s__id_asignacion));
            $datos['id_asignacion']=$this->s__id_asignacion;
            $this->dep('datos')->tabla('asignacion')->set($datos);
            $this->dep('datos')->tabla('asignacion')->sincronizar();
        }
        
        /*
         * registramos una asignacion por perido que dura un dia
         */
        function procesar_cambio ($datos){
            //validamos que no exista otro periodo, para ello tomamos la fecha actual y obtenemos todos los periodo 
            //que existen con sus respectivos dias y verificamos que el espacio que se quiere cargar no este ocupado en esos 
            //dias
            
        }
        
        /*
         * registramos una asignacion vieja en el año actual
         */
        function procesar_confirmacion ($datos){
            $this->registrar_asignacion($datos);
            $this->registrar_asignacion_periodo($datos);
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
        
        //---- Calendario ------------------------------------------------------------------------------
        
//        function conf__calendario (toba_ei_calendario $calendario){
//            $calendario->set_sab_seleccionable(true); 
//            $calendario->set_dom_seleccionable(true);
//            $calendario->set_seleccionar_solo_dias_pasados(false);
//        }
        
//        function evt__calendario__seleccionar_dia ($seleccion){
//            $fecha="{$seleccion['dia']}-{$seleccion['mes']}-{$seleccion['anio']}";
//            print_r($this->s__fechas);
//            
//            $fecha_inicio=$this->s__datos_form_asignacion['fecha_inicio'];
//            $fecha_inicio=date('d-m-Y', strtotime($fecha_inicio));
//            $fecha_fin=$this->s__datos_form_asignacion['fecha_fin'];
//            $fecha_fin=date('d-m-Y', strtotime($fecha_fin));
//            //verificamos que la fecha seleccionada del calendario este en el rango especificado por el
//            //usuario en el formulario form_asignacion.
//            if(($fecha >= $fecha_inicio) && ($fecha <= $fecha_fin)){
//                if(!($this->operar_sobre_fecha($fecha, 'f'))){
//                    $this->s__fechas[]=array('fecha'=>$fecha);
//                }
//            }
//            else{
//                toba::notificacion()->agregar(" La fecha seleccionada, $fecha, no pertenece al rango [ $fecha_inicio, $fecha_fin ] ");
//            }
//        }
        
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
                //$r=strcmp($fecha['fecha'], $fecha_calendario);
                //print_r("<br> Este es el resultado de comparar fechas, {$fecha['fecha']} = $fecha_calendario -> $r. <br>");
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
            $fechas=$this->get_dias($fecha_inicio, $fecha_fin, $dias_seleccionados);
            
            print_r($fechas);
            if(count($fechas)>0){
                $cuadro->set_datos($this->crear_estructura_cuadro($fechas));
            }
        }
        
        function evt__cuadro_fechas__seleccionar ($fecha_cuadro){
            //disparamos el calculo de horarios para la fecha seleccionada
        }
        
        function evt__cuadro_fechas__eliminar ($fecha_cuadro){
            $r=$this->operar_sobre_fecha($fecha_cuadro['fecha'], 'e');
        }
        
        function evt__volver_a_asig (){
            $this->set_pantalla('pant_asignacion');
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
        }
        
        //---- Filtro Docentes -------------------------------------------------------------------------
        
        function conf__filtro_docentes (toba_ei_filtro $filtro){
           
        }
        
        function evt__filtro_docentes__filtrar ($datos){
            
            $where=$this->dep('filtro_docentes')->get_sql_where('OR');
            $this->s__docentes=$this->dep('datos')->tabla('persona')->get_docentes(strtoupper($where));
        }
                
        //---- Cuadro Docentes -------------------------------------------------------------------------
        
        /*
         * Este cuadro guarda a un cjto. de docentes, que son el resultado de una consulta en la db.
         */
        function conf__cuadro_docentes (toba_ei_cuadro $cuadro){
            if(count($this->s__docentes)>0){
                $cuadro->set_datos($this->s__docentes);
            }
        }
        
        /*
         * Este cuadro guarda el los docentes seleccionados por el usuario.
         */
        function evt__cuadro_docentes__seleccionar ($docente_seleccionado){
            if(!($this->operar_sobre_docentes($docente_seleccionado, 'd'))){
                $this->s__docentes_seleccionados[]=$docente_seleccionado;
            }
        }
        
        /*
         * operar_sobre_docentes cumple con dos funciones :
         * a) verifica si un docente existe en la estructura s__docentes_seleccionados, para ello debemos pasar como
         * segundo parametro la letra d.
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
        
        //---- METODO AJAX -----------------------------------------------------------------------------
        
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
            toba::memoria()->set_dato_instancia(1, $parametros[0]); //hora_inicio
            toba::memoria()->set_dato_instancia(2, $parametros[1]); //hora_fin
            toba::memoria()->set_dato_instancia(3, $parametros[2]); //id_periodo
            toba::memoria()->set_dato_instancia(4, $parametros[3]); //tipo (Definitiva, Periodo)
            toba::memoria()->set_dato_instancia(5, $parametros[4]); //dia_semana
            
            $respuesta->set(array('clave' => 'no alterar ajax'));
            //$respuesta->agregar_cadena('clave', 'no alterar ajax');
            
        }
        
        //----------------------------------------------------------------------------------------------
        //---- Funcion para procesar periodos ----------------------------------------------------------
        //----------------------------------------------------------------------------------------------
        
        function procesar_periodo ($periodos, $i){
            foreach($periodos as $clave=>$valor){
                switch ($valor['tipo_periodo']){
                    case 'Cuatrimestre' : if(strcmp($i, "hd")==0){
                                            $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_cuatrimestre($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
                                          }else{
                                               $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones($this->s__where, $valor['id_periodo']);
                                          }
                                          
                                          break;
                                          
                    case 'Examen Final' : if(strcmp($i, "hd")==0){
                                            $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_examen_final($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
                                          }else{
                                                $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones($this->s__where, $valor['id_periodo']);
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
                                //print_r("<br>");
                                //print_r($fecha);
                                //print_r("<br>");
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
            //print_r("Mes Inicial : $mes_inicial <br>");
            //$tipo=  gettype($mes_inicial);
            //print_r("Tipo de dato : $tipo <br>");
            //print_r("Dias del mes : $mes <br>");
            for($i=$dia_inicial; $i<=$mes; $i++){
                //print_r("Dia inicial : $i <br>");
                $fecha=  date('d-m-Y', strtotime("$i-$mes_inicial-$anio"));
                //print_r("<br>");
                //print_r($fecha);
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
                //print_r("Este es el dia inicio : $dia_inicio <br>");
                //print_r(utf8_decode($this->_dias[$dia_inicio]));
                if(strcmp(utf8_decode($this->_dias[$dia_inicio]), $dia)==0){
                    $fin=TRUE;
                }
                $i++;
            }
            return $fin;
        }
        
        function crear_estructura_cuadro ($fechas){
            $cuadro=array();
            //necesitamos la sede del usuario logueado
            $nombre_usuario=toba::usuario()->get_id();
            $this->s__id_sede=$this->dep('datos')->tabla('persona')->get_sede_para_usuario_logueado($nombre_usuario);
            $this->s__id_sede=1;
            toba::memoria()->limpiar_datos_instancia();
            
            $hd=new HorariosDisponibles();
            $aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($this->s__id_sede);
            
            foreach($fechas as $clave=>$fecha){
                //guardamos el id_sede en sesion para agregar la capacidad del aula al resultado
                toba::memoria()->set_dato_instancia(0, $this->s__id_sede);
                //guardamos la fecha de consulta para extraer asig. en procesar_periodo
                $this->s__fecha_consulta=date('Y-m-d', strtotime($fecha));
                $this->s__dia_consulta=utf8_decode($this->obtener_dia(date('N', strtotime($this->s__fecha_consulta))));
                //con la fecha obtenemos los periodos academicos correspondientes
                $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($fecha, date('Y', strtotime($fecha)));
                //con el periodo obtenemos las asignaciones
                $asignaciones=$this->procesar_periodo($periodo, "hd");
                //obtenemos las aulas que actualmente estan ocupadas
                $aulas=$this->obtener_aulas($asignaciones);
                //creamos un objeto HorariosDisponibles y realizamos el calculo de horarios
                
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