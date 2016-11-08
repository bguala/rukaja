<?php

include_once(toba_dir().'/proyectos/rukaja/php/api/HorariosDisponibles.php');
include_once(toba_dir().'/proyectos/rukaja/php/api/Filtro.php');
include_once(toba_dir().'/proyectos/rukaja/php/api/Email.php');

class ci_solicitar_aula extends toba_ci
{
        protected $s__id_sede;
        protected $s__fecha_consulta;
        protected $s__dia_consulta;
        protected $s__horarios_disponibles;
        protected $s__datos_form;
        protected $s__datos_filtrados;
        protected $s__datos_cuadro;
        protected $s__solicitud_registrada;
        protected $s__filtro;
        protected $s__sigla_origen;                     //Guardamos la sigla del establecimiento que realiza un pedido de aula.


        //Estas variables se configuran cuando hay que realizar un calculo de horarios disponibles global.
        protected $s__establecimiento;
        protected $s__sede;
        protected $s__hd_global;  
        protected $s__solo_fecha;
        //Guardamos el id del responsable de aula, puede ser docente u organizacion. Esto se hace  porque cuando
        //pulsamos el boton Registrar Solicitud, se ejecuta una llamada ajax asociado al popup y borra el contenido
        //de dicho campo. Como consecuencia al servidor no llega el legajo o el id_organizacion. Esta 
        //informacion es fundamental paea registrar asignaciones.
        protected $s__id_responsable;
        
        protected $s__id_sede_origen;
        
        protected $s__datos_solicitud;
        
        protected $s__accion;
        
        protected $s__tipo;
        
        protected $s__datos_multi=array();
                
        //-------------------------------------------------------------------------------------
        //---- Procesamos un vinculo desde otra operacion -------------------------------------
        //-------------------------------------------------------------------------------------
        
        /*
         * Se ejecuta una sola vez cuando hacemos click en la operacion.
         */
        function ini__operacion() {
            $datos=toba::memoria()->get_parametros();
            
            //Guardamos el ID_ del responsable de aula para no dejar nullo el campo id_responsable de la tabla 
            //solicitud, esto puede ocurrir si decidimos no editar al responsable de aula. Caso contrario se 
            //actualiza a partir lo seleccionado.
            $this->s__id_responsable=$datos['id_responsable'];
            if(count($datos)>0){
                
                switch ($datos['estado']){
                //Si la solicitud esta en estado finalizada solamente podemos editar docente y finalidad.
                case 'FINALIZADA' :
                    //Este atributo se setea en cada rama porque en sesion existe array([tm]=>1), lo que hace
                    //que se ejecute una rama en el conf__cuadro donde se hace una consulta sql con un atributo
                    //null. Como consecuencia se genera un error.
                    $this->s__datos_solicitud=$datos;
                    $this->s__tipo=  strtolower($datos['tipo']);
                    $this->set_pantalla('pant_edicion');
                    break;
                
                case 'PENDIENTE' :
                    //Si la edicion es parcial, debemos navegar hasta la pantalla pant_edicion. En este caso
                    //editamos finalidad y tipo de responsable.
                    switch($datos['tipo_edicion']){
                        
                        case 'edicion_parcial' : $this->s__datos_solicitud=$datos;
                                                 $this->s__tipo=  strtolower($datos['tipo']);
                                                 $this->set_pantalla('pant_edicion');
                                                 break;
                        //Si la edicion es total, nos quedamos en la pantalla pant_reserva. En este caso practicamente
                        //creamos una nueva solicitud reutilizando el id_ de la solicitud que se quiere modificar.
                        //El proceso de reserva de aula debe continuar de la misma manera. Pero podemos mantener 
                        //algunos datos como el responsable de aula, la finalidad etc.
                        case 'edicion_total' : $this->s__datos_solicitud=$datos;
                                               $this->s__tipo=  strtolower($datos['tipo']);
                                               break;
                                           
                        default : $this->s__datos_solicitud=array();
                    
                    }
                    break;
                
            }
            }
        }
        
        //-------------------------------------------------------------------------------------
        //---- Pant Busqueda ------------------------------------------------------------------
        //-------------------------------------------------------------------------------------
        
        function conf__pant_reserva (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab('pant_edicion')->desactivar();
        }
        
	//---- Form Ingreso -------------------------------------------------------------------
               
        function evt__form_ingreso__aceptar ($datos){
            $fecha_actual=date('Y-m-d');
            $fecha=$datos['fecha'];
            if($fecha < $fecha_actual){
               $fecha_=  date('d-m-Y', strtotime($fecha_actual));
               $mensaje="No se puede seleccionar una fecha menor a $fecha_";
               toba::notificacion()->agregar(utf8_decode($mensaje), 'info'); 
               return ; 
            }
            
            $mensaje="";
            $this->s__fecha_consulta=$datos['fecha'];
            switch($this->analizar_seleccion($datos, &$mensaje)){
                /* Mas alla del tipo de solicitud siempre calculamos los horarios disponibles a partir
                   del campo fecha. */
                
                case 1  :  //La solicitud se etiqueta como multi.
                case 2  :  //La solicitud se etiqueta como unico.
                           
                           //Para no acumular resultados de busquedas sucesivas.
                           $this->s__horarios_disponibles=array();
                           $this->s__hd_global=array();
                           $this->s__id_sede=$datos['sede'];
                           $ua=$this->dep('datos')->tabla('unidad_academica')->get_unidad_academica($this->s__id_sede);
                           //Para implementar cortes de control en 'cuadro' de la pantalla pant_reserva. Estos eltos. se
                           //agregan al arreglo horarios_disponibles.
                           $this->s__establecimiento=$ua[0]['establecimiento'];
                           $this->s__sede=$ua[0]['sede'];
                           //Esta variable se utiliza para mostar un mensaje de notificacion cuando el usuario
                           //realiza busquedas acotadas, especificando en el formulario establecimiento, sede y fecha.
                           $this->s__solo_fecha=FALSE;
                           $this->calcular_horarios_disponibles_por_facultad();

                           //Se utiliza para realizar busquedas por capacidad, hora_inicio y hora_fin. Se pueden agregar nuevas
                           //opciones de busqueda modificando la clase Filtro.
                           $this->s__filtro=new Filtro($this->s__horarios_disponibles);
                           break;
                case 3  : 
                case 4  :
                case 5  :
                case 6  :
                case 7  :
                case 8  :
                case 9  :
                case 10 :
                case 11 :
                case 12 :
                case 14 :
                case 15 : toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                          break;
                    
                case 13 : //La solicitud se etiqueta como multi.
                case 16 : //La solicitud se etiqueta como unico.
                          //Para no acumular resultados de busquedas sucesivas.
                          $this->s__hd_global=array();
                          //Esta variable se utiliza para mostar un mensaje de notificacion cuando el usuario
                          //realiza busquedas acotadas, especificando en el formulario establecimiento, sede y fecha.
                          $this->s__solo_fecha=TRUE;
                          //Disparamos calculo de horarios disponibles global, para todas la unidades academicas 
                          //registradas en el sistema.
                          $this->calcular_horarios_disponibles_global();

                          //Se utiliza para realizar busquedas por capacidad, hora_inicio y hora_fin. Se pueden agregar nuevas
                          //opciones de busqueda modificando la clase Filtro.
                          $this->s__filtro=new Filtro($this->s__hd_global);
                          
                          break;
            }                      
            
        }
        
        /*
         * Esta funcion analiza los datos que puede seleccionar el usuario, teniendo en cuenta que pueden haber
         * 2^4 combinaciones. La idea de la funcion es poder mostrar un mensaje significativo al usuario en el 
         * caso de elegir mal los datos. Esta operacion se penso en base a una tabla de verdad.
         * @$mensaje: es una variable vacia que se pasa por referencia. Se configura dentro de cada rama.
         */
        function analizar_seleccion ($datos, $mensaje){
            //Analizamos el caso de ediciones totales de solicitudes multi evento.
            if(strcmp($this->s__datos_solicitud['tipo'], "multi")==0){
                if(!isset($datos['fecha_fin']) && count($datos['dias'])==0){
                    $mensaje="Para realizar una edición total de una solicitud multi evento debe elegir "
                            . "fecha_fin y una lista de días. ";
                }
            }
            
            if(isset($datos['sede']) && isset($datos['facultad']) && isset($datos['fecha_fin']) && count($datos['dias'])!=0){
                //Multi.
                $this->s__tipo="multi";
                $this->s__datos_multi=array($datos['fecha_fin'], $datos['dias']);
                return 1;
            }
            if(isset($datos['sede']) && isset($datos['facultad']) && !isset($datos['fecha_fin']) && count($datos['dias'])==0){
                //Unico.
                $this->s__tipo="unico";
                return 2;
            }
            if(isset($datos['sede']) && isset($datos['facultad']) && isset($datos['fecha_fin']) && count($datos['dias'])==0){
                $mensaje="Si elige sede, establecimiento y fecha_fin, también debe elegir dias.";
                return 3;
            }
            if(isset($datos['sede']) && isset($datos['facultad']) && !isset($datos['fecha_fin']) && count($datos['dias'])!=0){
                $mensaje="Si elige sede, establecimiento y días, también debe elegir fecha fin";
                return 4;
            }

            //Los casos de 5 a 8 no tiene sentido analizarlos. Esta conclusion se infiere a partie de una tabla 
            //de verdad.
            
            if(!isset($datos['sede']) && isset($datos['facultad']) && isset($datos['fecha_fin']) && count($datos['dias'])!=0){
                $mensaje="Si elige establecimiento, fecha_fin y días, también debe elegir sede, "
                        . "es útil para acotar la búsqueda que desea realizar en el sistema";
                return 9;
            }
            if(!isset($datos['sede']) && isset($datos['facultad']) && isset($datos['fecha_fin']) && count($datos['dias'])==0){
                $mensaje="Si elige establecimiento y fecha fin, también debe elegir sede y días.";
                return 10;
            }
            if(!isset($datos['sede']) && isset($datos['facultad']) && !isset($datos['fecha_fin']) && count($datos['dias'])!=0){
                $mensaje="Si elige establecimiento y una lista de días, también debe elegir sede y fecha fin. "
                        . "El sistema considera que desea acotar la búsqueda paea una solicitud multi-evento.";
                return 11;
            }
            if(!isset($datos['sede']) && isset($datos['facultad']) && !isset($datos['fecha_fin']) && count($datos['dias'])==0){
                $mensaje="Si elige facultad, también debe elegir sede, para acotar la búsqueda en un "
                        . "establecimiento específico.";
                return 12;
            }
            if(!isset($datos['sede']) && !isset($datos['facultad']) && isset($datos['fecha_fin']) && count($datos['dias'])!=0){
                //Multi.
                $this->s__tipo="multi";
                $this->s__datos_multi=array($datos['fecha_fin'], $datos['dias']);
                return 13;
            }
            if(!isset($datos['sede']) && !isset($datos['facultad']) && isset($datos['fecha_fin']) && count($datos['dias'])==0){
                $mensaje="Si eleige solamente fecha fin, el sistema no puede realizar una búsqueda exhaustiva de"
                        . " horarios disponibles. ";
                return 14;
            }
            if(!isset($datos['sede']) && !isset($datos['facultad']) && !isset($datos['fecha_fin']) && count($datos['dias'])!=0){
                $mensaje="El sistema no puede calcular horarios disponibles a partir de una lista de días, debe "
                        . "elegir un periodo comprendido por una fecha de inicio y fin. ";
                return 15;
            }
            if(!isset($datos['sede']) && !isset($datos['facultad']) && !isset($datos['fecha_fin']) && count($datos['dias'])==0){
                //Unico.
                $this->s__tipo="unico";
                return 16;
            }
        }
        
        function calcular_horarios_disponibles_global (){
            
            //Obtenemos todas las unidades academicas registradas en el sistema. 
            $establecimientos=$this->dep('datos')->tabla('unidad_academica')->get_unidades_academicas();
            
            //Recorremos el arreglo obtenido en el paso anterior y configuramos el id_sede
            foreach($establecimientos as $clave=>$valor){
                //Se usa dentro de calcular_horarios_disponibles_por_facultad.
                $this->s__id_sede=$valor['id_sede'];
                //Para implementar los cortes de control en el cuadro de pant_reserva.
                $this->s__establecimiento=$valor['establecimiento'];
                $this->s__sede=$valor['sede'];
                //Iniciamos el calculo de horarios disponibles por cada establecimiento.
                $this->calcular_horarios_disponibles_por_facultad();
                
                //Guardamos en hd_global todos los horarios disponibles de los establecimientos registrados 
                //en el sistema. 
                $this->unificar_asignaciones(&$this->s__hd_global, $this->s__horarios_disponibles);
                //Rseteamos s__horarios_disponibles para no repetir horarios disponibles en hd_global. Ademas
                //es util para colapsar cuadro de pant_reserva.
                $this->s__horarios_disponibles=array();
            }
            
        }
        
        function calcular_horarios_disponibles_por_facultad (){
             
            //Obtenemos todas las aulas de un establecimiento. El id_sede se configura en el metodo llamador.
            $aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($this->s__id_sede);
            
            //Si el establecimiento no tiene aulas registradas en sistema no hacemos nada. Pero los mensajes
            //deben quedar si elegimos establecimiento, sede y fecha.
            if(count($aulas_ua)==0){
                //Si esta condicion es true, en el formulario form_ingreso se eligio* establecimiento, sede y
                //fecha.
                if(!$this->s__solo_fecha){ 
                    $mensaje=" La Unidad Académica seleccionada no posee aulas registradas en el Sistema ";
                    toba::notificacion()->agregar(utf8_decode($mensaje),'info');
                }
            }
            else{
                $anio_lectivo=date('Y', strtotime($this->s__fecha_consulta));
                $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($this->s__fecha_consulta, $anio_lectivo, $this->s__id_sede);
                
                //Si un establecimiento no registro* periodos en el sistema no realizamos ningun 
                //procesamiento.
                if(count($periodo)>0){
                    $this->s__dia_consulta=$this->obtener_dia(date('N', strtotime($this->s__fecha_consulta)));

                    //Obtenemos todas las asignaciones para la fecha seleccionada.
                    $asignaciones=$this->procesar_periodo($periodo, 'hd');

                    //Obtenemos las aulas que poseen asignaciones.
                    $aulas=$this->obtener_aulas($asignaciones);
                    toba::memoria()->set_dato_instancia(0, $this->s__id_sede);
                    $horarios_disponibles=new HorariosDisponibles();
                    //Si un establecimiento tiene registrado en el sistema periodos acade*micos que no tienen
                    //asociadas asignaciones, la disponibilidad debe ser total para todas las aulas 
                    //involucradas. Esto se realiza en la clase HorariosDisponibles cuando el cjto. de 
                    //asignaciones es vacio.
                    $this->s__horarios_disponibles=$horarios_disponibles->calcular_horarios_disponibles($aulas, $aulas_ua, $asignaciones);

                    if(count($this->s__horarios_disponibles)==0){
                        //Si esta condicion es true, en el formulario form_ingreso se eligio* establecimiento, sede
                        //y fecha.
                        if(!$this->s__solo_fecha){
                            $mensaje="La Unidad Académica seleccionada no posee horarios disponibles";
                            toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                        }
                    }
                                    
                    //Agregamos a cada horario disponible el establecimiento, la sede y el id_sede, fundamentales 
                    //para implementar cortes de control en 'cuadro' de la pantalla pant_reserva.
                    $i=0;
                    $n=count($this->s__horarios_disponibles);
                    while($i < $n){
                        $this->s__horarios_disponibles[$i]['establecimiento']=$this->s__establecimiento;
                        $this->s__horarios_disponibles[$i]['sede']=$this->s__sede;
                        $this->s__horarios_disponibles[$i]['id_sede']=$this->s__id_sede;
                        $i++;
                    }
                }
            }
            
        }
        
                
        /*
         * Genera un arreglo con las aulas utilizadas en un dia especifico.
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
        
        function obtener_cuatrimestre (){
            $fecha=  getdate();
            $cuatrimestre=2;
            if(($fecha['mon'])>=1 && ($fecha['mon'])<=6){
                $cuatrimestre=1;
            }
            
            return $cuatrimestre;
        }
        
        /*
         * No tiene sentido realizar un chequeo relacionado con el dia, porque el mismo se obtiene a partir de 
         * un date php, que puede ser Lunes, ......, Domingo. 
         */
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
         * A partir de una fecha devolvemos el nombre del dia 
         */
        function recuperar_dia ($fecha){
            //si usamos w obtenemos 0 para domingo y 6 para sabado
            //si usamos N obtenemos 1 para lunes y 7 para domingo
            $dia_numerico=date('N', strtotime($fecha));
            
            //devolvemos el dia en un arreglo para no modificar la funcion registrar_asignacion_periodo
            return (array($this->obtener_dia($dia_numerico)));
        }
        
        /*
         * A partir de una fecha devolvemos el anio.
         */
        function recuperar_anio ($fecha){
            
            $anio=date('Y', strtotime($fecha));
            
            return $anio;
        }
        
        //---- Filtro -----------------------------------------------------------------------
        
        function conf__filtro (toba_ei_filtro $filtro){
            //La operacion ** permite iniciar un calculo de horarios disponibles individual o global. 
            //Segun la opcion elegida podemos guardar los resultados en s__horarios_disponibles o s__hd_glabal.
            //Debemos colapsar el filtro si ambos arreglos estan vacios. Caso contrario debemos descolapsarlo.
            if(count($this->s__horarios_disponibles)==0 && count($this->s__hd_global)==0){
                $filtro->colapsar();
            }else{
                $filtro->set_titulo(utf8_decode("Opciones de búsqueda"));
                $filtro->descolapsar();
            }
        }
        
        /*
         * Devuelve todos los dias de la semana para cargar el combo tipo 
         */
        function dias_semana (){            
            $sql="SELECT nombre FROM dia ORDER BY orden";
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Las opciones de busqueda se agrupan a traves de un || logico. De esta manera se eliminan resultados
         * repetidos.
         */
        function evt__filtro__filtrar ($datos){
            $this->s__datos_filtrados=$this->s__filtro->filtrar($datos);
        }
        
        /*
         * El objetivo de esta funcion es restaurar el cuadro de horarios disponibles, con el contenido del
         * arreglo s__horarios_disponibles.
         */
        function evt__filtro__limpiar (){
            $this->s__datos_filtrados=array();
        }
                        
        //---- Cuadro -----------------------------------------------------------------------
        
        function conf__cuadro (toba_ei_cuadro $cuadro){
            $fecha=date('d-m-Y', strtotime($this->s__fecha_consulta));
            //--Finalizamos la ejecucion en cada caso. Asi evitamos hacer comparaciones sin sentido.
            if(count($this->s__datos_filtrados)>0){
                $descripcion=  strtoupper("Horarios disponibles para el día {$this->obtener_dia(date('N', strtotime($this->s__fecha_consulta)))} $fecha");
                $cuadro->set_titulo(utf8_decode($descripcion));
                $cuadro->set_datos($this->s__datos_filtrados);
                return ; 
            }
            if(count($this->s__horarios_disponibles)==0 && count($this->s__hd_global)==0){
                $cuadro->colapsar();
                return ;
            }
            if(count($this->s__horarios_disponibles)>0 && count($this->s__hd_global)==0){
                $cuadro->descolapsar();
                //$fecha=date('d-m-Y', strtotime($this->s__fecha_consulta));
                $descripcion=  strtoupper("Horarios disponibles para el día {$this->obtener_dia(date('N', strtotime($this->s__fecha_consulta)))} $fecha");
                $cuadro->set_titulo(utf8_decode($descripcion));
                $cuadro->set_datos($this->s__horarios_disponibles);
                return ;
            }
            if(count($this->s__horarios_disponibles)==0 && count($this->s__hd_global)>0){
                $cuadro->descolapsar();
                //$fecha=date('d-m-Y', strtotime($this->s__fecha_consulta));
                $descripcion=  strtoupper("Horarios disponibles para el día {$this->obtener_dia(date('N', strtotime($this->s__fecha_consulta)))} $fecha");
                $cuadro->set_titulo(utf8_decode($descripcion));
                $cuadro->set_datos($this->s__hd_global);
                return ; //Lo dejamos igual.
            }
            
        }
        
        function evt__cuadro__seleccionar ($datos){
            
            //Es necesario usar strtotime para no generar conflictos entre fechas.
            $datos['fecha']=date('d-m-Y', strtotime($this->s__fecha_consulta));
            //Obtenemos la sede a la que pertenece el usuario logueado. Es quien realiza el pedido de 
            //aula. Estas sentencias se deben cambiar cuando existan perfiles de datos.
            $this->s__id_sede_origen=$this->dep('datos')->tabla('sede')->get_id_sede();
                        
            $ua=$this->dep('datos')->tabla('unidad_academica')->get_unidad_academica($this->s__id_sede_origen);
            $datos['facultad']=$ua[0]['establecimiento'];
            $this->s__sigla_origen=$ua[0]['sigla'];
            //Guardamos el id_sede_origen para poder obtener las solicitudes realizadas por un usuario, esto 
            //permite editar o eliminar solicitudes. Los pedidos de aula son realizados unicamente por el 
            //responsable de aulas de cada establecimiento.
            //$this->s__id_sede_origen=$id_sede;
            
            //El establecimiento_destino solamente se debe mostrar en el formulario para saber a quien le estamos
            //haciendo un pedido de aula. En la tabla solicitud debemos registrar quien realiza el pedido, en este 
            //caso la SIGLA del campo facultad.
            //La informacion que se guarda en datos_cuadro (id_aula, hora_inicio, hora_fin, capacidad, 
            //establecimiento, id_sede) sirve para:
            //a) cargar en la solicitud el id_aula e id_sede seleccionados.
            //b) cargar 'formulario' con datos por defecto.
            $this->s__datos_cuadro=$datos;
            
            $this->set_pantalla("pant_edicion");
        }
        
        function evt__volver (){
            $this->set_pantalla("pant_reserva");
        }
        
        //-----------------------------------------------------------------------------------
        //---- Pant Edicion -----------------------------------------------------------------
        //-----------------------------------------------------------------------------------
        
        function conf__pant_edicion (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab('pant_reserva')->desactivar();
        }
        
	//---- Formulario -------------------------------------------------------------------

        /*
         * En esta funcion agregamos la logica necesaria para cargar el formulario con datos por defecto.
         */
	function conf__formulario(toba_ei_formulario $form)
	{   
            //Esta variable se carga con valores cuando queremos hacer ediciones.
            if(count($this->s__datos_solicitud)==0){
                /** Cargamos informacion en la seccion 'datos iniciales'. **/
                $form->ef('fecha_seleccionada')->set_estado(date('d-m-Y', strtotime($this->s__fecha_consulta)));
                $form->ef('dia')->set_estado(utf8_decode($this->obtener_dia(date('N', strtotime($this->s__fecha_consulta)))));
                $form->ef('inicio')->set_estado($this->s__datos_cuadro['hora_inicio']);
                $form->ef('fin')->set_estado($this->s__datos_cuadro['hora_fin']);
                                
                //Cargamos informacion por defecto en formulario de la pantalla pant_edicion.
                $form->ef('establecimiento')->set_estado($this->s__datos_form['facultad']);
                $form->set_datos($this->s__datos_cuadro);
                
                if(strcmp($this->s__tipo, "multi")==0){
                    $form->ef('fecha_fin')->set_estado(date('d-m-Y', strtotime($this->s__datos_multi[0])));
                    $form->ef('dias')->set_estado($this->s__datos_multi[1]);
                    $form->ef('dias')->set_solo_lectura();
                }else{
                    $form->desactivar_efs(array('dias', 'fecha_fin'));
                }
                
                $this->s__accion="registrar";
            }else{
                //Si tenemos eltos. en s__datos_solicitud es porque queremos hacer una edicion.
                $form->ef('dias')->set_solo_lectura();
                //Cargamos datos por defecto para editar solicitudes.
                switch($this->s__datos_solicitud['tipo_edicion']){
                    
                    case 'edicion_total'   : $this->configurar_datos_solicitud();
                                                                             
                                             
                    case 'edicion_parcial' : 
                                             /** Cargamos informacion en la seccion 'datos iniciales'. **/
                                             $form->ef('fecha_seleccionada')->set_estado(date('d-m-Y', strtotime($this->s__datos_solicitud['fecha'])));
                                             $form->ef('dia')->set_estado(utf8_decode($this->obtener_dia(date('N', strtotime($this->s__datos_solicitud)))));
                                             //Usamos la hora que especifico el usuario en la solicitud.
                                             $form->ef('inicio')->set_estado($this->s__datos_solicitud['hora_inicio']);
                                             $form->ef('fin')->set_estado($this->s__datos_solicitud['hora_fin']);
                                             
                                             /** Agregamos datos relacionados a un multi-evento **/
                                             if(strcmp($this->s__tipo, "multi")==0 && strcmp($this->s__datos_solicitud['tipo_edicion'], "edicion_parcial")==0){
                                                $dias=$this->dep('datos')->tabla('solicitud')->get_lista_dias($this->s__datos_solicitud['id_solicitud']);
                                                $fecha_fin=$this->dep('datos')->tabla('solicitud')->get_datos_multi($this->s__datos_solicitud['id_solicitud']);
                                                
                                                $this->s__datos_solicitud['fecha_fin']=$fecha_fin;
                                                $this->s__datos_solicitud['dias']=$this->obtener_dias_seleccionados($dias);
                                             }
                                             
                                             /** Configuramos los establecimientos origen y destino. **/
                                             $establecimiento_destino=$this->dep('datos')->tabla('unidad_academica')->get_unidad_academica($this->s__datos_solicitud['id_sede']);
                                             $establecimiento_origen=$this->dep('datos')->tabla('unidad_academica')->get_unidad_academica($this->s__datos_solicitud['id_sede_origen']);
                                                                                          
                                             //Guardamos el establecimiento que emite la solicitud (origen).
                                             $this->s__datos_solicitud['facultad']=$establecimiento_origen[0]['establecimiento'];
                                             //Guardamos el establecimiento que recibe la solicitud (destino).
                                             $this->s__datos_solicitud['establecimiento']=$establecimiento_destino[0]['establecimiento'];
                                             
                                             $form->ef('tipo_asignacion')->set_estado($this->s__datos_solicitud['tipo_asignacion']);
                                             //Cargamos la informacion que viene desde la operacion 'ver solicitudes'.
                                             $form->set_datos($this->s__datos_solicitud);
                                             /** Configuramos la seccion 'datos del solicitante'. **/
                                             switch($this->s__datos_solicitud['tipo_agente']){
                                                 //Aqui vamos a cargar los datos por separado para que no se rompa nada.
                                                 case 'Docente'      : $docente=$this->dep('datos')->tabla('persona')->get_datos_docente($this->s__datos_solicitud['id_responsable']);
                                                                       $nombre=$docente[0]['nombre'];
                                                                       
                                                                       $form->ef('legajo')->set_estado($docente[0]['legajo']);
                                                                       $form->ef('nombre')->set_estado($nombre);
                                                                       $form->ef('apellido')->set_estado($docente[0]['apellido']);
                                                                       $form->ef('tipo_agente')->set_estado('Docente');
                                                                       break;
                                                                   
                                                 case 'Organizacion' : $organizacion=$this->dep('datos')->tabla('organizacion')->get_organizacion($this->s__datos_solicitud['id_responsable']);
                                                                       
                                                                       $form->ef('tipo_agente')->set_estado('Organizacion');
                                                                       $form->ef('org')->set_estado($organizacion[0]['id_organizacion']);
                                                                       $form->ef('nombre_org')->set_estado($organizacion[0]['nombre']);
                                                                       $form->ef('telefono_org')->set_estado($organizacion[0]['telefono']);
                                                                       $form->ef('email_org')->set_estado($organizacion[0]['email']);
                                                                       break;
                                                                   
                                                 default : print_r("Ocurre un problema con el tipo_agente");
                                             }
                                                             
                                             //Si la solicitud esta en estado pendiente y la edicion esparcial no debemos permitir que se editen 
                                             //hora_inicio y hora_fin. Estos atributos se pueden editar en estado pendiente ejecutando nuevamente 
                                             //la operacion 'solicitar aula'.
                                             if(strcmp($this->s__datos_solicitud['tipo_edicion'], 'edicion_parcial')==0){
                                                $form->set_solo_lectura(array('hora_inicio', 'hora_fin'));
                                                $this->s__accion="edicion_parcial";
                                                
                                             }else{//Por las dudas guardamos edicion_total en accion;
                                                 $this->s__accion="edicion_total";
                                             }                                                                                                                                
                                             
                                             break;
                                         

                }
                
                
            }
            
	}
        
        function obtener_dias_seleccionados ($lista_dias){
            $dias=array();
            foreach ($lista_dias as $key=>$dia){
                $dias[]=$dia['nombre'];
            }
            
            return $dias;
        }
        
        //-------------------------------------------------------------------------------------
        //---- Funciones para autocompletar datos relacionados a docentes u organizaciones ----
        //---- en 'formulario' de la pantalla pant_edcicion -----------------------------------
        //-------------------------------------------------------------------------------------
        
        /*
         * @id_organizacion o @legajo : es el dato que transferimos desde el cliente. Como usamos la funcion 
         * ajax_cadenas los valores viene como string y el legajo o id_organizacion son atributos integer 
         * por lo tanto el gestor de bd reporta un error de compatibilidad de tipos. Debemos hacer una conversion 
         * de datos, para ello usamos la funcion intval().
         */
        function ajax__autocompletar_org ($id_organizacion, toba_ajax_respuesta $respuesta){
            if(strcmp($id_organizacion, '')!=0){
            $this->s__id_responsable=  intval($id_organizacion);
            //Nos tomamos una licencia para hacer una consulta sql fuera del datos_tabla correspondiente.
            $sql="SELECT *
                  FROM organizacion 
                  WHERE id_organizacion={$this->s__id_responsable}";
            $datos_org=toba::db('rukaja')->consultar($sql);
            
            if(count($datos_org) != 0){
                $respuesta->agregar_cadena('agente', 'organizacion');
                $respuesta->agregar_cadena('nombre', $datos_org[0]['nombre']);
                $respuesta->agregar_cadena('telefono', $datos_org[0]['telefono']);
                $respuesta->agregar_cadena('email', $datos_org[0]['email']);
            }else{
                toba::notificacion()->agregar_mensaje("Sin Respuesta", 'info');
            }
            }else{
                //Enviando un valor cualquiera evitamos que la ejecucion de dos llamadas ajax consecutivas
                //eliminen el id del responsable de aula.
                $respuesta->agregar_cadena('agente', '2');
            }
        }
        
        
        function ajax__autocompletar_form ($legajo, toba_ajax_respuesta $respuesta){
            if(strcmp($legajo, '')!=0){
            $this->s__id_responsable=  intval($legajo);
            //Nos tomamos una licencia para hacer una consulta sql fuera del datos_tabla correspondiente.
            $sql="SELECT nombre,
                         apellido
                  FROM docente 
                  WHERE legajo={$this->s__id_responsable}";
            $datos_docente=toba::db('mocovi')->consultar($sql);
            
            $contenido=$this->dep('formulario')->ef('legajo')->get_estado();
            
            if(count($datos_docente) != 0){
                
                $respuesta->agregar_cadena('agente', 'docente');
                $respuesta->agregar_cadena('nombre', $datos_docente[0]['nombre']);
                $respuesta->agregar_cadena('apellido', $datos_docente[0]['apellido']);
            }
            }else{
                //Enviando un valor cualquiera evitamos que la ejecucion de dos llamadas ajax consecutivas
                //eliminen el id del responsable de aula.
                $respuesta->agregar_cadena('agente', '2');
            }          
            
        }
        
        /*
         * Esta funcion permite mezclar el contenido de s__datos_cuadro con s__datos_solicitud. Esto se utiliza
         * cuando queremos editar solicitudes en forma total. La informacion que debemos pasar es: id_aula, aula,
         * id_sede, establecimiento destino.
         */
        function configurar_datos_solicitud (){
                        
            $this->s__datos_solicitud['id_aula']=$this->s__datos_cuadro['id_aula'];
            $this->s__datos_solicitud['aula']=$this->s__datos_cuadro['aula'];
            $this->s__datos_solicitud['id_sede']=$this->s__datos_cuadro['id_sede'];
            $this->s__datos_solicitud['facultad']=$this->s__datos_cuadro['facultad'];
            $this->s__datos_solicitud['hora_inicio']=$this->s__datos_cuadro['hora_inicio'];
            $this->s__datos_solicitud['hora_fin']=$this->s__datos_cuadro['hora_fin'];
            $this->s__datos_solicitud['fecha']=$this->s__datos_cuadro['fecha'];
            $this->s__datos_solicitud['capacidad']=$this->s__datos_cuadro['capacidad'];
            
            //Si la solicitud editada es total y multi. 
            if(strcmp($this->s__tipo, "multi")==0 && count($this->s__datos_multi)>0){
                $this->s__datos_solicitud['fecha_fin']=date('d-m-Y', strtotime($this->s__datos_multi[0]));
                $this->s__datos_solicitud['dias']=$this->s__datos_multi[1];
            }
        }
        
        //------------------------------------------------------------------------------------------
        //------------------------------------------------------------------------------------------

	function evt__formulario__alta($datos)
	{
            //Si el usuario especifica otro tipo de asignacion debemos guardarlo en la base de datos para que este
            //disponible en otro momento.
            if(strcmp('OTRO', $datos['tipo_asignacion'])==0){
                $this->dep('datos')->tabla('tipo_asignacion')->nueva_fila(array('tipo'=>  strtoupper($datos['tipo_nombre'])));
                $this->dep('datos')->tabla('tipo_asignacion')->sincronizar();
                $this->dep('datos')->tabla('tipo_asignacion')->resetear();
            }
            
            //Registramos una nueva organizacion en el sistema.
            if(strcmp('Organizacion', $datos['tipo_agente'])==0){
                //Si no iniciamos una busqueda por popup de la organizacion en s__id_responsable se guarda una 
                //cadena vacia, caso contrario se guarda un numero como string. Si tenemos una cadena vacia
                //y convertimos el string en entero obtenemos un cero como resultado. El id_organizacion es un 
                //atributo de tipo serial y el rango de valores de este tipo de dato comienza en 1.                
                $id=  intval($this->s__id_responsable);
                                            
                //Verificamos si la organizacion ya existe en el sistema.               
                if($id != 0){ //Si la organizacion no existe, la registramos en el sistema.
                    $organizacion=$this->dep('datos')->tabla('organizacion')->get_organizacion($id);
                                        
                    if(count($organizacion)==0){
                        $organizacion=array(
                            'telefono' => $datos['telefono_org'],
                            'email' => strtolower($datos['email_org']),
                            'nombre' => strtoupper($datos['nombre_org']),
                        );

                        $this->dep('datos')->tabla('organizacion')->nueva_fila($organizacion);
                        $this->dep('datos')->tabla('organizacion')->sincronizar();
                        $this->dep('datos')->tabla('organizacion')->resetear();

                        //Necesitamos el id_organizacion de la nueva tupla anteriormente insertada, para poder hacer
                        //la asociacion correcta entre la solicitud y el responsable de la misma. Este atributo se utiliza
                        //en la funcion registrar_solicitud. Solamente se puede hacer uso de la secuencia cuando
                        //insertamos una nueva tupla en una relacion, caso contrario ocurre un error.
                        //Si insertamos tuplas en una relacion a traves de un script sql, con sentencias insert into, 
                        //la secuencia no se resetea, debemos hacerlo manualmente desde postgres con la siguiente 
                        //sentencia select setval('tabla_id_seq', nuevo_valor, 't' )
                        $this->s__id_responsable=  recuperar_secuencia('organizacion_id_organizacion_seq');
                    }//Si la organizacion ya existe usamos el contenido de s__id_responsable.
                    
                }else{
                    //Si el usuario edita una solicitud, puede ocurrir que no realice una busqueda por popup, entonces
                    //el id_responsable queda con una cadena vacia. Pero si hace una busqueda en id_responsable queda
                    //un valor concreto. Debemos guardar en id_responsable el id de datos_solicitud.
                    $this->s__id_responsable=$this->s__datos_solicitud['id_responsable'];
                }
            }
            
            //Anteriormente se hacia un chequeo de horarios, ya no es necesario, porque se hace en el cliente.
            
            switch($this->s__accion){
                case 'registrar'        : $this->registrar_solicitud($datos);
                                          break;
                //En ambos casos hacemos lo mismo.
                case 'edicion_parcial'  : 
                case 'edicion_total'    : $this->edicion($datos);
                                          toba::vinculador()->navegar_a('rukaja', 3573);
                                          break;
            }            
                        
	}
        
        /*
         * Esta funcion guarda en la base de datos una solicitud editada. 
         * @$datos : contiene los datos de 'formulario'. Esta informacion se toma tal cual para hacer la 
         * modificacion correspondiente. Si la edicion es total podemos modificar fecha, hora_inicio, hora_fin,
         * aula entre otros datos. Si la edicion es parcial podemos editar datos relacionados al responsable de 
         * aula, la finalidad etc. 
         */
        function edicion ($datos){
            try{ //Capturamos excepciones generadas por los objetos datos_tabla.
                                
                $nombre=(strcmp($datos['tipo_agente'], 'Docente')==0) ? $datos['nombre'].' '.$datos['apellido'] : $datos['nombre_org'] ;
                
                $establecimiento=$this->dep('datos')->tabla('unidad_academica')->get_unidad_academica($this->s__datos_solicitud['id_sede_origen']);
                switch($this->s__accion){
                    case 'edicion_total'  :  //En este caso podemos editar la fecha, hora_inicio, hora_fin, 
                                             //establecimiento destino, como datos mas importantes. No es 100%
                                             //confiable usar la variable $datos, ya que la llamada ajax de los 
                                             //popup se ejecuta dos veces y borra el contenido de los campos 
                                             //asociados al responsable de aulas.
                                                                                          
                                             if(strcmp($this->s__tipo, "multi")==0){
                                                 
                                                 //Eliminamos la solicitud actual y la volvemos a registrar,
                                                 //haremos uso del datos_tabla para borrarla de la base de datos.
                                                 try{
                                                     $this->dep('datos')->tabla('solicitud')->cargar(array('id_solicitud'=>$this->s__datos_solicitud['id_solicitud']));
                                                     $this->dep('datos')->tabla('solicitud')->eliminar_todo();
                                                     $this->dep('datos')->tabla('solicitud')->sincronizar();
                                                     $this->dep('datos')->tabla('solicitud')->resetear();
                                                 }catch(toba_error $ex){
                                                     //Capturamos la excepcion generada.
                                                 }
                                                 $this->editar_solicitud_multi_evento($datos);
                                                                                                  
                                             }else{//Edicion total sobre solicitud unica.
                                                 $solicitud=array(
                                                    'id_solicitud' => $this->s__datos_solicitud['id_solicitud'],
                                                    'nombre' => $nombre,
                                                    'fecha' => $datos['fecha'],
                                                    'capacidad' => $datos['capacidad'],
                                                    'finalidad' => $datos['finalidad'],
                                                    'hora_inicio' => $datos['hora_inicio'],
                                                    'hora_fin' => $datos['hora_fin'],
                                                    'id_sede' => $this->s__datos_cuadro['id_sede'],
                                                    'estado' => $this->s__datos_solicitud['estado'],
                                                    'id_aula' => $this->s__datos_cuadro['id_aula'],
                                                    'facultad' => $this->s__sigla_origen,
                                                    'tipo_agente' => $datos['tipo_agente'],
                                                    'id_responsable' => $this->s__id_responsable,
                                                    'tipo_asignacion' => $datos['tipo_asignacion'],
                                                    'id_sede_origen' => $this->s__id_sede_origen
                                                  );
                                                 
                                                 $this->dep('datos')->tabla('solicitud')->cargar(array('id_solicitud'=>$this->s__datos_solicitud['id_solicitud']));
                                                 $this->dep('datos')->tabla('solicitud')->set($solicitud);
                                                 $this->dep('datos')->tabla('solicitud')->sincronizar();
                                                 $this->dep('datos')->tabla('solicitud')->resetear();
                                                 
                                             }
                        
                                              break;
                                          
                    case 'edicion_parcial' :  //En este caso podemos editar datos del responsable de aula y finalidad.
                                              //Por lo tanto es conveniente reutilizar la informacion proporcionada
                                              //por el arreglo s__datos_solicitud.
                                              $solicitud=array(
                                                'id_solicitud' => $this->s__datos_solicitud['id_solicitud'],
                                                'nombre' => $nombre,
                                                'fecha' => $datos['fecha'],
                                                'capacidad' => $datos['capacidad'],
                                                'finalidad' => $datos['finalidad'],
                                                'hora_inicio' => $datos['hora_inicio'],
                                                'hora_fin' => $datos['hora_fin'],
                                                'id_sede' => $this->s__datos_solicitud['id_sede'],
                                                'estado' => $this->s__datos_solicitud['estado'],
                                                'id_aula' => $this->s__datos_solicitud['id_aula'],
                                                'facultad' => $establecimiento[0]['sigla'],
                                                'tipo_agente' => $datos['tipo_agente'],
                                                'id_responsable' => $this->s__id_responsable,
                                                'tipo_asignacion' => $datos['tipo_asignacion'],
                                                'id_sede_origen' => $this->s__datos_solicitud['id_sede_origen']
                                              );
                                                                                            
                                              $this->dep('datos')->tabla('solicitud')->cargar(array('id_solicitud'=>$this->s__datos_solicitud['id_solicitud']));
                                              $this->dep('datos')->tabla('solicitud')->set($solicitud);
                                              $this->dep('datos')->tabla('solicitud')->sincronizar();
                                              $this->dep('datos')->tabla('solicitud')->resetear();
                                              
                                              //Si la solicitud es multi-evento debemos editar la fecha_fin y la
                                              //lista de dias. Lo mas conveniente es borrar la solicitud y volverla a cargar, 
                                              //el problema surge con la lista de dias del multivento y las buledeces del datos_tabla
                                              //al momento de cargarlo con multiples registros.
                                                                                                                                                                                        
                                              break;
                }
                                
            }catch (toba_error $ex) {
               //Capturamos la excepcion generada por la interfaz datos_tabla. 
            }
        }
        
        function editar_solicitud_multi_evento($datos){
            //Guardamos en nombre la cambinacion nombre-apellido para un docente o el nombre completo de una 
            //organizacion.
            $nombre=(strcmp($datos['tipo_agente'], 'Docente')==0) ? strtoupper(($datos['nombre'])." ".($datos['apellido'])) : (strtoupper($datos['nombre_org'])) ;
            $id_sede=$this->s__datos_cuadro['id_sede'];
            //Fecha de solicitud.
            $fecha= date('d-m-Y', strtotime($this->s__fecha_consulta));
            
            $solicitud=array(
                'nombre' => $nombre,
                'fecha' => $fecha,
                'capacidad' => $datos['capacidad'],
                'finalidad' => $datos['finalidad'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'], 
                'id_sede' => $id_sede,   //Guardamos el id_sede del establecimeinto al que le hacemos el pedido de aula.
                'estado' => $this->s__datos_solicitud['estado'], //Conservamos el estado que tiene la solicitud que queremos editar.
                'id_responsable' => intval($this->s__id_responsable),
                'tipo_agente' => $datos['tipo_agente'],
                'tipo' => strtoupper($this->s__tipo),  //Puede ser unico o multi.
                'tipo_asignacion' => $datos['tipo_asignacion'],
                'id_sede_origen' => $this->s__id_sede_origen, //Guardamos el id_sede del establecimeinto que realiza el pedido de aula.
                'id_aula' => $this->s__datos_cuadro['id_aula'],
                'facultad' => $this->s__sigla_origen        //Especificamos la sigla del establecimiento que realiza un pedido de aula. En la tabla solicitud
                                                            //el campo para este dato es character varying (6).
            );
            
            //Registramos la solicitud en la base de datos.
            $this->dep('datos')->tabla('solicitud')->nueva_fila($solicitud);
            $this->dep('datos')->tabla('solicitud')->sincronizar();
            $this->dep('datos')->tabla('solicitud')->resetear();
            
            $secuencia=  recuperar_secuencia('solicitud_id_solicitud_seq');
            $solicitud_multi_evento=array(
                'id_solicitud' => $secuencia, 
                'fecha_fin' => $this->s__datos_multi[0]
            );

            $this->dep('datos')->tabla('solicitud_multi_evento')->nueva_fila($solicitud_multi_evento);
            $this->dep('datos')->tabla('solicitud_multi_evento')->sincronizar();
            $this->dep('datos')->tabla('solicitud_multi_evento')->resetear();

            $hd=new HorariosDisponibles();
            $lista_fechas=$hd->get_dias($fecha, $this->s__datos_multi[0], $this->s__datos_multi[1]);
            
            foreach($lista_fechas as $clave=>$fecha){
                $multi_evento=array(
                    'id_solicitud' => $secuencia,
                    'fecha' => date('Y-m-d', strtotime($fecha)),
                    'nombre' => utf8_decode($this->obtener_dia(date('N', strtotime($fecha))))
                );

                $this->dep('datos')->tabla('multi_evento')->nueva_fila($multi_evento);
                $this->dep('datos')->tabla('multi_evento')->sincronizar();
                $this->dep('datos')->tabla('multi_evento')->resetear();
            }
        }
        
        /*
         * Registramos una solicitud de aula. Agregamos la logica necesaria teniendo en cuenta los tipos de 
         * solicitantes.
         * 
         * No es responsabilidad de registrar_solicitud enviar un email de notificacion, esto impide reutilizar
         * completamente esta funcion para editar multi_eventos. Cambio a futuro.
         */
        function registrar_solicitud ($datos){
            //Guardamos en nombre la cambinacion nombre-apellido para un docente o el nombre completo de una 
            //organizacion.
            $nombre=(strcmp($datos['tipo_agente'], 'Docente')==0) ? strtoupper(($datos['nombre'])." ".($datos['apellido'])) : (strtoupper($datos['nombre_org'])) ;
                        
            //Fecha de solicitud.
            $fecha= date('d-m-Y', strtotime($this->s__fecha_consulta));           
            
            //Guardamos el id_sede del establecimiento al que le hacemos un pedido de aula.
            $id_sede=$this->s__datos_cuadro['id_sede'];
            //$tipo=  gettype($this->s__id_responsable);
            
            $descripcion="$nombre ha registrado una SOLICITUD de aula para el dia $fecha, en su Establecimiento. ";

            $asunto="SOLICITUD DE AULA";
            
            //Depuramos el arreglo $datos utilizando lo estrictamente necesario para registrar una solicitud.
            $solicitud=array(
                'nombre' => $nombre,
                'fecha' => $fecha,
                'capacidad' => $datos['capacidad'],
                'finalidad' => $datos['finalidad'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'], 
                'id_sede' => $id_sede,   //Guardamos el id_sede del establecimeinto al que le hacemos el pedido de aula.
                'estado' => 'PENDIENTE', //Las solicitudes pueden estar en dos estados posibles, pendiente o finalizada.
                'id_responsable' => intval($this->s__id_responsable),
                'tipo_agente' => $datos['tipo_agente'],
                'tipo' => strtoupper($this->s__tipo),  //Puede ser unico o multi.
                'tipo_asignacion' => $datos['tipo_asignacion'],
                'id_sede_origen' => $this->s__id_sede_origen, //Guardamos el id_sede del establecimeinto que realiza el pedido de aula.
                'id_aula' => $this->s__datos_cuadro['id_aula'],
                'facultad' => $this->s__sigla_origen        //Especificamos la sigla del establecimiento que realiza un pedido de aula. En la tabla solicitud
                                                            //el campo para este dato es character varying (6).
            );
            
            //Registramos la solicitud en la base de datos.
            $this->dep('datos')->tabla('solicitud')->nueva_fila($solicitud);
            $this->dep('datos')->tabla('solicitud')->sincronizar();
            $this->dep('datos')->tabla('solicitud')->resetear();
            
            //Si el tipo de solicitud es multi, debemos guardar informacion en la tabla solicitud_multi_evento y
            //multi_evento.
            if(strcmp($this->s__tipo, "multi")==0){
                $secuencia=  recuperar_secuencia('solicitud_id_solicitud_seq');
                $solicitud_multi_evento=array(
                    'id_solicitud' => $secuencia, 
                    'fecha_fin' => $this->s__datos_multi[0]
                );
                
                $this->dep('datos')->tabla('solicitud_multi_evento')->nueva_fila($solicitud_multi_evento);
                $this->dep('datos')->tabla('solicitud_multi_evento')->sincronizar();
                $this->dep('datos')->tabla('solicitud_multi_evento')->resetear();
                
                $hd=new HorariosDisponibles();
                $lista_fechas=$hd->get_dias($fecha, $this->s__datos_multi[0], $this->s__datos_multi[1]);
                
                foreach($lista_fechas as $clave=>$fecha){
                    $multi_evento=array(
                        'id_solicitud' => $secuencia,
                        'fecha' => date('Y-m-d', strtotime($fecha)),
                        'nombre' => utf8_decode($this->obtener_dia(date('N', strtotime($fecha))))
                    );
                    
                    $this->dep('datos')->tabla('multi_evento')->nueva_fila($multi_evento);
                    $this->dep('datos')->tabla('multi_evento')->sincronizar();
                    $this->dep('datos')->tabla('multi_evento')->resetear();
                }
            }
            
            //Obtenemos el correo electronico del destinatario del pedido de aula.
            $destinatario=$this->dep('datos')->tabla('administrador')->get_correo_electronico($id_sede);
            
            //Creamos un objeto para enviar un email de notificacion.
            $email=new Email();
            $envio=$email->enviar_email($destinatario[0]['correo_electronico'], $asunto, $descripcion);
            
            if(!$envio){
                toba::notificacion()->agregar(utf8_decode('La solicitud se registró en forma exitosa, pero se produjo un error al intentar enviar un email de notificación.'), 'error');
                //$this->s__solicitud_registrada=FALSE;
            }
            else{
                $mensaje=' La solicitud se registró en forma exitosa ';
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                //El pedido de pagina genera que el formulario se pueda cargar  nuevamente con datos por defecto
                //pero esto no tiene sentido porque cargamos la solicitud y nos vamos a la pantalla pant_reserva.
                //$this->s__solicitud_registrada=TRUE;
            }
            
            //Debemos bajar el horario disponible seleccionado del arreglo hd_global u horarios_disponibles, para
            //que no vuelva a aparecer en el 'cuadro' de la pantalla pant_reserva. Para ello usamos los datos
            //id_aula, hora_inicio y hora_fin guardados en el arreglo s__datos_cuadro.
            (count($this->s__hd_global)>0) ? $this->bajar_horario_seleccionado(&$this->s__hd_global) : $this->bajar_horario_seleccionado(&$this->s__horarios_disponibles);
            
            $this->set_pantalla('pant_reserva');
        }
        
	function evt__formulario__modificacion($datos)
	{
		$this->dep('datos')->tabla('solicitud')->set($datos);
		$this->dep('datos')->sincronizar();
		$this->resetear();
	}

	function evt__formulario__baja()
	{
		$this->dep('datos')->eliminar_todo();
		$this->resetear();
	}

	function evt__formulario__cancelar()
	{
		$this->resetear();
	}

	function resetear()
	{
		$this->dep('datos')->resetear();
	}
        
//        function obtener_aulas (){
//            //Hay que tener en cuenta el usuario que se loguea
//            $sql="SELECT nombre, id_aula FROM aula WHERE (NOT eliminada)";
//            $aulas=toba::db('gestion_aulas')->consultar($sql);
//            return $aulas;
//        }
        
        function obtener_facultades (){
            //Hay que tener en cuenta quien se loguea?
            //$nombre_usuario=toba::usuario()->get_nombre();
            //$sql="SELECT t_s.sigla FROM sede t_s, administrador t_a WHERE t_a.nombre_usuario=$nombre_usuario AND t_s.id_sede=t_a.id_sede";
            //$sql="SELECT t_ua.sigla, t_ua.descripcion FROM unidad_academica t_ua, sede t_s JOIN administrador t_a ON (t_a.nombre_usuario=$nombre_usuario) JOIN (t_a.id_sede=t_s.id_sede) WHERE t_s.sigla=t_ua.id_sede";
            $sql="SELECT t_s.id_sede, t_ua.descripcion FROM unidad_academica t_ua, sede t_s WHERE t_ua.sigla=t_s.sigla";
            $facultades=toba::db('rukaja')->consultar($sql);
            return $facultades;
        }
        
        
        /*
         * Aqui agregamos toda la interfaz apropiada para devolver las asignaciones para una fecha 
         * seleccionada
         */
        function procesar_periodo ($periodo, $accion){
            foreach ($periodo as $clave=>$valor){
                
                switch ($valor['tipo_periodo']){
                    case 'Cuatrimestre' : if(strcmp($accion, 'hd')==0){
                                              $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_cuatrimestre($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
                                          else{//En esta rama obtenemos las asignaciones para el dia seleccionado 
                                              $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_definitivas_por_fecha_cuatrimestre($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                              $periodo=$this->dep('datos')->tabla('asignacion')->get_asignaciones_periodo_por_fecha_cuatrimestre($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
                                          break;
                                      
                    case 'Examen Final' : if(strcmp($accion, 'hd')==0){
                                              //obtenemos todas las asignaciones por periodo, que estan inluidas en un cuatrimestre,
                                              //pero que pertenecen a un examen_final
                                              $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_examen_final($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
                                          else{
                                              $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_periodo_por_fecha_para_examen($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
                                          break;
                }
                
            }
            
            if((count($cuatrimestre)>0) && (count($examen_final)>0)){
                if(strcmp($accion, 'hd')==0){
                    //debemos iniciar descarte y unificacion
                    //asig_definitivas = cuatrimestre, asig_periodo = examen final.
                    $this->descartar_asignaciones_definitivas($examen_final, &$cuatrimestre);

                    $this->unificar_asignaciones(&$examen_final, $cuatrimestre);

                    return $examen_final;
                }
                else{
                    //debemos unificar periodo con examen final
                    $this->unificar_asignaciones(&$periodo, $examen_final);
                    $this->s__asignaciones_periodo=$periodo;
                    return $cuatrimestre;
                }
            }
            
            if((count($cuatrimestre)>0) && (count($examen_final)==0)){
                if(strcmp($accion, 'hd')==0){
                    //devolvemos solo cuatrimestre
                    return $cuatrimestre;
                }
                else{
                    //devolvemos cuatrimestre y guardamos en una variable de sesion el resultado obtenido en 
                    //periodo
                    $this->s__asignaciones_periodo=$periodo;
                    return $cuatrimestre;
                }
            }
            
            if((count($cuatrimestre)==0) && (count($examen_final)>0)){
                if(strcmp($accion, 'hd')==0){
                    //devolvemos solo examen final
                    return $examen_final;
                }
                else{
                    $this->s__asignaciones_periodo=$examen_final;
                    return $cuatrimestre;
                }
            }
            
            if((count($cuatrimestre)==0) && (count($examen_final)==0)){
                if(strcmp($accion, 'hr')==0){
                    $this->s__asignaciones_periodo=$periodo;
                }
                 
                //devolvemos vacio
                return array();
                               
            }
        }
        
        /*
         * Con esta funcion eliminamos las asignaciones definitivas solapadas con las asignaciones por
         * periodo. Las asignaciones por periodo tiene prioridad.
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
         * Devuelve true si una asignacion por periodo esta incluida en una definitiva. Falta ver inclusion parcial
         */
        function existe_inclusion ($periodo, $definitiva){
            //(strcmp($periodo['aula'], $definitiva['aula'])==0)
            return ( ($periodo['id_aula'] == $definitiva['id_aula']) && 
                   (($periodo['hora_inicio'] >= $definitiva['hora_inicio']) && ($periodo['hora_inicio'] <= $definitiva['hora_fin'])) &&
                   ($periodo['hora_fin'] <= $definitiva['hora_fin']));
        }
        
        function unificar_asignaciones ($periodo, $definitiva){
            foreach ($definitiva as $clave=>$valor){
                if(isset($valor)){
                   $periodo[]=$valor; //agrega al final
                }
            }            
        }
        
        //---- Para mi estos metodos no se ejecutan, pero hay que especificarlos en el toba_editor ----
        function get_persona ($legajo){
            $sql="SELECT nombre, apellido, legajo
                  FROM docente 
                  WHERE legajo=$legajo";
            $docente=toba::db('mocovi')->consultar($sql);
            
            $this->dep('formulario')->ef('legajo')->set_estado($docente[0]['legajo']);
            $this->dep('formulario')->ef('nombre')->set_estado($docente[1]['nombre']);
            
            return ($docente[2]['legajo']);
        }
        
        function get_organizacion ($id_organizacion){
            $sql="SELECT *
                  FROM organizacion 
                  WHERE id_organizacion=$id_organizacion";
            return toba::db('rukaja')->consultar($sql);
        }
        
        /*
         * Esta funcion elimina un horario seleccionado de 'cuadro'. Se utiliza para no volver a mostrar el 
         * mismo horario reservado.
         * @$horarios_disponibles : es un parametro pasado por referencia, puede apuntar a hd_global u
         * horarios_disponibles.
         */
        function bajar_horario_seleccionado ($horarios_disponibles){
            $i=0;
            $n=count($horarios_disponibles);
            $fin=FALSE;
            
            while($i<$n && !$fin){
                $elto=$horarios_disponibles[$i];
                if($elto['id_aula']==$this->s__datos_cuadro['id_aula'] && strcmp($elto['hora_inicio'], $this->s__datos_cuadro['hora_inicio'])==0 && strcmp($elto['hora_fin'], $this->s__datos_cuadro['hora_fin'])==0){
                    $fin=TRUE;
                    unset($horarios_disponibles[$i]);
                }
                $i++;
            }
            
        }
                
}

?>