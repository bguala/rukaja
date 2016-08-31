<?php

include_once(toba_dir().'/proyectos/rukaja/php/api/HorariosDisponibles.php');
include_once(toba_dir().'/proyectos/rukaja/php/api/Filtro.php');

class ci_calendario_comahue extends toba_ci
{
        protected $s__fecha_seleccionada;                //Guarda la fecha seleccionada del calendario
        protected $s__tipo_seleccion;                    //Guarda la opcion elegida en el combo tipo
        protected $s__fecha_consulta;                    //Para extraer asignaciones
        protected $s__dia_consulta;                      //Para extraer asignaciones
        protected $s__horarios_disponibles=array();      //Guarda todos los horarios disponibles para una fecha especifica
        protected $s__id_sede;                           //Guarda la sede del usuario logueado
        protected $s__asignaciones=array();              //Guarda las asignaciones que hay que mostrar en el cuadro
        protected $s__asignaciones_periodo;              //Guarda las asignaciones por periodo que estan solapadas con asignaciones definitivas
        protected $s__datos_filtro=array();              //Guarda un cjto de registros filtrados
        protected $s__asig_solapadas=null;
        
                
        //-----------------------------------------------------------------------------------------------
        //---- Pant Edicion -----------------------------------------------------------------------------
        //-----------------------------------------------------------------------------------------------
        
        function conf__pant_edicion (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab('pant_horarios')->desactivar();
            $this->pantalla()->tab('pant_periodo')->desactivar();
        }
        
        //---- Calendario -------------------------------------------------------------------------------
        
        function conf__calendario (toba_ei_calendario $calendario){
            
            $calendario->set_seleccionar_solo_dias_pasados(FALSE);
            //hace que el calendario se vea mas grande
            $calendario->set_ver_contenidos(TRUE);
            $calendario->set_sab_seleccionable(TRUE);
            $calendario->set_dom_seleccionable(TRUE);
            //$calendario->set_rango_anios($inicio, $fin);
            
        }
        
        function evt__calendario__seleccionar_dia ($seleccion){
            $this->s__fecha_seleccionada=$seleccion;
            $this->set_pantalla('pant_horarios');
        }
        
        function evt__volver (){
            $this->s__horarios_disponibles=array();
            $this->s__asignaciones=array();
            //$this->s__asignaciones_periodo=array();
            $this->s__datos_filtro=array();
            $this->set_pantalla('pant_edicion');
        }
        
        //-----------------------------------------------------------------------------------------------
        //---- Pant Horarios ----------------------------------------------------------------------------
        //-----------------------------------------------------------------------------------------------
        
        function conf__pant_horarios (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab('pant_edicion')->desactivar();
            $this->pantalla()->tab("pant_periodo")->desactivar();
        }
        
        //---- Formulario -------------------------------------------------------------------------------
        
        function conf__formulario (toba_ei_formulario $form){
            //Obtenemos la sede del usuario logueado en el sistema.
            
            $this->s__id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
           
                       
            $fecha="{$this->s__fecha_seleccionada['dia']}-{$this->s__fecha_seleccionada['mes']}-{$this->s__fecha_seleccionada['anio']}";
            $dia_numerico=date('N', strtotime($fecha));
            $this->s__dia_consulta=$this->obtener_dia($dia_numerico);
            
            $form->ef('fecha')->set_estado($fecha);
            $form->ef('dia')->set_estado(utf8_decode($this->s__dia_consulta));
            $form->set_solo_lectura(array('fecha','dia'));
            
            $this->s__fecha_consulta="{$this->s__fecha_seleccionada['anio']}-{$this->s__fecha_seleccionada['mes']}-{$this->s__fecha_seleccionada['dia']}";
        }
        
        function evt__formulario__aceptar ($datos){
            $this->s__horarios_disponibles=array();
            $this->s__asignaciones=array();
            $this->s__asignaciones_periodo=array();
            $this->s__datos_filtro=array();
                        
            if(strcmp('Horarios Disponibles', $datos['tipo'])==0){
                $this->s__tipo_seleccion="Horarios disponibles";
                $this->obtener_horarios_disp();
            }
            else{
                $this->obtener_asignaciones();
            }
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
         * A partir de una fecha devolvemos el nombre del dia 
         */
        function recuperar_dia ($fecha){
            //si usamos w obtenemos 0 para domingo y 6 para sabado
            //si usamos N obtenemos 1 para lunes y 7 para domingo
            $dia_numerico=date('N', strtotime($fecha));
            
            return (array($this->obtener_dia($dia_numerico)));
        }
        
        /*
         * A partir de una fecha devolvemos el anio 
         */
        function recuperar_anio ($fecha){
            
            return (date('Y', strtotime($fecha)));
        }
                
        //---- Filtro ----------------------------------------------------------------------------------
        
        function conf__filtro (toba_ei_filtro $filtro){
            if((count($this->s__asignaciones)>0) || (count($this->s__horarios_disponibles)>0)){
                $filtro->set_titulo(utf8_decode("Opciones de búsqueda"));
                $filtro->descolapsar();
            }
            else{
                $filtro->colapsar();
            }
        }
        
        function evt__filtro__filtrar ($datos){
            
            if(count($this->s__horarios_disponibles) > 0){
//                $this->filtrar_datos($datos['aula']['valor'], $this->s__horarios_disponibles);
//                $this->dep('cuadro_horarios_disponibles')->set_datos($this->s__filtro);
                
                $filtro=new Filtro($this->s__horarios_disponibles);
                $this->s__datos_filtro=$filtro->filtrar($datos);
                
                
                //$this->dep('cuadro_horarios_disponibles')->set_datos($datos_filtro);
            }
            else{
                $filtro=new Filtro($this->s__asignaciones);
                $this->s__datos_filtro=$filtro->filtrar($datos);
                //$this->dep('cuadro_asignaciones')->set_datos($datos_filtro);
            }
            
        }
        
        function evt__filtro__limpiar (){
            //Nunca se puede dar el caso de tener cargados los arreglos s__horarios_disponibles y 
            //s__asignaciones al mismo tiempo. Por lo tanto para restaurar el contenido de ambos cuadros 
            //hay que vaciar el arreglo s__datos_filtro.
            $this->s__datos_filtro=array();
        }
        
                
        //---- Cuadro Horarios Disponibles --------------------------------------------------------------
        
        function conf__cuadro_horarios_disponibles (toba_ei_cuadro $cuadro){
            if(count($this->s__horarios_disponibles)==0){
                
                $cuadro->colapsar();
            }
            else{
                if(count($this->s__datos_filtro) > 0){
                    $cuadro->set_datos($this->s__datos_filtro);
                }
                else{
                        $cuadro->set_titulo("Horarios Disponibles");
                        $cuadro->set_datos($this->s__horarios_disponibles);
                    
                }
            }
        }
        
        function evt__cuadro_horarios_disponibles__seleccionar ($datos){
            
            $parametros=array(
                'id_aula' => $datos['id_aula'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
                'dias' => $this->s__dia_consulta,
                'fecha_inicio' => $this->s__fecha_consulta,
                'fecha_fin' => $this->s__fecha_consulta,
                'tipo' => 'Periodo'
            );
            //Generamos un vinculo a la operacion 'Cargar Asignaciones'.
            toba::vinculador()->navegar_a("rukaja", 3567,$parametros);
        }
        
        /*
         * Vinculo asociado al boton PDF del cuadro horarios_disponibles. Se usa para generar un docuemento pdf
         * personalizado. Es una alternativa para generar un reporte con los horarios disponibles. Formará parte
         * del sistema aunque no se use.
         */
        function vista_pdf (toba_vista_pdf $salida){
            ob_end_clean();
            $salida->set_nombre_archivo("Horarios Disponibles.pdf");
            $pdf=$salida->get_pdf();
            $encabezado=$this->generar_encabezado(150, TRUE);
            //$encabezado .= ('\nHorarios Disponibles\n'.$this->generar_encabezado(strlen($encabezado), FALSE).'\n');
            //sin margenes se superpone el texto con la imagen encabezado_reporte
            $pdf->ezSetMargins(68, 30, 35, 35);
            $pdf->addJpegFromFile(toba_dir().'/www/img/encabezado_reporte.jpg', 35, 785, 260, 58);
            //definimos el formato del pie de pagina
            $pie_de_pagina="Página {PAGENUM} de {TOTALPAGENUM}";
            $pdf->ezText($encabezado, 8, array('justification'=>'center', 'spacing'=>1.5));
            //agregamos el numero de pagina al pdf
            $pdf->ezStartPageNumbers(550, 20, 8, 'left', utf8_d_seguro($pie_de_pagina));
            
            $pdf->ezText("Horarios Disponibles", 8, array('justification'=>'center', 'spacing'=>1.5));
            $pdf->ezText($this->generar_encabezado(strlen($encabezado), FALSE), 8, array('justification'=>'center', 'spacing'=>1.5));
            
            $this->agregar_tabla($pdf);            
            
        }
        
        function agregar_tabla (Cezpdf $pdf){
            $opciones=array(
                'splitRows' => 0,
                'rowGraph' => 0,
                'showHeadings' => true,
                'titleFontSize' => 6,
                'fontSize' => 6, //definimos el tamanio de fuente
                'shadeCol' => array(0.9,0.9,0.9),//especificamos el color de cada fila
                'xOrientation' => 'center',
                'width' => 500,
                'xPos' => 'centre',
                'yPos' => 'centre',
            );
            
            $columnas=array(
                'aula' => 'Aula',
                'capacidad' => 'Capacidad',
                'hora_inicio' => 'Hora Inicio',
                'hora_fin' => 'Hora Fin',
            );
            
            $pdf->ezTable($this->s__horarios_disponibles, $columnas, $title, $opciones);
        }
        
        function generar_encabezado ($fin, $true){
            $fecha=date('d-m-Y', strtotime($this->s__fecha_consulta));
            $encabezado=($true) ? "Fecha : $fecha" : '';
            $i=0;
            while ($i < $fin){
                $encabezado .= '-';
                $i += 1;
            }
            
            if($true){
                $hora=date('H:i:s');
                return ($encabezado."Hora : $hora");
            }
            else{
                return $encabezado;
            }
        }
        
        //---- Cuadro Asignaciones ----------------------------------------------------------------------
        
        function conf__cuadro_asignaciones (toba_ei_cuadro $cuadro){
            if(count($this->s__asignaciones)==0){
                $cuadro->colapsar();
                
            }
            else{
                if(count($this->s__datos_filtro) > 0){
                    $cuadro->set_datos($this->s__datos_filtro);
                }
                else{
                        $cuadro->set_titulo("Asignaciones registradas en el sistema");
                        $cuadro->set_datos($this->s__asignaciones);                   
                }
            }
        }
        
        function evt__cuadro_asignaciones__ver ($datos){
            //debemos extraer las asig_per solapadas con asig_definitivas. Las asig_per estan en 
            //s__asignaciones_periodo
            $this->s__asig_solapadas=$this->extraer_asignaciones_solapadas($datos);
            
            if(count($this->s__asig_solapadas)==0){
                $mensaje=" No existen asignaciones por período solapadas ";
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
            }
            else{
                $this->set_pantalla("pant_periodo");
            }
            
        }
        
        function extraer_asignaciones_solapadas ($datos){            
            $asig_solapadas=array();
            foreach ($this->s__asignaciones_periodo as $clave=>$valor){
                if($this->analizar_horario($datos, $valor)){
                    //agregamos valores existentes pra el corte de control
                    $valor['finalidad_def']=$datos['finalidad'];
                    $valor['hora_inicio_def']=$datos['hora_inicio'];
                    $valor['hora_fin_def']=$datos['hora_fin'];
                    $asig_solapadas[]=$valor;
                }
            }
            return $asig_solapadas;
        }
        
        function analizar_horario ($datos, $valor){
            $id_aula_def=$datos['id_aula'];
            $hora_inicio_def="{$datos['hora_inicio']}:00";
            $hora_fin_def="{$datos['hora_fin']}:00";
            
            $id_aula_per=$valor['id_aula'];
            $hora_inicio_per="{$valor['hora_inicio']}:00";
            $hora_fin_per="{$valor['hora_fin']}:00";
            
            return (($id_aula_def == $id_aula_per) && (($hora_inicio_per >= $hora_inicio_def && $hora_inicio_per <= $hora_fin_def && $hora_fin_per <= $hora_fin_def) 
                   || ($hora_inicio_per >= $hora_inicio_def && $hora_inicio_per <= $hora_inicio_def) 
                   || ($hora_fin_per >= $hora_inicio_def && $hora_fin_per <= $hora_inicio_def) 
                   ));
        }
        
                
        //---- obtener asignaciones segun fecha ---------------------------------------------------------
        
        function obtener_asignaciones (){
            $anio_lectivo=date('Y', strtotime($this->s__fecha_consulta));
            $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($this->s__fecha_consulta, $anio_lectivo, $this->s__id_sede);            
            
            $this->s__asignaciones=$this->procesar_periodo($periodo, 'hr');
            
            if(count($this->s__asignaciones)==0){
                //$this->s__mostrar_mensaje=TRUE;
                $mensaje="No existen asignaciones registradas en el sistema para el día seleccionado";
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
            }
        }
        
        //---- calculo de horarios disponibles ----------------------------------------------------------
        
        /*
         * Esta funcion dispara el calculo de horarios disponibles para todas las aulas de un 
         * establecimiento.
         */
        function obtener_horarios_disp (){
            
            //el formato de s__fecha_consulta es y-m-d.
            $anio_lectivo=date('Y', strtotime($this->s__fecha_consulta)); 
                        
            $aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($this->s__id_sede);
            
            $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($this->s__fecha_consulta, $anio_lectivo, $this->s__id_sede);
            
            $asignaciones=$this->procesar_periodo($periodo, 'hd');            
                        
            //obtenemos todas las aulas involucradas 
            $aulas=$this->obtener_aulas($asignaciones);
            toba::memoria()->set_dato_instancia(0, $this->s__id_sede);
            $horarios_disponibles=new HorariosDisponibles();
            
            $this->s__horarios_disponibles=$horarios_disponibles->calcular_horarios_disponibles($aulas, $aulas_ua, $asignaciones);
            
            if(count($this->s__horarios_disponibles)==0){
                $mensaje="No existen horarios disponibles para el día seleccionado";
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
            }
            
            toba::memoria()->set_dato_operacion(0, $this->s__fecha_consulta);
            
        }
        
        /*
         * 
         */
        function procesar_periodo ($periodo, $accion){
            //inicializamos las estructuras de datos utilizadas
            $cuatrimestre=array();
            $examen_final=array();
            $periodos=array();
            foreach ($periodo as $clave=>$valor){
                
                switch ($valor['tipo_periodo']){
                    case 'Cuatrimestre' : if(strcmp($accion, 'hd')==0){
                                              $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_cuatrimestre($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
                                          else{
                                              $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_definitivas_por_fecha_cuatrimestre($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo']);
                                              $periodos=$this->dep('datos')->tabla('asignacion')->get_asignaciones_periodo_por_fecha_cuatrimestre($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                              
                                          }
                                          break;
                                      
                    case 'Examen Final' : if(strcmp($accion, 'hd')==0){
                                              //obtenemos todas las asignaciones por periodo, que estan inluidas en un cuatrimestre,
                                              //pero que pertenecen a un examen_final
                                              $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_examen_final($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
                                          else{ //la operacion se cuelga porque esta mal el nombre de la funcion
                                              $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_periodo_por_fecha_examen($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
                                          break;
                }
                
            }
            
            if((count($cuatrimestre)>0) && (count($examen_final)>0)){
                if(strcmp($accion, 'hd')==0){
                    //Debemos iniciar descarte y unificacion.
                    //asig_definitivas = cuatrimestre, asig_periodo = examen final.
                    //$this->descartar_asignaciones_definitivas($examen_final, &$cuatrimestre);

                    $this->unificar_asignaciones(&$examen_final, $cuatrimestre);

                    return $examen_final;
                }
                else{
                    //Debemos unificar periodo con examen final.
                    $this->unificar_asignaciones(&$periodos, $examen_final);
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
                    //Devolvemos cuatrimestre y guardamos en una variable de sesion el resultado obtenido en 
                    //periodo.
                    $this->s__asignaciones_periodo=$periodos;
                    return $cuatrimestre;
                }
            }
            
            if((count($cuatrimestre)==0) && (count($examen_final)>0)){
                if(strcmp($accion, 'hd')==0){
                    //Devolvemos solo examen final.
                    return $examen_final;
                }
                else{
                    $this->s__asignaciones_periodo=$examen_final;
                    return $cuatrimestre;
                }
            }
            
            if((count($cuatrimestre)==0) && (count($examen_final)==0)){
                if(strcmp($accion, 'hr')==0){
                    $this->s__asignaciones_periodo=$periodos;
                }
                
                //Devolvemos vacio.
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
         * devuelve todas las aulas que poseen diponibilidad total de horarios (de 8 a 22 hs)
         */
        function obtener_aulas_con_disponibilidad_total ($aulas, $aulas_ua){
            $aulas_disponibles=array();
            if(count($aulas) == count($aulas_ua)){
                return $aulas_disponibles; //no hay aulas con disponibilidad total
            }
            else{
                foreach ($aulas_ua as $aula){
                    $existe=FALSE;
                    foreach ($aulas as $a){
                        if(strcmp($aula['id_aula'], $a['id_aula']) == 0){
                            $existe=TRUE;
                        }
                    }
                    
                    if(!$existe){
                        $aulas_disponibles[]=$aula;
                    }
                }
                return $aulas_disponibles;
            }
            
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
        
        //-----------------------------------------------------------------------------------------------
        //----Pant Periodo ------------------------------------------------------------------------------
        //-----------------------------------------------------------------------------------------------
        
        function conf__pant_periodo (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab("pant_edicion")->desactivar();
            $this->pantalla()->tab("pant_horarios")->desactivar();
        }
        
        //---- Cuadro Asignaciones Periodo --------------------------------------------------------------
        
        function conf__cuadro_asignaciones_periodo (toba_ei_cuadro $cuadro){
            if(count($this->s__asig_solapadas)>0){
                $cuadro->descolapsar();
                $cuadro->set_titulo("Asignaciones solapadas ");
                $cuadro->set_datos($this->s__asig_solapadas);
            }
            
            $this->s__asig_solapadas=null;
        }
        
        function evt__volver_horarios (){
            $this->set_pantalla("pant_horarios");
        }
        
//        function agregar_capacidad (){
//            $aulas_con_capacidad=$this->dep('datos')->tabla('aula')->get_aulas_mas_capacidad($this->s__id_sede);
//            
//            $longitud=count($this->s__horarios_disponibles);
//            
//            foreach ($aulas_con_capacidad as $clave=>$valor){
//                for($i=0;$i<$longitud;$i++){
//                    $elto=$this->s__horarios_disponibles[$i];
//                    if($valor['id_aula'] == $elto['id_aula']){
//                        $this->s__horarios_disponibles[$i]['capacidad']=$valor['capacidad'];
//                    }                                                            
//                }
//            }
//        }
                
}

?>