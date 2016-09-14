<?php
class ci_memorando_por_fecha extends toba_ci
{
        protected $s__fecha_consulta;
        protected $s__fecha_reporte;
        protected $s__id_sede;
        protected $s__dia_consulta;
        protected $s__asignaciones;
        protected $s__imagenes=array('');

        //---- Pant Edicion -----------------------------------------------------------------------------
    
        //---- Calendario -------------------------------------------------------------------------------
    
        function conf__calendario (toba_ei_calendario $calendario){
            
            $this->s__id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
                        
            $this->pantalla()->tab('pant_memorando')->desactivar();
            $calendario->set_seleccionar_solo_dias_pasados(FALSE);
            $calendario->set_sab_seleccionable(TRUE);
            $calendario->set_dom_seleccionable(TRUE);
            $calendario->set_ver_contenidos(TRUE);
        }
        
        function evt__calendario__seleccionar_dia ($seleccion){
            //Verificamos si existen aulas registradas en el sistema.
            $aulas=$this->dep('datos')->tabla('aula')->get_aulas($this->s__id_sede);
            if(count($aulas)==0){
                $mensaje=" No existen aulas registradas en el sistema. ";
                toba::notificacion()->agregar($mensaje, 'info');
                return ;
            }
            
            $this->s__fecha_consulta="{$seleccion['anio']}-{$seleccion['mes']}-{$seleccion['dia']}";
            $this->s__fecha_reporte="{$seleccion['dia']}-{$seleccion['mes']}-{$seleccion['anio']}";
            
            $dia_numerico=date('N', strtotime($this->s__fecha_consulta));
            $this->s__dia_consulta=$this->obtener_dia($dia_numerico);
            
            $anio_lectivo=date('Y', strtotime($this->s__fecha_consulta));
            $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($this->s__fecha_consulta, $anio_lectivo, $this->s__id_sede);
            
            if(count($periodo)==0){
                $mensaje=" No existen períodos académicos registrados en el sistema para el año actual ";
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
            }
            else{
                
                //Obtenemos las asignaciones por periodo para la fecha seleccionada.
                $this->s__asignaciones=$this->procesar_periodo($periodo);
                if(count($this->s__asignaciones)==0){
                    toba::notificacion()->agregar(" No existen asignaciones por periodo registradas en el sistema para la fecha {$this->s__fecha_reporte} ", 'info');
                }
                else{
                    $this->set_pantalla('pant_memorando');
                }
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
                       
            return ($dias[$dia_numerico]);
        }
        
        function obtener_mes ($mes_numerico){
            $meses=array(
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            );
                       
            return ($meses[$mes_numerico]);
        }
        
        //-----------------------------------------------------------------------------------------------
        //---- Pant Memorando ---------------------------------------------------------------------------
        //-----------------------------------------------------------------------------------------------
        
        function conf__pant_memorando (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab('pant_edicion')->desactivar();
        }
        
        function conf__formulario (toba_ei_formulario $form){
            $form->ef('fecha')->set_estado($this->s__fecha_reporte);
            $dia_numerico=date('N', strtotime($this->s__fecha_consulta));
            $dia=$this->obtener_dia($dia_numerico);
            $form->ef('dia')->set_estado(utf8_decode($dia));
        }
                       
        function vista_pdf (toba_vista_pdf $salida){
            ob_end_clean();
            $dia_numerico=  date('N', strtotime($this->s__fecha_consulta));
            $dia=$this->obtener_dia($dia_numerico);
            
            $mes_numerico=  date('n', strtotime($this->s__fecha_consulta));
            $mes=$this->obtener_mes($mes_numerico);
            
            $dia_del_mes=date('j', strtotime($this->s__fecha_consulta));
            $anio=  date('Y', strtotime($this->s__fecha_consulta));
            
            $salida->set_nombre_archivo(utf8_d_seguro("Actividades para el día {$this->s__fecha_reporte}.pdf"));
            $pdf=$salida->get_pdf();
            
            $pdf->ezSetMargins(58, 30, 35, 35);
            $pdf->addJpegFromFile(toba_dir().'/proyectos/rukaja/www/img/encabezado_reporte.jpg', 35, 785, 260, 58);
            
            $alineacion_der=array('justification'=>'right');
            $alineacion_centro=array('justification'=>'center');
            
            $titulo="MEMORANDO   ............./............. \n\n";
            $pdf->ezText($titulo, 8, $alineacion_centro);
            
            $ua=$this->dep('datos')->tabla('unidad_academica')->get_unidad_academica($this->s__id_sede);
            $establecimiento=$ua[0]['establecimiento'];
            
            $inicio=  utf8_decode("Producido por : Dirección de ");
            $pdf->ezText(utf8_d_seguro($inicio.$establecimiento."\n\n"), 8);
            
            $finalidad="Para Información de : Área de Serenos \n\n";
            $pdf->ezText(utf8_d_seguro($finalidad), 8);
            
            $asunto="Asunto : Dictado del día $dia {$this->s__fecha_reporte} \n\n";
            $pdf->ezText(utf8_d_seguro($asunto), 8);
            
            //Es otra variante para obtener la fecha actual.
            $fecha=getdate();
            $mes_actual=$this->obtener_mes($fecha['mon']);
            $fecha_actual="Neuquén, {$fecha['mday']} de $mes_actual de {$fecha['year']} \n";
            $pdf->ezText(utf8_d_seguro($fecha_actual), 8, $alineacion_der);
            
            $cadena=$this->generar_cadena(196);
            $pdf->ezText($cadena, 8);
            
            $descripcion="\n Por el presente informo las actividades que se desarrollarán el día $dia $dia_del_mes de $mes de $anio, a continuación se detallan. \n\n";
            
            $pdf->ezText(utf8_d_seguro($descripcion), 8, $alineacion_der);
            
            $this->agregar_tabla($pdf);
            
        }
        
        function agregar_tabla (Cezpdf $pdf){
            //definimos el formato de la tabla 
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
            
            //Definimos las columnas de la tabla.
            $columnas=array(
                'finalidad' => 'Materia/Curso/Evento',
                'hora_inicio' => 'Hora Inicio',
                'hora_fin' => 'Hora Fin',
                'aula' => 'Aula',
                'facultad' => 'Facultad',
                'responsable' => 'Responsable de Aula',
                'catedra' => 'Docentes Sustitutos'
            );
                        
            $this->agregar_catedra($this->s__asignaciones);                                    
            
            $pdf->ezTable($this->s__asignaciones, $columnas, " ", $opciones);
            
        }
        
        /*
         * Esta funcion obtiene el equipo de catedra perteneciente a una asignacion periodica.
         */
        function agregar_catedra ($asignaciones){
            $i=0;
            $n=count($asignaciones);
            while ($i < $n){
                $id_asignacion=$asignaciones[$i]['id_asignacion'];
                
                $equipo_catedra=$this->dep('datos')->tabla('persona')->get_equipo_de_catedra($id_asignacion);
                
                $catedra=$this->toString($equipo_catedra);
                
                $this->s__asignaciones[$i]['catedra']=$catedra;
                
                $i++;
            }
        }
        
        function toString ($equipo_catedra){
            $i=0;
            $n=count($equipo_catedra);
            $separador=" ; ";
            $cadena=" ";
            while($i < $n){
                if($i != ($n - 1))
                    $cadena .= (($equipo_catedra[$i]['miembro']) . $separador);
                else
                    $cadena .= ($equipo_catedra[$i]['miembro']);
                
                $i++;
            }
            
            return $cadena;
        }
        
        function generar_cadena ($cantidad_caracteres){
            $cadena="-";
            $i=0;
            
            while($i < $cantidad_caracteres){
                $cadena .= "-";
                $i += 1;
            }
            
            return $cadena;
        }
        
        function evt__volver (){
           $this->set_pantalla('pant_edicion'); 
        }
        
        //------------------------------------------------------------------------------------------------
        //---- Funcion Procesar Periodo ------------------------------------------------------------------
        //------------------------------------------------------------------------------------------------
        
        /*
         * Una fecha puede estar incluida en dos periodos academicos distintos. Esto ocurre cuando seleccionamos
         * una fecha que esta dentro de un turno de examen, que a su vez esta incluido en un cuatrimestre.
         */
        function procesar_periodo ($periodo){
            $cuatrimestre=array();
            $examen_final=array();
            foreach ($periodo as $clave=>$valor){
                
                switch ($valor['tipo_periodo']){
                    case 'Cuatrimestre' : $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_memo_por_cuatrimestre(utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__id_sede, $this->s__fecha_consulta);
                                          
                                          break;
                                      
                    case 'Examen Final' : //Obtenemos todas las asignaciones por periodo, que estan inluidas en un cuatrimestre,
                                          //pero que pertenecen a un examen_final
                                          $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_memo_por_examen_final(utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__id_sede, $this->s__fecha_consulta);
                                          
                                          break;
                }
                
            }
            
            if((count($cuatrimestre)>0) && (count($examen_final)>0)){
                
                    //debemos iniciar descarte y unificacion
                    //asig_definitivas = cuatrimestre, asig_periodo = examen final.
                    $this->descartar_asignaciones_definitivas($examen_final, &$cuatrimestre);

                    $this->unificar_asignaciones(&$examen_final, $cuatrimestre);

                    return $examen_final;
                
                
            }
            
            if((count($cuatrimestre)>0) && (count($examen_final)==0)){
                
                    //devolvemos solo cuatrimestre
                    return $cuatrimestre;
                
                
            }
            
            if((count($cuatrimestre)==0) && (count($examen_final)>0)){
                
                    //devolvemos solo examen final
                    return $examen_final;
                
            }
            
            if((count($cuatrimestre)==0) && (count($examen_final)==0)){
                                
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