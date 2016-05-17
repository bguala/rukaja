<?php

include_once(toba_dir().'/proyectos/rukaja/php/api/HorariosDisponibles.php');
include_once(toba_dir().'/proyectos/rukaja/php/api/Filtro.php');
include_once(toba_dir().'/proyectos/rukaja/php/api/Email.php');

class ci_solicitar_aula extends toba_ci
{
        protected $s__id_sede;
        protected $s__fecha_consulta;
        protected $s__dia_consulta;
        protected $s__horarios_disponibles=array();
        protected $s__datos_form;
        protected $s__datos_filtrados;
        protected $s__datos_cuadro;
        protected $s__solicitud_registrada;
        protected $s__filtro;
        
        //---- Pant Busqueda ------------------------------------------------------------------
        
        function conf__pant_reserva (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab('pant_edicion')->desactivar();
        }
	//---- Form Ingreso -------------------------------------------------------------------

        function conf__form_ingreso (toba_ei_formulario $form){
            
        }
        
        function evt__form_ingreso__aceptar ($datos){
            $this->s__id_sede=$datos['sede'];
            $this->s__fecha_consulta=$datos['fecha'];
            
            $unidad_academica=$this->dep('datos')->tabla('unidad_academica')->get_unidad_academica_mas_sede($datos['facultad'],$this->s__id_sede);
            
            //Se utiliza para cargar el formulario form_datos de la pantalla pant_reserva
            $this->s__datos_form=array(
                'facultad' => $unidad_academica[0]['facultad'],
                'sede' => $unidad_academica[0]['sede'],
                'fecha' => date('d-m-Y', strtotime($this->s__fecha_consulta))
            );
            $this->calcular_horarios_disponibles_por_facultad();
            //se utiliza para realizar busquedas por capacidad, hora_inicio y hora_fin.
            $this->s__filtro=new Filtro($this->s__horarios_disponibles);
        }
        
        function calcular_horarios_disponibles_por_facultad (){
                       
            //obtenemos todas las aulas de un establecimiento
            $aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($this->s__id_sede);
            
            if(count($aulas_ua)==0){
                $mensaje=" La Unidad Académica seleccionada no posee aulas registradas en el Sistema ";
                toba::notificacion()->agregar(utf8_decode($mensaje),'info');
                $this->s__horarios_disponibles=array();
            }
            else{
                $anio_lectivo=date('Y', strtotime($this->s__fecha_consulta));
                $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($this->s__fecha_consulta, $anio_lectivo);
                $this->s__dia_consulta=$this->obtener_dia(date('N', strtotime($this->s__fecha_consulta)));
                
                //obtenemos todas las asignaciones para la fecha seleccionada
                $asignaciones=$this->procesar_periodo($periodo, 'hd');
            
                //obtenemos las aulas que poseen asignaciones
                $aulas=$this->obtener_aulas($asignaciones);
                toba::memoria()->set_dato_instancia(0, $this->s__id_sede);
                $horarios_disponibles=new HorariosDisponibles();
            
                $this->s__horarios_disponibles=$horarios_disponibles->calcular_horarios_disponibles($aulas, $aulas_ua, $asignaciones);
                                
                if(count($this->s__horarios_disponibles)==0){
                    $mensaje="La Unidad Académica seleccionada no posee horarios disponibles";
                    toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                }

            }
            
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
        
        function obtener_cuatrimestre (){
            $fecha=  getdate();
            $cuatrimestre=2;
            if(($fecha['mon'])>=1 && ($fecha['mon'])<=6){
                $cuatrimestre=1;
            }
            
            return $cuatrimestre;
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
         * A partir de una fecha devolvemos el anio 
         */
        function recuperar_anio ($fecha){
            
            $anio=date('Y', strtotime($fecha));
            
            return $anio;
        }
        
        //---- Filtro -----------------------------------------------------------------------
        
        function conf__filtro (toba_ei_filtro $filtro){
            if(count($this->s__horarios_disponibles)==0){
                $filtro->colapsar();
            }
            else{
                $filtro->set_titulo(utf8_decode("Opciones de búsqueda"));
                $filtro->descolapsar();
            }
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
                
        //---- Form Datos -------------------------------------------------------------------
        
        function conf__form_datos (toba_ei_formulario $form){
            if(count($this->s__horarios_disponibles)==0){
                $form->colapsar();
            }
            else{
                $form->descolapsar();
                $form->set_datos($this->s__datos_form);
            }
        }
        
        //---- Cuadro -----------------------------------------------------------------------
        
        function conf__cuadro (toba_ei_cuadro $cuadro){
            if(count($this->s__datos_filtrados)>0){
                $cuadro->set_datos($this->s__datos_filtrados);
            }
            else{
            if(count($this->s__horarios_disponibles)==0){
                $cuadro->colapsar();
            }
            else{
                $cuadro->descolapsar();
                $cuadro->set_datos($this->s__horarios_disponibles);
            }
            }
        }
        
        function evt__cuadro__seleccionar ($datos){
            //es necesario usar strtotime para no generar conflictos entre fechas
            $datos['fecha']=date('d-m-Y', strtotime($this->s__fecha_consulta));
            $this->s__datos_cuadro=$datos;
            $this->set_pantalla("pant_edicion");
        }
        
        function evt__volver (){
            $this->set_pantalla("pant_reserva");
        }
    
        //---- Pant Edicion -----------------------------------------------------------------
        
        function conf__pant_edicion (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab('pant_reserva')->desactivar();
        }
        
	//---- Formulario -------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
            
                $fecha= date('d-m-Y');
                
                //$nro_solicitud=  recuperar_secuencia('solicitud_id_solicitud_seq');
                $form->ef('fecha_actual')->set_estado($fecha);
                $form->ef('solicitud')->set_estado_defecto(16743);
                $form->ef('inicio')->set_estado($this->s__datos_cuadro['hora_inicio']);
                $form->ef('fin')->set_estado($this->s__datos_cuadro['hora_fin']);
                
                if(!$this->s__solicitud_registrada){
                    $form->ef('facultad_destino')->set_estado($this->s__datos_form['facultad']);
                    $form->set_datos($this->s__datos_cuadro);
                }
            
	}
        
                
        function ajax__autocompletar_form ($legajo, toba_ajax_respuesta $respuesta){
            
            $sql="SELECT t_p.nombre,
                         t_p.apellido
                  FROM persona t_p 
                  JOIN docente t_d ON (t_p.nro_doc=t_d.nro_doc)
                  WHERE t_d.legajo='$legajo'";
            $datos_docente=toba::db('rukaja')->consultar($sql);
            
            $contenido=$this->dep('formulario')->ef('legajo')->get_estado();
            print_r("Este es el valor de contenido : $contenido");
            
            if(count($datos_docente) != 0){
                
                $respuesta->agregar_cadena('accion', "y");
                $respuesta->agregar_cadena('nombre', $datos_docente[0]['nombre']);
                $respuesta->agregar_cadena('apellido', $datos_docente[0]['apellido']);
            }
            else{
                $respuesta->agregar_cadena('accion', "x");
            }
            
            
        }

	function evt__formulario__alta($datos)
	{
            if(strcmp('OTRO', $datos['tipo'])==0){
                $this->dep('datos')->tabla('tipo_asignacion')->nueva_fila(array('tipo'=>  strtoupper($datos['tipo_nombre'])));
                $this->dep('datos')->tabla('tipo_asignacion')->sincronizar();
                $this->dep('datos')->tabla('tipo_asignacion')->resetear();
            }
            
            //persistimos informacion en la tabla persona, no es lo mejor, pero hay que ahorrarse 
            //complicaciones.
            if(strcmp('Organizacion', $datos['tipo'])==0){
                $organizacion=array(
                    'nro_doc' => strtoupper($datos['nombre_org']),
                    'tipo_doc' => 'ORG',
                    'telefono' => $datos['telefono_org'],
                    'correo_electronico' => strtolower($datos['email_org']),
                    'nombre' => strtoupper($datos['nombre_org']),
                    'apellido' => ' '
                );
                $this->dep('datos')->tabla('persona')->nueva_fila($organizacion);
                $this->dep('datos')->tabla('persona')->sincronizar();
                $this->dep('datos')->tabla('persona')->resetear();
            }
            
            //anteriormente se hacia un chequeo de horarios, ya no es necesario
            $this->registrar_solicitud($datos);
                        
	}
                
        function registrar_solicitud ($datos){
            
            $nombre=  strtoupper($datos['nombre']);
            $apellido=  strtoupper($datos['apellido']);
            $fecha= date('d-m-Y', strtotime($this->s__fecha_consulta));
            
            $datos['estado']='Pendiente';
            $datos['id_sede']=$this->s__id_sede;
            $datos['id_aula']=$this->s__datos_cuadro['id_aula'];
            
            $descripcion="$nombre $apellido ha registrado una SOLICITUD de aula para el dia $fecha, en su Establecimiento. ";

            $datos['tipo']=TRUE;
            $asunto="SOLICITUD DE AULA";
                            
            $this->dep('datos')->tabla('solicitud')->nueva_fila($datos);
            $this->dep('datos')->tabla('solicitud')->sincronizar();
            $this->dep('datos')->tabla('solicitud')->resetear();
            
            $id_sede=$datos['id_sede'];
            $destinatario=$this->dep('datos')->tabla('persona')->get_correo_electronico($id_sede);
            
            $email=new Email();
            $envio=$email->enviar_email($destinatario[0]['correo_electronico'], $asunto, $descripcion);
            
            if(!$envio){
                toba::notificacion()->agregar(utf8_decode('Se produjo un error al intentar enviar un email de notificación.'), 'error');
                $this->s__solicitud_registrada=FALSE;
            }
            else{
                $mensaje=' La solicitud se registró en forma exitosa ';
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                $this->s__solicitud_registrada=TRUE;
            }
            
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
                                              $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_cuatrimestre($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
                                          else{
                                              $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_definitivas_por_fecha_cuatrimestre($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
                                              $periodo=$this->dep('datos')->tabla('asignacion')->get_asignaciones_periodo_por_fecha_cuatrimestre($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
                                          break;
                                      
                    case 'Examen Final' : if(strcmp($accion, 'hd')==0){
                                              //obtenemos todas las asignaciones por periodo, que estan inluidas en un cuatrimestre,
                                              //pero que pertenecen a un examen_final
                                              $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_examen_final($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
                                          else{
                                              $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_periodo_por_fecha_para_examen($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
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
         * devuelve true si una asignacion por periodo esta incluida en una definitiva.
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
        

}

?>