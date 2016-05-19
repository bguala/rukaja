<?php

require_once(toba_dir().'/php/3ros/phpmailer/class.phpmailer.php');
require_once(toba_dir().'/php/3ros/ezpdf/class.ezpdf.php');
require_once(toba_dir().'/proyectos/gestion_aulas/php/api/HorariosDisponibles.php');
require_once(toba_dir().'/proyectos/gestion_aulas/php/api/Filtro.php');
require_once(toba_dir().'/proyectos/gestion_aulas/php/api/Email.php');

class ci_ver_solicitudes extends toba_ci
{
        protected $s__contador=0;
        protected $s__contador_notificacion=0;
        protected $s__id_sede;
        protected $s__id_solicitud;                      //contiene el id de la solicitud seleccionada
        protected $s__hora_inicio;
        protected $s__hora_fin;
        protected $s__horarios;
        protected $s__i=0;
        protected $s__horarios_disponibles=array();      //contiene todos los horarios disponibles alternativos para el dia solicitado, no coinciden con el horario especificado en la solicitud
        protected $s__horarios_libres;                   //guarda los horarios disponibles segun el requerimiento de hora de inicio y fin de la solicitud
        protected $s__legajo;
        protected $s__id_aula;
        protected $s__emisor;
        protected $s__destinatario;      
        protected $s__sigla;
        protected $s__fecha_consulta;
        protected $s__capacidad;
        protected $s__datos_solicitud;                  //se utiliza para cargar el formulario form_asignacion
        protected $s__nombre_sede;
        protected $s__nombre_facultad;
        protected $s__nombre_aula;
        protected $s__notificar=FALSE;
        protected $s__pantalla_actual;
        protected $s__dia_consulta;
        protected $s__datos_filtro;                    //contiene un cjto. de datos filtrados.
        protected $s__sede_origen;
                
        //se cargan si hay que notificar horarios alternativos
        protected $s__nombre;
        protected $s__apellido;
        protected $s__finalidad;
        
        //------------------------------------------------------------------------------------
        //---- Pant Edicion ------------------------------------------------------------------
        //------------------------------------------------------------------------------------
        
        function conf__pant_edicion (){
            $this->s__pantalla_actual="pant_edicion";
            $this->pantalla()->tab('pant_busqueda')->desactivar();
            $this->pantalla()->tab('pant_asignacion')->desactivar();
        }
        
        //---- Cuadros -----------------------------------------------------------------------
        
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
            if($this->s__contador == 0){ //VER, quizas se puede sacar si este metodo se ejecuta solo una vez
                //la sede se necesita para calcular horarios disponibles
                $this->s__id_sede=$this->dep('datos')->tabla('persona')->get_sede_para_usuario_logueado((toba::usuario()->get_id()));
                $this->s__id_sede=1;
                
                $this->s__emisor=$this->dep('datos')->tabla('persona')->get_correo_electronico($this->s__id_sede);
                $this->s__emisor='sed.uncoma@gmail.com';
            }
            
            $cuadro->set_datos($this->dep('datos')->tabla('solicitud')->get_listado_solicitudes($this->s__id_sede));
            $cuadro->set_titulo("Solicitudes de Aula");
		
	}

	function evt__cuadro__seleccion($datos){
	    //se necesita para pasar la solicitud a estado finalizada o notificar horarios alternativos
            $this->s__id_solicitud=$datos['id_solicitud']; 
            $this->s__hora_inicio=$datos['hora_inicio'];
            $this->s__hora_fin=$datos['hora_fin'];
            $this->s__contador += 1;
            $this->s__legajo=$datos['legajo'];
            
            //guardamos la fecha de solicitud
            $this->s__fecha_consulta=$datos['fecha'];
            $this->s__capacidad=$datos['capacidad'];
            //se necesita para enviar una notificacion si la solicitud es exitosa
            $this->s__sede_origen=$datos['id_sede'];
            $this->s__sigla=$datos['facultad'];
            $this->s__datos_filtro=array();
            //se usa para cargar el formulario form_asignacion           
            $this->s__datos_solicitud=$datos;
            
            
            if($datos['tipo']){
                
                $this->verificar_existencia_de_espacio();
                $this->set_pantalla('pant_busqueda');
            }
	}
        
        
        function verificar_existencia_de_espacio (){
            
            $anio_lectivo=date('Y', strtotime($this->s__fecha_consulta));
            $this->s__dia_consulta=$this->obtener_dia(date('N', strtotime($this->s__fecha_consulta)));
            $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario(date('Y-m-d', strtotime($this->s__fecha_consulta)), $anio_lectivo);
            print_r($periodo);
            //obtenemos las aulas del establecimiento 
            $aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($this->s__id_sede);
            
            //obtenemos las asignaciones para la fecha solicitada           
            $asignaciones=$this->procesar_periodo($periodo, 'hd');
            
            //obtenemos todas las aulas involucradas 
            $aulas=$this->obtener_aulas($asignaciones);
            
            //guardamos el id_sede en sesion, para utilizar dentro de la clase HorariosDisponibles en la 
            //operacion agregar_capacidad.
            toba::memoria()->set_dato_instancia(0, $this->s__id_sede);
            
            $horarios_disponibles=new HorariosDisponibles();
            
            $this->s__horarios_disponibles=$horarios_disponibles->calcular_horarios_disponibles($aulas, $aulas_ua, $asignaciones);
            
            //obtenemos los horarios que coinciden con el requerimiento registrado
            $horarios=$this->calcular_horarios_disponibles_segun_req();
            
            //si existe al menos 1 horario libre que coincide con el requerimiento, lo mostramos en el cuadro_espacio_ocupado :D
            if(count($horarios) > 0){
                $this->s__horarios_libres=$horarios; //contiene los horarios que conciden con el requerimiento
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
        
        /*
         * en base a los horarios disponibles, verifica si existe un espacio segun la hora de inicio y fin 
         * especificadas por el usuario en la solicitud
         */
        function calcular_horarios_disponibles_segun_req (){
            $horarios_disponibles=array();
            foreach($this->s__horarios_disponibles as $horario){
                if($this->verificar_existencia_de_horario($horario)){
                    $horarios_disponibles[]=$horario;
                }
            }
            return $horarios_disponibles;
        }
        
        function verificar_existencia_de_horario ($horario){
            $resultado=false;
            if(($horario['hora_inicio'] <= $this->s__hora_inicio)&&($horario['hora_fin'] >= $this->s__hora_fin)){
                $resultado=true;
            }
            return $resultado;
            
        }
        
	//---- Formulario -------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if ($this->dep('datos')->esta_cargada()) {
			$form->set_datos($this->dep('datos')->tabla('solicitud')->get());
		}
	}

	function evt__formulario__alta($datos)
	{
		$this->dep('datos')->tabla('solicitud')->set($datos);
		$this->dep('datos')->sincronizar();
		$this->resetear();
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
                
        function obtener_dia ($dia_numerico){
            $dias=array( 
                         1 => 'Lunes', 
                         2 => 'Martes',
                         3 => 'Miércoles', 
                         4 => 'Jueves', 
                         5 => 'Viernes', 
                         6 => 'Sábado'
                );
                        
            return $dias[$dia_numerico];
        }
                
        /*
         * genera un arreglo con las aulas utilizadas en un dia especifico
         * @espacios_concedidos contiene todos los espacios concedidos en las aulas de una Unidad Academica en un dia especifico  
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
         * @aulas contiene un cjto de aulas
         * @aula se verifica que si exista en aulas.
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
        
        //----------------------------------------------------------------------------------------
        //---- Pant Busqueda ---------------------------------------------------------------------
        //----------------------------------------------------------------------------------------
        
        function conf__pant_busqueda (){
            $this->s__pantalla_actual="pant_busqueda";
            $this->pantalla()->tab('pant_edicion')->desactivar();
            $this->pantalla()->tab('pant_asignacion')->desactivar();
        }
        
        function conf__filtro (toba_ei_filtro $filtro){
            
        }
        
        function evt__filtro__filtrar ($datos){
            $filtro=new Filtro();
            $this->s__datos_filtro=$filtro->filtrar($datos);
        }
        
        /*
         * cuadro_aulas contiene todos los horarios disponibles que no coinciden con el requerimiento registrado
         */
        function conf__cuadro_aulas (toba_ei_cuadro $cuadro){
            //los datos filtrados tienen prioridad para ser cargados en el cuadro
            if(count($this->s__datos_filtro)>0){
                $cuadro->set_datos($this->s__datos_filtro);
            }
            else{
                if(count($this->s__horarios_libres) > 0){
                    $cuadro->descolapsar();  
                    $cuadro->set_datos($this->s__horarios_disponibles);
                    $cuadro->set_titulo("Horarios Disponibles Alternativos");
                    //$cuadro->colapsar();
                }
                else{
                    $cuadro->set_titulo("Horarios Disponibles Alternativos : ");
                    $cuadro->set_datos($this->s__horarios_disponibles);
                }
            }
                       
        }
        
        function evt__cuadro_aulas__enviar (){
            if($this->s__notificar){
                toba::vinculador()->navegar_a("gestion_aulas", 3525);
            }
            else{
                $mensaje=" Para realizar notificaciones debe descargar previemente un archivo PDF. "
                        . "Presione el botón Descargar Archivo PDF";
                toba::notificacion()->agregar(utf8_decode($mensaje));
            }
        }
        
        function evt__volver (){
            switch($this->s__pantalla_actual){
                case "pant_busqueda"   : $this->set_pantalla('pant_edicion'); 
                                         break;
                case "pant_asignacion" : $this->set_pantalla('pant_busqueda'); 
                                         break;
            }
        }
        
//        function evt__cuadro_aulas__notificar ($datos){
//            $this->s__hora_inicio=$datos['hora_inicio'];
//            $this->s__hora_fin=$datos['hora_fin'];
//            //$this->s__hay_archivo_adjunto=true;
//            $this->set_pantalla('pant_notificacion');
//            $this->s__contador_notificacion += 1;
//        }
        
        /*
         * cuadro_espacio_ocupado contiene todos los horarios disponibles que coinciden con el requerimiento,
         * su  nombre es medio confuso *_*
         */
        function conf__cuadro_espacio_ocupado (toba_ei_cuadro $cuadro){

            $this->pantalla()->tab('pant_edicion')->desactivar();
            $this->pantalla()->tab('pant_asignacion')->desactivar();
            $this->pantalla();
            if(count($this->s__horarios_libres) > 0){
                $cuadro->set_titulo("Horarios Disponibles ");
                $cuadro->set_datos($this->s__horarios_libres);
            }
            else{
                $cuadro->descolapsar();
            }
                    
        }
        
        function evt__cuadro_espacio_ocupado__asignar ($datos){
            //para cargar form_datos
            $this->s__hora_inicio=$datos['hora_inicio'];
            $this->s__hora_fin=$datos['hora_fin'];
            $this->s__id_aula=$datos['id_aula'];
            $this->s__nombre_aula=$datos['aula'];
//            $link=toba::vinculador()->navegar_a(null, 3532, array( 0=>'Formulario' ));
            
            $this->set_pantalla('pant_asignacion');
        }
        
        //---- Pant Asignacion -------------------------------------------------------------------
        
        function conf__pant_asignacion () {
            $this->s__pantalla_actual="pant_asignacion";
            $this->pantalla()->tab('pant_busqueda')->desactivar();
            $this->pantalla()->tab('pant_edicion')->desactivar();
        }

        //---- Form Asignacion -------------------------------------------------------------------
        
        /*
         * Este formulario se carga con informacion que esta lista para ser registrada en las tablas
         * asignacion y asignacion_periodo
         */
        function conf__form_asignacion (toba_ei_formulario $form){
            
            
            if(($this->s__contador_notificacion % 2)==0){
              $form->descolapsar();             
                //obtenemos los datos del docente para registrar una asignacion por periodo
                //y enviar una notificacion
                $sql="SELECT t_p.nro_doc, t_p.tipo_doc, t_p.correo_electronico
                        FROM persona t_p
                        JOIN docente t_d ON (t_p.nro_doc=t_d.nro_doc AND t_p.tipo_doc=t_d.tipo_doc)
                        WHERE t_d.legajo='{$this->s__legajo}'";
            $datos_docente=toba::db()->consultar($sql);
            //$this->s__destinatario=$datos_docente['correo_electronico'];
            $this->s__destinatario='sed.uncoma@gmail.com';
            
            $efs=array( 'hora_inicio',
                        'hora_fin',
                        'finalidad',
                        'fecha',
                        'nro_doc',
                        'tipo_doc',
                        'capacidad',
                        'aula',
                        'facultad',
            );
            $form->set_solo_lectura($efs);
            $form->set_titulo("Formulario para registrar Asignaciones por Periodo");            
            $form->set_datos_defecto($this->dep('datos')->tabla('solicitud')->get_datos_solicitud($this->s__id_solicitud));
            $form->ef('aula')->set_estado($this->s__nombre_aula);
            $form->set_datos_defecto($datos_docente[0]);
            
            
           }
           else{
               $form->colapsar();
           }
        }
        
        function evt__form_asignacion__aceptar ($datos){
            //persisteir en asignacion, asig_periodo, esta_formada y pasar la solicitud a estado finalizada
            print_r("LLegamos a form asignacion aceptar");exit();
            $dia=$this->recuperar_dia($this->s__fecha_consulta);
            
            $asignacion=array(
                'finalidad' => $datos['finalidad'],
                'descripcion' => $datos['descripcion'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
                'cantidad_alumnos' => $datos['cantidad'],
                'facultad' => $this->s__sigla,
                'nro_doc' => $datos['nro_doc'],
                'tipo_doc' => $datos['tipo_doc'],
                'id_aula' => $this->s__id_aula,
                'dias' => $dia,
                'fecha_inicio' => $datos['fecha'],
                'fecha_fin' => $datos['fecha']
            );
            
            $this->registrar_asignacion($asignacion);
            
            $this->registrar_asignacion_periodo($asignacion);
           
//            $this->s__contador_notificacion += 1;
            
            //pasamos la solicitud a estado finalizada
            $this->pasar_a_estado_finalizada($this->dep('datos')->tabla('solicitud')->get_datos_solicitud($this->s__id_solicitud));
            
            //enviamos una notificacion al interesado
            $this->notificar();
            
        }
        
        function registrar_asignacion ($datos){
            $this->dep('datos')->tabla('asignacion')->nueva_fila($datos);
            $this->dep('datos')->tabla('asignacion')->sincronizar();
            $this->dep('datos')->tabla('asignacion')->resetear();
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
            foreach ($dias as $clave=>$dia){
                $dato['nombre']=$dia;
                $dato['id_asignacion']=$secuencia;
                $this->dep('datos')->tabla('esta_formada')->nueva_fila($dato);
                $this->dep('datos')->tabla('esta_formada')->sincronizar();
                $this->dep('datos')->tabla('esta_formada')->resetear();
            }
                        
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
        
        function notificar (){
            //Obtenemos datos personales del responsable de aula que recibio la solicitud.
            //Esta informacion se utiliza para firmar la notificacion           
            $emisor=$this->dep('datos')->tabla('persona')->get_datos_emisor(toba::usuario()->get_id());
            //obtenemos el correo electronico del responsable de aula 
            $destinatario=$this->dep('datos')->tabla('persona')->get_correo_electronico($this->s__sede_origen);
            //creamos un asunto por defecto
            $asunto="SOLICITUD CONCEDIDA";
            
            //creamos una descripcion por defecto
            //$descripcion="La SOLICITUD DE AULA en el Establecimiento {$emisor['establecimiento']}, para el dia {$this->s__fecha} en el horario {$this->s__hora_inicio} a {$this->s__hora_fin} hs ha sido registrada exitosamente. \n\n {$emisor['responsable']}";
            $descripcion="La SOLICITUD DE AULA en el Establecimiento Administracion Central, para el dia {$this->s__fecha_consulta} en el horario {$this->s__hora_inicio} - {$this->s__hora_fin} hs ha sido registrada exitosamente. \n\n Santiago Briceño";
            
            $email=new Email();
            //enviamos un email automaticamente. Su objetivo es notificar el resultado positivo de la solicitud.
            $email->enviar_email($destinatario, $asunto, $descripcion);
                                    
            //volvemos a la pantalla inicial
            $this->set_pantalla('pant_edicion');
        }
        
               
        //---- Form Datos ------------------------------------------------------------------------
        
        function conf__form_datos (toba_ei_formulario $form){
            if(($this->s__contador_notificacion % 2) == 0){
                $form->descolapsar();
                $form->set_titulo("Datos Disponibles");
                $form->ef('hora_inicio')->set_estado($this->s__hora_inicio);
                $form->ef('hora_fin')->set_estado($this->s__hora_fin);
                $form->ef('solicitud')->set_estado($this->s__id_solicitud);
                $form->ef('legajo')->set_estado($this->s__legajo);
            }
            else{
                $form->colapsar();
            }
        }       
        
//        function enviar_email ($datos){
//            $email=new PHPMailer();
//            $email->IsSMTP();
//            $email->SMTPAuth='true';
//            $email->SMTPSecure='ssl';
//            $email->Host='smtp.gmail.com';
//            $email->Port=465;
//            
//            $email->Username='sed.uncoma@gmail.com';
//            
//            $email->Password='n1s.toba15';
//            $email->Timeout=100;
//            
//            $email->SetFrom('sed.uncoma@gmail.com'); //de
//            
//            try{
//                $email->AddAddress('sed.uncoma@gmail.com'); //para
//                
//                $email->Subject=$datos['asunto']; //agregamos el asunto
//                
//                $email->Body=$datos['descripcion']; //agregamos la descripcion
//                
//                if(isset($datos['adjunto'])){
//                    $email->AddAttachment($datos['dajunto']['tmp_name'], $datos['adjunto']['name']);
//                }
//                
//                
//                if($email->Send()){
//                    $mensaje=  utf8_decode(" La notificación por email a {$this->s__legajo} se envió correctamente ");
//                    $nivel="info";
//                }
//                else{
//                    $mensaje=utf8_decode(" No se pudo enviar ninguna notificación por email");
//                    $nivel="error";
//                }
//                
//                toba::notificacion()->agregar($mensaje, $nivel);
//                
//            } catch (phpmailerException $ex) {
//                print_r($ex);
//            }
//            
//        }
        
        function vista_pdf (toba_vista_pdf $salida){
            $this->generar_pdf($salida);      
            
            $this->s__notificar=TRUE;
        }
        
        function generar_pdf (toba_vista_pdf $salida){
            //obtenemos un objeto Cezpdf
            $pdf=$salida->get_pdf();
            
            //configuramos los margenes del pdf (top, bottom, left, right)
            $pdf->ezSetMargins(40, 40, 33, 33);
            
            //definimos el formato del pie de pagina
            $pie_de_pagina="Página {PAGENUM} de {TOTALPAGENUM}";
            
            //agregamos el numero de pagina al pdf
            $pdf->ezStartPageNumbers(300, 20, 8, 'left', utf8_d_seguro($pie_de_pagina));
            
            //definimos el formato de la tabla que contiene el pdf
            $formato_cuadro=array(
                'splitRows' => 0,
                'rowGraph' => 1,
                'showHeadings' => true,
                'titleFontSize' => 9,
                'fontSize' => 10, //definimos el tamanio de fuente
                'shadeCol' => array(0.9,0.9,0.9), //especificamos el color de cada fila
                'xOrientation' => 'center',
                'width' => 500,
                'xPos' => 'center',
                'yPos' => 'center',
            );
            
            //definimos los nombres de las columnas de la tabla
            $formato_columna=array(
                'hora_inicio' => 'Hora de Inicio',
                'hora_fin' => 'Hora de Fin',
                'aula' => 'Aula',
            );
            
            //definimos el encabezado del documento
            $this->configurar_encabezado($pdf);
            
            //definimos el nombre del archivo
            $salida->set_nombre_archivo("Horarios Disponibles Alternativos.pdf");
                        
            //agregamos la tabla al pdf
            $pdf->ezTable($this->s__horarios_disponibles, $formato_columna, "", $formato_cuadro);
            
            //cerramos el buffer de salida. Si no se cierra dicho buffer el archivo pdf se genera dañado
            ob_end_clean(); 
            $pdf->ezOutput(0);
            
            //encabezados HTTP al cliente
            header('Cache-Control: private');
            header('Content-type: application/pdf');
            header('Content-Disposition: attachment; '); //filename="Archivo"
            header('Pragma: no-cache');
            header('Expires: 0');            
        }
        
        function configurar_encabezado (Cezpdf $pdf){
            //obtenemos los datos personales del responsable de aula
//            $sql="SELECT (t_a.nombre || t_a.apellido) as nombre_usuario, t_s.descripcion sede, t_ua.descripcion facultad
//                  FROM administrador t_a 
//                  JOIN sede t_s ON (t_a.id_sede=t_s.id_sede)
//                  JOIN unidad_academica t_ua ON (t_s.sigla=t_ua.sigla)
//                  WHERE t_s.id_sede={$this->s__id_sede}";
//            $datos_admin=toba::db('gestion_aulas')->consultar($sql);
//            $firma=$datos_admin[0]['nombre_usuario'];
//            $this->s__nombre_sede=$datos_admin[0]['sede'];
//            $this->s__nombre_facultad=$datos_admin[0]['facultad'];
            
            $this->s__nombre_facultad='Facultad de Ciencias del Ambiente y la Salud';
            $this->s__nombre_sede='Neuquén Capital';
            
            //obtenemos fecha y hora para arnar el encabezado inicial
            $fecha=date('d-m-y');
            $hora=date('H:m:s');
            //definimos una cadena origen para crear el resto del encabezado
            $origen="Fecha : $fecha-------------------------------------------------------------------------------------------------------------------------------------------------------Hora : $hora";
            
            $encabezado_inicial=$this->armar_precontenido($origen, TRUE);
            
            $solicitud="DATOS DE LA SOLICITUD\n";
            
            $pdf->ezText($encabezado_inicial, 8, array('justification' => 'left'));
            $pdf->ezText($solicitud, 8, array('justification' => 'center'));
            
            $cuerpo=$this->armar_cuerpo($origen);
            $pdf->ezText(utf8_d_seguro($cuerpo), 8, array('justification' => 'left'));
            
            $horario="HORARIOS ALTERNATIVOS DISPONIBLES\n";
            $pdf->ezText($horario, 8, array('justification' => 'center'));
            
            $linea=$this->armar_precontenido($origen, FALSE);
            $pdf->ezText($linea, 8, array('justification' => 'left'));
            
            //definimos el ultimo segmento del encabezado    
            $fin="Estimado/a {$this->s__nombre} {$this->s__apellido} :\n\nLe informamos que en la fecha {$this->s__fecha_consulta} no tenemos aulas disponibles para satisfacer el requerimiento especificado en este documento.\nA continuación adjuntamos los horarios que tenemos disponibles.\n";
            $pdf->ezText(utf8_d_seguro($fin), 8, array('justification' => 'left'));
            
            $firma="Santiago Briceño.\n\n";
            $pdf->ezText(utf8_d_seguro($firma), 8, array('justification' => 'right'));
            
       }
        
        /*
         * Agrega las primeras tres lineas al documento
         */
        function armar_precontenido ($origen, $resultado){
            $longitud=  strlen($origen) + 12;
            $guion='';
            $i=0;
            while($i<$longitud){
                $guion .= '-';
                $i += 1;
            }
            
            return ($resultado) ? ($origen . "\n\n" . $guion . "\n") : ($guion . "\n");
        }
        
        /*
         * Genera los datos de la solicitud alineados.
         */
        function armar_cuerpo ($origen){
            $solicitud=$this->dep('datos')->tabla('solicitud')->get_datos_basicos_solicitud($this->s__id_solicitud);      
            $this->s__nombre=$solicitud[0]['nombre'];
            $this->s__apellido=$solicitud[0]['apellido'];
            $codigo="Número de Solicitud : {$this->s__id_solicitud}";
            $emisor="Emisor : {$solicitud[0]['nombre']} {$solicitud[0]['apellido']}";
            $establecimiento="Establecimiento : {$this->s__nombre_facultad} ({$this->s__nombre_sede})";
            $hora_inicio="Hora de Inicio : {$this->s__hora_inicio}";
            $hora_fin="Hora de Fin : {$this->s__hora_fin}";
            $capacidad="Capacidad del aula : {$this->s__capacidad} Alumnos";
            $fecha_pedido="Fecha de Pedido : {$this->s__fecha_consulta}";
            $finalidad="Finalidad : {$solicitud[0]['finalidad']}";
            
            //definimos una longitud maxima para completar con espacios en blanco cada oracion del documento
            $longitud=  85; 
            
            $items=array(
                0 => array($codigo, $emisor),
                1 => array($establecimiento),
                2 => array($finalidad),
                3 => array($capacidad, $fecha_pedido),
                4 => array($hora_inicio,$hora_fin),
            );
            $cadena="";           
            foreach($items as $clave=>$valor){
                
                $cadena .= $this->generar_cadena($valor, $longitud);
                
            }
            
            return ($this->armar_precontenido($origen, FALSE) . "\n" .$cadena . $this->armar_precontenido($origen, FALSE));
        }
        
        /*
         * El objetivo de esta funcion es alinear los dos componentes que forman una oracion del encabezado.
         */
        function generar_cadena ($par, $longitud){
            
            $resultado="";
            if((count($par)) == 2){
                $iterar_hasta=$longitud - (strlen(ltrim($par[0])));
                $i=0;
                //definimos como separador a un espacio en blanco
                $separador="";
            
                while($i < $iterar_hasta){
                    $separador .= " ";
                    $i += 1;
                }
                
                $resultado=(($par[0]) . $separador . (ltrim($par[1])) . "\n\n");
            }
            else{
                $resultado=(($par[0]) . "\n\n");
            }
            
            return $resultado;
        }
        
        /*
         * se utiliza para pasar una solicitud a estado finalizada
         */
        function pasar_a_estado_finalizada ($datos){
            $datos['estado']="Finalizada";
            $this->dep('datos')->tabla('solicitud')->cargar(array('id_solicitud'=>$this->s__id_solicitud));
            $this->dep('datos')->tabla('solicitud')->set($datos);
            $this->dep('datos')->tabla('solicitud')->sincronizar();
        }
        
        //-----------------------------------------------------------------------------------------
        //---- Interfaz para procesar periodos ----------------------------------------------------
        //-----------------------------------------------------------------------------------------
        
        /*
         * Aqui agregamos toda la interfaz apropiada para devolver las asignaciones para una fecha 
         * seleccionada
         */
        function procesar_periodo ($periodo, $accion){
            $cuatrimestre=array();
            $examen_final=array();
            foreach ($periodo as $clave=>$valor){
                
                switch ($valor['tipo_periodo']){
                    case 'Cuatrimestre' : if(strcmp($accion, 'hd')==0){
                                              $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_cuatrimestre($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
                                              
                                          }
//                                          else{
//                                              $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_definitivas_por_fecha_cuatrimestre($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
//                                              $periodo=$this->dep('datos')->tabla('asignacion')->get_asignaciones_periodo_por_fecha_cuatrimestre($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
//                                          }
                                          break;
                                      
                    case 'Examen Final' : if(strcmp($accion, 'hd')==0){
                                              //obtenemos todas las asignaciones por periodo, que estan inluidas en un cuatrimestre,
                                              //pero que pertenecen a un examen_final
                                              $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_examen_final($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
//                                          else{
//                                              $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_periodo_por_fecha_para_examen($this->s__id_sede, $this->s__dia_consulta, $valor['id_periodo'], $this->s__fecha_consulta);
//                                          }
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
        
        

}

?>