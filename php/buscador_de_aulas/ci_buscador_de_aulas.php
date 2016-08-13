<?php

include_once (toba_dir().'/proyectos/rukaja/php/api/HorariosDisponibles.php');

/*
 * En esta operacion debemos garantizar el no-solapamiento de asignaciones. Para evitar el no-solapamiento
 * las asignaciones por periodo se comportan como definitivas, cuando queremos registrar asignaciones 
 * definitivas. 
 */
class ci_buscador_de_aulas extends toba_ci
{
    
        protected $s__horarios_disponibles;
        protected $s__id_sede;
        protected $s__fecha_consulta;
        protected $s__dia_consulta;
        protected $s__asignaciones_periodo;

        //---- Cuadro -----------------------------------------------------------------------
        
        /*
         * Debemos filtrar las aulas segun el horario especificado por el usuario en el formulario 
         * form_asignacion. Para ello usamos ajax, en sesion guardamos el periodo seleccionado por el usuario
         * junto con la hora de inicio y fin, el dia o la lista de dias y el tipo de asignacion.
         */
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
            //Obtenemos la sede a la que pertenece el usuario logueado.
            $nombre_usuario=toba::usuario()->get_id();
            $id_sede=$this->dep('datos')->tabla('persona')->get_sede_para_usuario_logueado($nombre_usuario);
            
            //Obtenemos los datos almacenados en la sesion. Esta informacion se guarda en el metodo
            //ajax__guardar_estado_sesion.
            $tipo=toba::memoria()->get_dato_instancia(4);
            
            //Con esta instruccion evitamos enviar una clave aleatoria al popup.
            $cuadro->desactivar_modo_clave_segura();
            $hora_inicio=toba::memoria()->get_dato_instancia(1);
            $hora_fin=toba::memoria()->get_dato_instancia(2);
            //print_r($tipo);print_r($hora_inicio);print_r($hora_fin);
            switch($tipo){
                case 'Definitiva' : //array( 1 => hora_inicio, 2 => hora_fin, 3 => id_periodo, 
                                    // 4 => tipo, 5 => $dia).                                    
                                    $id_periodo=toba::memoria()->get_dato_instancia(3);
                                    $dia=toba::memoria()->get_dato_instancia(5);
                                    toba::memoria()->set_dato_instancia(0, $id_sede);
                                    //print_r($id_periodo);print_r($dia);
                                    $aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($id_sede);
                                    $asignaciones=$this->dep('datos')->tabla('asignacion')->get_asignaciones_definitivas_por_dia($id_sede, $dia, $id_periodo);
                                    
                                    //Obtenemos las aulas que estan siendo utilizadas para el dia $dia.
                                    $aulas=$this->obtener_aulas($asignaciones);
                                    
                                    $fechas=$this->dep('datos')->tabla('periodo')->get_fechas_cuatrimestre($id_sede, $id_periodo);
                                    $examenes_ordinarios=$this->dep('datos')->tabla('periodo')->get_examenes_ordinarios($fechas[0]['fecha_inicio'], $fechas[1]['fecha_fin']);
                                    
                                    
                                    $asig_per=$this->dep('datos')->tabla('asignacion')->get_asignaciones_periodo_cuatrimestre($id_sede, $dia, $id_periodo);
                                    
                                    foreach ($examenes_ordinarios as $clave=>$examen){
                                        $asig_ef[]=$this->dep('datos')->tabla('asignacion')->get_asignaciones_examen_final_($id_sede, $dia, $examen['id_periodo']);
                                    }
                                    
                                    $this->unificar_asignaciones(&$asig_per, $asig_ef);
                                    $fechas=$this->extraer_fechas($asig_per);
                                    
                                    //$asignaciones_periodo=$this->dep('datos')->tabla('asignacion')->get_asignaciones_periodo_($id_sede, $dia);
                                    //$this->agregar_asignaciones_periodo(&$asignaciones, $asignaciones_periodo);
                                    
                                    //print_r("<br><br>");print_r($asignaciones);
                                    //print_r("<br><br>");print_r($aulas);
                                    $horarios_disponibles=new HorariosDisponibles();
                                    $horarios_disponibles_por_aula=$horarios_disponibles->calcular_horarios_disponibles($aulas, $aulas_ua, $asignaciones);
                                    //print_r("<br><br>");
                                    //print_r($horarios_disponibles_por_aula);
                                    $cuadro->set_titulo(utf8_decode("Asignación Definitiva"));
                                    $cuadro->set_datos($this->extraer_aulas_disponibles($horarios_disponibles_por_aula, "$hora_inicio:00", "$hora_fin:00"));
                                    
                                    break;
                                
                case 'Periodo' :  $cuadro->set_titulo(utf8_decode("Asignación por Periodo"));
                                  //$cuadro->set_datos($this->dep('datos')->tabla('aula')->get_aulas_por_sede($id_sede));
                                  //array( 1 => hora_inicio, 2 => hora_fin, 3 => id_periodo, 
                                  // 4 => tipo, 5 => $dia, 6 => fecha_inicio, 7 => fecha_fin).
                                  
                                  $lista_dias=array();
                                  for($i=8; $i<=13; $i++){
                                      $dia=toba::memoria()->get_dato_instancia($i);
                                      if(strcmp($dia, 'undefined') != 0){
                                          $lista_dias[]=$dia;
                                      }
                                  }
                                  
                                  //print_r($lista_dias);
                                  $fecha_inicio=toba::memoria()->get_dato_instancia(6);
                                  
                                  print_r($fecha_inicio);print_r("<br><br>");
                                                                    
                                  $fecha_fin=toba::memoria()->get_dato_instancia(7);
                                  print_r($fecha_fin);print_r("<br><br>");
                                                                    
                                  toba::memoria()->set_dato_instancia(0, $id_sede);
                                  $this->s__id_sede=$id_sede;
                                  $hd=new HorariosDisponibles();
                                  toba::memoria()->limpiar_datos_instancia();
                                  //lista_dias = array( 0 => 'Lunes', ....., 5 => 'Sábado' ).
                                  $lista_fechas=$hd->get_dias($fecha_inicio, $fecha_fin, $lista_dias);
                                  //print_r($lista_fechas);exit();
                                  
                                  //El formato de hd_fechas es:
                                  //array( 0 => array( 0 => fecha, 1 => hd) ). A su vez cada hd es:
                                  //array( 0 => array( hora_inicio, hora_fin, aula, id_aula, capacidad) ).
                                  $hd_fechas=$this->horarios_disponibles_por_fecha($lista_fechas);
                                  //print_r($hd_fechas[0]);//exit();
                                  
                                  //Obtenemos las aulas de la unidad academica en cuestion.
                                  //Y no va a quedar otra que ponerse a recorren todo el arreglo, en busca de un aula
                                  //que este disponible todos los dias del periodo en ese horario. La complejidad 
                                  //va aumentar a partir de la longitud del periodo.
                                  $aulas=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($id_sede);
                                  
                                  $aulas_disponibles=$this->extraer_aulas_disponibles_para_periodo($aulas, $hd_fechas, "$hora_inicio:00", "$hora_fin:00", count($lista_fechas));
                                  print_r($aulas_disponibles);
                                  $cuadro->set_datos($aulas_disponibles);
                                  print_r("<br><br> Longitudes de lista_fechas y hd_fechas, respectivamente: <br><br>");
                                  print_r(count($lista_fechas));print_r("<br><br>");
                                  print_r(count($hd_fechas));
                                  break;
            }
            
            //$cuadro->set_datos($this->dep('datos')->tabla('aula')->get_aulas($id_sede));
            
            
            toba::memoria()->limpiar_datos_instancia();
            
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
         * Verifica si un aula ya se encuentra presente en la estructura aulas.
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
         * Esta funcion permite obtener las aulas que poseen un horario disponible que incluye a hora_inicio 
         * y hora_fin.
         */
        function extraer_aulas_disponibles ($horarios_disponibles, $hora_inicio, $hora_fin){
            $aulas_disponibles=array();
            foreach ($horarios_disponibles as $clave=>$valor){
                
                $hora_inicio_disp=$valor['hora_inicio'];
                $hora_fin_disp=$valor['hora_fin'];
                
                if((($hora_inicio >= $hora_inicio_disp) && ($hora_inicio <= $hora_fin_disp)) && ($hora_fin <= $hora_fin_disp)){
                    $aulas_disponibles[]=$valor;
                }
            }
            
            return $aulas_disponibles;
        }
        
        /*
         * Si se unifican dos consultas del datos_tabla asignbacion se debe eliminar
         */
        function unificar_asignaciones ($asignaciones, $asignaciones_periodo){
            foreach ($asignaciones_periodo as $clave=>$periodo){
                $asignaciones[]=$periodo;
            }
        }
        
        function extraer_fechas ($asignaciones){
            $fechas=array();
            foreach ($asignaciones as $clave=>$asignacion){
                $fecha=$asignacion['fecha'];
                if(!$this->existe_fecha($fecha, $fechas)){
                    $fechas[]=$fecha;
                }
            }
            
            return $fechas;
        }
        
        /*
         * Si fecha se encuentra incluida en $lista_fechas devolvemos true.
         */
        function existe_fecha ($fecha, $lista_fechas){
            $i=0;
            $n=count($lista_fechas);
            $fin=FALSE;
            while($i<$n && !$fin){
                if(strcmp($fecha, $lista_fechas[$i])==0){
                    $fin=TRUE;
                }
                $i++;
            }
            
            return $fin;
        }
        
        //-------------------------------------------------------------------------------------------
        //---- Interfaz para periodos ---------------------------------------------------------------
        //-------------------------------------------------------------------------------------------
        
        /*
         * @$lista_fechas : el formato de esta estructura es : 
         * array( 0 => array(id_solicitud, fecha, nombre) ).
         */
        function horarios_disponibles_por_fecha ($lista_fechas){
            //Obtenemos las aulas una unica vez.
            $aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($this->s__id_sede);
            $hd_fecha=array();
            
            foreach($lista_fechas as $clave=>$fecha){
                //En la operacion Ver Solicitudes, la lista de fechas surge de un aconsulta en la base de datos.
                //Y su formato es : array( 0 => array( 'fecha' => f1, ...., )). Por eso debemos indexarla usando 
                //$fecha['fecha']. Pero aqui esto ultimo no es necesario, pues el formato de lista-fechas es:
                //array( 0 => 'f1', 1 => 'f2', ..., n => 'fn')
                //$this->s__fecha_consulta=$fecha['fecha'];
                $this->s__fecha_consulta=$fecha;
                $this->hd_multi_evento($aulas_ua);
                
                //0 => fecha, 1 => s__horarios_disponibles.
                $hd_fecha[]=array($fecha , $this->s__horarios_disponibles);
                
                //Limpiamos el arreglo para no acumular resultados.
                $this->s__horarios_disponibles=array();
            }
            
            return $hd_fecha;
                      
        }
        
        /*
         * Esta funcion calcula horarios disponibles para cada fecha del periodo. Agrupa la funciones que 
         * empiezan el calculo de hd desde el controlador.
         */
        function hd_multi_evento ($aulas_ua){
            
            $anio_lectivo=date('Y', strtotime($this->s__fecha_consulta));
            //Configuramos el dia de consulta para que este disponible en la funcion procesar_periodo.
            $this->s__dia_consulta=$this->obtener_dia(date('N', strtotime($this->s__fecha_consulta)));
            //print_r($this->s__datos_solcitud);
            //Obtenemos los periodos que pueden contener a la fecha de solicitud.
            $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($this->s__fecha_consulta, $anio_lectivo, $this->s__id_sede);
            //Usamos la cadena 'au' para extraer las asignaciones pertenecientes a un aula en particular. 
            //Es una condicion mas dentro de la funcion procesar_periodo.
            $asignaciones=$this->procesar_periodo($periodo, 'hd');
            
            $aulas=$this->obtener_aulas($asignaciones);
            //Guardamos en sesion el id_sede para agregar la capacidad de cada aula a un horario disponible.
            toba::memoria()->set_dato_instancia(0, $this->s__id_sede);
            
            $hd=new HorariosDisponibles();
            
            $this->s__horarios_disponibles=$hd->calcular_horarios_disponibles($aulas, $aulas_ua, $asignaciones);
                      
        }
        
        /*
         * Aqui agregamos toda la interfaz apropiada para devolver las asignaciones para una fecha en particular.
         * Si recibimos en la variable accion:
         * au : debemos obtener las asignaciones pertenecientes a un aula.
         * hd : obtenemos las asignaciones para calcular horarios disponibles en todas las aulas de un 
         *      establecimiento.
         * 
         * Esta funcion se copio desde la operacion Calendario Comahue, por lo tanto hay cosas que se pueden 
         * sacar definitivamente, como lo que esta comentado.
         */
        function procesar_periodo ($periodo, $accion){
            $cuatrimestre=array();
            $examen_final=array();
            foreach ($periodo as $clave=>$valor){
                
                switch ($valor['tipo_periodo']){
                    case 'Cuatrimestre' : if(strcmp($accion, 'hd')==0){
                                              //Obtenemos asignaciones definitivas y periodicas para empezar calculos de horarios
                                              //disponibles en todas las aulas de un establecimiento.
                                              $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_cuatrimestre($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                              
                                          }
                                          else{ //Si accion es 'au'.
                                              //Esta consulta nos permite obtener asignaciones definitivas o periodicas en un aula y fecha en particular.
                                              $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_por_aula_cuatrimestre(utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta, $this->s__datos_solcitud['id_aula']);
                                              //print_r($cuatrimestre);
                                          }
                                          break;
                                      
                    case 'Examen Final' : if(strcmp($accion, 'hd')==0){
                                              //Obtenemos todas las asignaciones por periodo, que estan inluidas en un cuatrimestre,
                                              //pero que pertenecen a un examen_final
                                              $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_examen_final($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
                                          else{ //Si accion es 'au'.
                                              //Obtenemos asignaciones periodicas que pertenecen a un turno de examen de un aula especifica.
                                              $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_por_aula_examen_final(utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta, $this->s__datos_solcitud['id_aula']);
                                          }
                                          break;
                }
                
            }
            
            if((count($cuatrimestre)>0) && (count($examen_final)>0)){
                if(strcmp($accion, 'hd')==0 || strcmp($accion, 'au')==0){
                    //Debemos iniciar descarte y unificacion
                    //asig_definitivas & periodicas = cuatrimestre, asig_periodo = examen final.
                    //En teoria no hay solapamiento, por lo tanto esto es redundante.
                    //$this->descartar_asignaciones_definitivas($examen_final, &$cuatrimestre);
                    
                    //Las asignaciones periodicas para examen final tienen prioridad.
                    $this->unificar_asignaciones(&$examen_final, $cuatrimestre);

                    return $examen_final;
                }
//                else{
//                    //Debemos unificar periodo con examen final.
//                    $this->unificar_asignaciones(&$periodo, $examen_final);
//                    $this->s__asignaciones_periodo=$periodo;
//                    return $cuatrimestre;
//                }
            }
            
            if((count($cuatrimestre)>0) && (count($examen_final)==0)){
                if(strcmp($accion, 'hd')==0 || strcmp($accion, 'au')==0){
                    //devolvemos solo cuatrimestre
                    return $cuatrimestre;
                }
//                else{
//                    //devolvemos cuatrimestre y guardamos en una variable de sesion el resultado obtenido en 
//                    //periodo
//                    $this->s__asignaciones_periodo=$periodo;
//                    return $cuatrimestre;
//                }
            }
            
            if((count($cuatrimestre)==0) && (count($examen_final)>0)){
                if(strcmp($accion, 'hd')==0 || strcmp($accion, 'au')==0){
                    //devolvemos solo examen final
                    return $examen_final;
                }
//                else{
//                    $this->s__asignaciones_periodo=$examen_final;
//                    return $cuatrimestre;
//                }
            }
            
            if((count($cuatrimestre)==0) && (count($examen_final)==0)){
//                if(strcmp($accion, 'hr')==0){
//                    $this->s__asignaciones_periodo=$periodo;
//                }
                 
                //devolvemos vacio
                return array();
                               
            }
        }
        
        /*
         * @$dia_numerico: contiene un numero entre 1 y 6. Se obtiene a partir de la funcion date de php.
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
                        
            return $dias[$dia_numerico];
        }
        
        /*
         * Esta funcion verifica si el multi-evento se puede conceder, esto es ver si en cada fecha existe el 
         * horario disponible especificado en la solicitud.
         */
        function existe_hd_para_periodo ($hd_fechas, $id_aula, $hora_inicio, $hora_fin){
            $i=0;
            $j=0;
            $n=count($hd_fechas);
            $fin=TRUE;
            $fin_hd=FALSE;
            while($i<$n && $fin){
                $hd=$hd_fechas[$i];
                //Guardamos la longitud del arreglo que contiene todos los hd para una fecha.
                $m=count($hd[1]);
                $horarios=$hd[1];
                while($j<$m && !$fin_hd){
                    $aula=$horarios[$j];
                    if($aula['id_aula']==$id_aula && ($hora_inicio>=$aula['hora_inicio'] && $hora_inicio<=$aula['hora_fin'] && $hora_fin<=$aula['hora_fin'])){
                        $fin_hd=TRUE;
                    }
                    $j++;
                }
                
                $fin=($fin_hd) ? TRUE : FALSE ;
                $j=0;
                $i++;
            }
            
            return $fin;
        }
        
        /*
         * @$longitud: representa la cantidad de fechas.
         */
        function extraer_aulas_disponibles_para_periodo($aulas, $hd_fechas, $hora_inicio, $hora_fin, $longitud){
            //Significa que para al menos una fecha no hay horarios disponibles en el sistema. Esto puede ocurrir
            //si un aula esta ocupada todo el dia. Entonces no se otorgaran aulas para satisfacer parte del 
            //periodo. Esto es poco probable, pero igual se contempla.
            $n=count($hd_fechas);
            if($n < $longitud){
                return array();
            }           
            
            $disponibilidad=0;
            $aulas_disponibles=array();
            foreach ($aulas as $clave=>$aula){
                $i=0;
                
                //Recorremos hd_fechas.
                while($i<$n){
                    //Obtenemos los hd para una fecha del periodo
                    $hd=$hd_fechas[$i][1];
                    $fin=FALSE;
                    $m=count($hd);
                    $j=0;
                    //Verificamos si un aula se encuentra disponible para un horario en particular.
                    //Recorremos hd. Siempre recorremos lo estrictamente necesario para mejorar un poco 
                    //la eficiencia del algortimo.
                    while($j<$m && !$fin){
                                                
                        if($this->existe_($hd[$j], $aula, $hora_inicio, $hora_fin)){
                            $disponibilidad++;
                            $fin=TRUE;
                        }
                        $j++;
                    }
                    $i++;
                }
                
                if($disponibilidad == $longitud){
                    $aulas_disponibles[]=$aula;
                }
                
                $disponibilidad=0;
                
            }
            
            return $aulas_disponibles;
        }
        
        function existe_($horario, $aula, $hora_inicio, $hora_fin){
            return (($horario['id_aula']==$aula['id_aula']) && 
                   (($hora_inicio >= $horario['hora_inicio'] && $hora_inicio <= $horario['hora_fin']) &&
                   ($hora_fin <= $horario['hora_fin'])));
        }

}

?>