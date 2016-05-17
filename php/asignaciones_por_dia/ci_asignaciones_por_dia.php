<?php

agregar_dir_include_path(toba_dir().'/php/3ros/phpExcel');
require_once('PHPExcel.php');

class ci_asignaciones_por_dia extends toba_ci
{
        protected $s__posicion_aula;                      // ( id_aula, fila )
        protected $s__hora;                               //guarda la siguiente informacion (indice => hora) donde indice : 0, 1, 2, 3,  ... y hora : 08:00, 08:30, ... 
        protected $s__posicion_hora;                      //guarda la siguiente informacion (hora => columna) donde hora: 08:00, 08:30, 09:00 ... y columna : Bx, Cx, Dx, Ex, ... con x=s__contador_merge
        protected $s__columnas=array(
            'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI' 
        );                                               //contiene todas las columnas utilizadas en el reporte 34
        protected $s__merge;
        protected $s__contador_merge=2;                  //Empieza en 2 y despues se actualiza a medida que completamos el reporte
        protected $s__celdas_combinadas=array();         //Guarda pares de celdas combinadas, por ejemplo AB para cargar el horarios en la primer fila
        protected $s__aulas_ua;                          //Guarda las aulas de una unidad academica.
        protected $s__color=array(
                'FAI' => 'FF012B', 'FAIN'=>'FFA33B', 'FAEA'=>'4EFC4E', 'FAHU'=>'CDC7CC', 'FATU'=>'FD3AE0', 
                'FACIAS' => '6DE5FD', 'SS'=> 'C00222'
            );                             //guarda la siguiente informacion (sigla_facultad => color) donde color es una expresion hexadecimal
        
        
        protected $s__temp_salida;
        protected $s__asignaciones;
        
        protected $s__datos;                               //guarda la informacion especificada en el formulario de la pantalla pant_edicion
        protected $s__hoja_activa;                         //contiene el indice de la hoja activa
        protected $s__dia;                                 


        //---- Pant Edicion -----------------------------------------------------------------
	//---- Formulario -------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
            
        }

	function evt__formulario__alta($datos)
	{
            
            //creamos un objeto excel
            //$excel=new PHPExcel();
            $this->s__datos=$datos;
            $this->s__hoja_activa=0;
            //print_r($datos);
            //$this->iniciar_proceso($datos, $excel);
            $this->set_pantalla('pant_reporte');
            //creamos un archivo temporal, con el objeto phpexcel ya configurado
            //$this->crear_archivo_temporal($excel);
            
            //enviamos el reporte al cliente
            //$this->enviar_archivo();
            
	}
        
        function conf__form (toba_ei_formulario $form){
            $form->ef('cuatrimestre')->set_estado($this->s__datos['cuatrimestre']);
            $form->ef('anio_lectivo')->set_estado($this->s__datos['anio_lectivo']);
            
            $solo_lectura=array('cuatrimestre', 'anio_lectivo');
            
            $form->set_solo_lectura($solo_lectura);
        }
        
        /*
         * iniciar proceso se cambia por vista_excel
         */
        function vista_excel (toba_vista_excel $salida){
            //procesamos los dias seleccionados. Esto implica modificar en excel.
            $excel=$salida->get_excel();
            $dias=$this->s__datos['dia'];
            $anio_lectivo=$this->s__datos['anio_lectivo'];
            $cuatrimestre=(strcmp('Primer Cuatrimestre', $this->s__datos['cuatrimestre'])==0) ? 1 : 2;
            $id_periodo=$this->dep('datos')->tabla('periodo')->get_id_periodo($cuatrimestre, $anio_lectivo);
            if(!isset($id_periodo)){
                $mensaje="No existe un período académico registrado en el sistema para {$this->s__datos['cuatrimestre']} $anio_lectivo ";
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
            }
            else{
                $sin_asignaciones=FALSE;
                $dias_sin_asig="";
                foreach ($dias as $dia){
                    //obtenemos las asignaciones para armar el reporte.
                    $asignaciones=$this->dep('datos')->tabla('asignacion')->get_asignaciones_por_dia($id_periodo['id_periodo'], $dia);                
                    if(count($asignaciones)>0){
                        //configuramos el excel para agregar las asignaciones pertenecientes a dia. Al finalizar esta 
                        //rutina la variable s__contador_merge queda ubicada en el lugar exacto para empezar un nuevo
                        //reporte para otro dia seleccionado en el multi_seleccion_check.
                        $this->s__asignaciones=$asignaciones;
                        $this->s__dia=$dia;
                        $this->generar_reporte($excel);
                        
                        
                    }
                    else{
                        $sin_asignaciones=TRUE;
                        $dias_sin_asig .= $dia.", ";
                    }
                    $excel->createSheet();
                    $this->s__hoja_activa += 1;
                    //reseteamos el contador_merge para empezar el reporte en la misma posicion pero en otra
                    //hoja de calculo.
                    $this->s__contador_merge=2;
                }
                
                if($sin_asignaciones){
                    $mensaje="No existen asignaciones registradas en el sistema para los dias $dias_sin_asig";
                }
            }
            
            $salida->set_nombre_archivo("Asignaciones {$this->s__datos['cuatrimestre']} $anio_lectivo.xls");
        }
        
        /*
         * Esta funcion crea un archivo temporal a partir de un objeto PHPExcel.
         */
        function crear_archivo_temporal (PHPExcel $excel){
//            $clase='PHPExcel_Writer_Excel5';
//            $archivo = explode('_', $clase);
//            $archivo = implode('/', $archivo).'.php';
            //incluimos la clase que nos permite escribir el excel un archivo temporal
            require_once('3ros/phpExcel/PHPExcel/Writer/Excel5.php');
            //require_once('3ros/phpExcel/PHPExcel/IOFactory.php');
            $writer = new PHPExcel_Writer_Excel5($excel);
            //guardamos el path del archivo temporal, C:/......./mi_proyecto/temp/ concatenado con el 
            //nombre del archivo.
            $this->s__temp_salida = toba::proyecto()->get_path_temp().'/'.uniqid();
            //crea el archivo temporal que contiene el excel 
            $writer->save($this->s__temp_salida);
        }
        
        /*
         * Esta funcion envia el reporte creado al cliente.
         */
        function enviar_archivo (){
            $longitud = filesize($this->s__temp_salida);
            if (file_exists($this->s__temp_salida)) {
                    //obtenemos el file pointer del archivo temporal donde esta el reporte
                    $fp = fopen($this->s__temp_salida, 'r');
                    $this->cabecera_http($longitud);
                    fpassthru($fp);
                    fclose($fp);
                    unlink($this->s__temp_salida);
            }
            else{
                toba::notificacion()->agregar("No existe un archivo temporal fuente", $nivel);
            }
        }
        
        /*
         * Esta funcion envia los headers necesarios al cliente para que se pueda concretar la transferencia
         * de datos server-client.
         */
        function cabecera_http ($longitud){
            header("Cache-Control: private");
            header('Content-type: application/vnd.ms-excel');
            header("Content-Length: $longitud");	
            header("Content-Disposition: attachment; filename=\"{$this->s__nombre_archivo}\"");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
                
        function cargar_aulas_ua (){
            $this->s__posicion_aula =array();
            $nombre_usuario=toba::usuario()->get_id();
            $id_sede=$this->dep('datos')->tabla('persona')->get_sede_para_usuario_logueado($nombre_usuario);
            $id_sede=1;
            $aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($id_sede);
            $this->s__aulas_ua=$aulas_ua;
        }
        
        function cargar_hora (){
            //guardamos informacion con el siguiente formato (indice => hora) 08:00, 08:30, 09:00 , .... 24:00
            $this->s__hora=array();
            $hora=8;
            $inicio=0;
            $fin=24;
            while ($hora <= $fin){
                $prefijo=($hora <= 9) ? "0".$hora : $hora ;
                $valor="$prefijo:00:00";
                $this->s__hora[$inicio]=$valor;
                if($hora < $fin){
                    $inicio += 1;
                    $valor="$prefijo:30:00";
                    $this->s__hora[$inicio]=$valor;
                }
                $inicio += 1; //hay que hacer un incremento as porque sino se pisan los horarios pares
                $hora++;
            }
        }
        
        /*
         * Esta funcion le agrega al arreglo merge el string adecuado para combinar celdas.
         * Ademas guarda en el arreglo s__celdas_combinadas el string que especifica que celdas se combinaron.
         */
        function cargar_merge (){
            $this->s__merge=array();
            $longitud=count($this->s__columnas)-2;
            $indice=0;
            $contador=1;
            while ($indice <= $longitud){
                $columna_par=$this->s__columnas[$indice];
                $columna_impar=$this->s__columnas[$contador];
                
                $merge="$columna_par{$this->s__contador_merge}:$columna_impar{$this->s__contador_merge}";
                                
                $indice=$indice + 2;
                $contador=$contador + 2;
                $this->s__merge[]=$merge;               
            }
            
        }
        
        /*
         * Esta funcion ejecuta las rutinas necesarias para crear el formato del reporte, cargar las estructuras
         * de datos usadas y agregar informacion en el excel.
         */
        function generar_reporte (PHPExcel $excel){
            //cerramos el buffer de php para no dañar el archivo enviado
            ob_end_clean();
            
            //establecemos la primer hoja del excel como activa
            $excel->setActiveSheetIndex($this->s__hoja_activa);
            $excel->getActiveSheet()->setTitle("{$this->s__dia}");
            
                                    
            $this->crear_formato_reporte($excel);
            $this->cargar_aulas_ua();
            $this->llenar_segmentos($excel);
            $this->completar_reporte($excel);
        }
        
        /*
         * Esta funcion crea el formato principal del reporte. Establece la longitud de las celdas y las 
         * combina.
         */
        function crear_formato_reporte (PHPExcel $excel){
            //agregamos al arreglo merge las columnas que debemos combinar
            $this->cargar_merge();
            $excel->getActiveSheet()->getColumnDimension('A')->setWidth(10.50);
            
            //establecemos el ancho de las columnas B, C, D, E, ....... AI
            foreach ($this->s__columnas as $clave=>$valor){
                $excel->getActiveSheet()->getColumnDimension($valor)->setWidth(6,29);
            }
            
            //una vez que tenemos configurado el ancho de las columnas, realizamos las combinaciones 
            //correspondientes a partir del arreglo s__merge
            foreach ($this->s__merge as $clave=>$valor){
                $excel->getActiveSheet()->mergeCells($valor);
            }
            
        }
        
        /*
         * Esta funcion permite llenar la primer fila de reporte con el rango de horarios 08 a 24 hs, la
         * segunda fila con el texto 00 o 30 y la tercer fila con las auas de la UA.
         */
        function llenar_segmentos (PHPExcel $excel){
            //llenamos la primer fila del reporte, cuando el s__contador_merge esta en 2. En este caso debemos 
            //agregar los horarios a las celdas que estan combinadas.
            $this->cargar_hora();
            //$excel->setActiveSheetIndex(0);
            $indice=0;
            $longitud=count($this->s__columnas);
            
            while($indice < $longitud){
                if(($indice % 2) == 0){
                    $excel->getActiveSheet()->setCellValue("{$this->s__columnas[$indice]}{$this->s__contador_merge}", $this->s__hora[$indice]);
                    $excel->getActiveSheet()->getStyle("{$this->s__columnas[$indice]}{$this->s__contador_merge}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//                    $excel->getActiveSheet()->getStyle("{$this->s__columnas[$indice]}{$this->s__contador_merge}")->applyFromArray(
//                        array(
//                            'borders' => array( 
//                                'top' => array(
//                                    'style' => PHPExcel_Style_Border::BORDER_THIN
//                                ),
//                                'bottom' => array(
//                                    'style' => PHPExcel_Style_Border::BORDER_THIN
//                                ),
//                                'left' => array(
//                                    'style' => PHPExcel_Style_Border::BORDER_THIN
//                                ),
//                                'right' => array(
//                                    'style' => PHPExcel_Style_Border::BORDER_THIN
//                                ),
////                                'allborders' => array(
////                                    'style' => PHPExcel_Style_Border::BORDER_HAIR
////                                ),
//                            )
//                        )
//                    );
                }
                $indice += 1;
            }
            
            $this->s__contador_merge += 1;
            //llenamos la segunda fila del reporte, cuando el s__contador_merge esta en 3. En este caso debemos 
            //agregar en las celdas que no estan combinadas el texto 00 o 30.
            //Aqui es donde debemos configurar el arreglo posicion_hora con el siguiente formato 
            //(hora => columna).
            //s__columnas(B(0), C(1), D(2), E(3), ..., AI) = s__hora(08:00(0), 08:30(1), 09:00(2), 09:30(3), ...)
            //esto nos permite utilizar como indice el atributo clave.
            foreach($this->s__columnas as $clave=>$valor){
                if(($clave % 2) == 0){
                    $excel->getActiveSheet()->getCell("$valor{$this->s__contador_merge}")->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
                    $excel->getActiveSheet()->setCellValue("$valor{$this->s__contador_merge}", "00");
                    $excel->getActiveSheet()->getStyle("$valor{$this->s__contador_merge}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                }
                else{
                    $excel->getActiveSheet()->setCellValue("$valor{$this->s__contador_merge}", "30");
                    $excel->getActiveSheet()->getStyle("$valor{$this->s__contador_merge}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                }
                
                //(08:00 => B, 08:30 =>C, 09:00 => D), necesitamos saber las coordenadas especificas , a partir 
                //de una hora, para merguear celdas.
                $this->s__posicion_hora[$this->s__hora[$clave]]=$valor;
            }
            
            $this->s__contador_merge += 1;
            //LLenamos la columna A con las aulas de la UA correcta, en este caso el s__contador_merge empieza
            //a funcionar con el valor 4. Se incrementa por cada cada aula agregada. A su vez debemos establecer
            //la altura adecuada de las fila para poder guardar sin problemas la informacion relacionada a una
            //asignacion. Entre otras cosas debemos alinear el texto.
            //$excel->getActiveSheet()->getRowDimension($this->s__contador_merge)->setRowHeight(35);
            $a='A';
            foreach ($this->s__aulas_ua as $clave=>$valor){
                $excel->getActiveSheet()->setCellValue("$a{$this->s__contador_merge}", $valor['aula']);
                $excel->getActiveSheet()->getStyle("$a{$this->s__contador_merge}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $excel->getActiveSheet()->getStyle("$a{$this->s__contador_merge}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $excel->getActiveSheet()->getRowDimension($this->s__contador_merge)->setRowHeight(45);
                $excel->getActiveSheet()->getStyle("$a{$this->s__contador_merge}")->applyFromArray(
                        array(
                            'borders' => array( 
                                'top' => array(
                                    'style' => PHPExcel_Style_Border::BORDER_THIN
                                ),
                                'bottom' => array(
                                    'style' => PHPExcel_Style_Border::BORDER_THIN
                                ),
                                'left' => array(
                                    'style' => PHPExcel_Style_Border::BORDER_THIN
                                ),
                                'right' => array(
                                    'style' => PHPExcel_Style_Border::BORDER_THIN
                                ),
//                                'allborders' => array(
//                                    'style' => PHPExcel_Style_Border::BORDER_HAIR
//                                ),
                            )
                        )
                );
                //(aula_x, fila_y) donde y es el valor de s__contador_merge.
                $this->s__posicion_aula[$valor['id_aula']]=$this->s__contador_merge;
                
                $this->s__contador_merge += 1;
            }
            
            //cuando el proceso anterior termina debemos sumarle 3 unidades al contador y no tocarlo nunca mas 
            //hasta que el proceso vuelva a empezar con otro dia de la semana. Podemos recibir hasta 7 dias.
            //Esto es importante si queremos crear un reporte debajo de otro, en la misma hoja de calculo. 
            //Pero si recibimos 7 dias es mas conveniente crear una hoja de calculo nueva para cada dia 
            //de la semana. Para lograr esto ultimo debemos resetear contador_merge cuando el proceso
            //de configuracion termina.
            $this->s__contador_merge += 3;
            //$excel->getActiveSheet()->setCellValue("$a{$this->s__contador_merge}", "A E O T");
            
        }
        
        /*
         * Esta funcion agrega las asignaciones en el cuerpo principal del reporte. 
         */
        function completar_reporte (PHPExcel $excel){
            //print_r($this->s__asignaciones);
            //debemos tener cuidado con el formato de la hora. Desde postgres extraemos la hora con el siguiente
            //formato hh:mm:ss, ejemplo 08:00:00.
            //Si en posicion_hora guardamos hh:mm la comparacion no es exitosa por lo tanto el reporte no se 
            //puede concretar.
            
            foreach ($this->s__asignaciones as $clave=>$asignacion){
                
                $fila=$this->s__posicion_aula[$asignacion['id_aula']];
                $primer_columna=$this->s__posicion_hora[$asignacion['hora_inicio']];
                $segunda_columna=$this->s__posicion_hora[$asignacion['hora_fin']];
                //print_r(" Esta es la primer columna : $primer_columna, y esta es una columna : {$this->s__posicion_hora[$hora_inicio]}");exit();
                //print_r($this->s__asignaciones);exit();
                $columna_anterior=$this->buscar_columna_anterior($segunda_columna);
                $excel->getActiveSheet()->mergeCells("$primer_columna$fila:$columna_anterior$fila");
                //print_r("$primer_columna$fila:$segunda_columna$fila");
                $excel->getActiveSheet()->getCell("$primer_columna$fila")->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
                //print_r("esta es la primer columna del mergue : $primer_columna$fila, y este es el dato {$asignacion['dato_celda']} \n");
                //agregamos un color de fondo
                $excel->getActiveSheet()->getStyle("$primer_columna$fila")->getFill()->applyFromArray(
                       array( 
                           'type' => PHPExcel_Style_Fill::FILL_SOLID, 
                           'startcolor' => array('rgb' => ($this->s__color[$asignacion['facultad']])) 
                       )
                );
                $dato_celda=  strtoupper($asignacion['dato_celda']);
                //debemos decodificar la informacion de la celda para que no se trunque en el reporte final.
                $excel->getActiveSheet()->setCellValue("$primer_columna$fila", utf8_encode($dato_celda));
                //ajustamos el texto a la celda
                $excel->getActiveSheet()->getStyle("$primer_columna$fila")->getAlignment()->setVertical(PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY);
                                
            }
            
      }
      
      /*
       * Esta funcion busca la columna anterior para la hora_fin. Se usa para merguear un grupo de celdas
       * correctamente.
       */
      function buscar_columna_anterior ($columna_actual){
          $columna_anterior='';
          if(strcmp($columna_actual, 'B')==0){
              return $columna_actual;
          }
          else{
              $i=1;
              $fin=FALSE;
              $longitud=count($this->s__columnas);
              $columna_anterior=$this->s__columnas[0];
              while(($i<$longitud) && !$fin){
                  $columna=$this->s__columnas[$i];
                  if(strcmp($columna_actual, $columna)==0){
                      $fin=TRUE;
                  }
                  else{
                      $columna_anterior=$columna;
                  }
                  $i++;
              }
              
              return $columna_anterior;
          }
      }
      
      //----------------------------------------------------------------------------------------------
      //---- Implementamos la funcion volver ---------------------------------------------------------
      //----------------------------------------------------------------------------------------------
      
      function evt__volver (){
          $this->set_pantalla("pant_edicion");
      }
      
      //----------------------------------------------------------------------------------------------
      //---- Pantalla Reporte y Edicion --------------------------------------------------------------
      //----------------------------------------------------------------------------------------------
      
      function conf__pant_reporte (toba_ei_pantalla $pantalla){
          $this->pantalla()->tab('pant_edicion')->desactivar();
      }
      
      function conf__pant_edicion (toba_ei_pantalla $pantalla){
          $this->pantalla()->tab('pant_reporte')->desactivar();
      }

}

?>