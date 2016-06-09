<?php

/*
 * Esta clase agrupa la interfaz para calculr horarios disponibles a partir de :
 * aulas pertenecientes a una unidad academica.
 * aulas actualmente usadas.
 * asignaiones en aulas.
 * HorariosDisponibles no es una clase asociada al controlador de interfaz, por lo tanto dentro de ella no 
 * podemos usar la api toba_datos_tabla para hacer consultas en la db. 
 */

class HorariosDisponibles {
    
        private $s__horarios_disponibles;
        
        public function __construct() {
            $this->s__horarios_disponibles=array();
        }
        
        //---------------------------------------------------------------------------------------------------
        //---- Funciones para calcular horarios disponibles -------------------------------------------------
        //---------------------------------------------------------------------------------------------------
        
        /*
         * Esta funcion devuelve los horarios disponibles a partir de un cjto de asignaciones y aulas.
         * Se utiliza en la operacion Generar Solicitud, Calendario Comahue, Cargar Asignaciones.
         */
        public function calcular_horarios_disponibles ($aulas, $aulas_ua, $asignaciones){
                //Obtenemos las aulas con disponibilidad total, de 8 a 22 hs.
                $aulas_disponibles=$this->obtener_aulas_con_disponibilidad_total($aulas, $aulas_ua);

                //Agrupamos los horarios de clases segun las aulas.
                $espacios_filtrados=$this->filtrar_espacios($aulas, $asignaciones);
                
                //Obtenemos los horarios disponibles en cada aula .
                $this->obtener_horarios_disponibles($aulas, $espacios_filtrados);           

                //Agregamos al arreglo s__horarios_disponibles todas las aulas que tengan disponibilidad total.
                if(count($aulas_disponibles)>0){
                    $total=$this->obtener_horarios_disponibles($aulas_disponibles, array());
                }
                
                if(count($this->s__horarios_disponibles)>0){
                    $this->agregar_capacidad();
                }
                
                return $this->s__horarios_disponibles;
        }

        /*
         * devuelve todas las aulas que poseen diponibilidad total de horarios (de 8 a 22 hs)
         */
        private function obtener_aulas_con_disponibilidad_total ($aulas, $aulas_ua){
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
         * Genera un recordset con todos los horarios ocupados en un aula. Cada Elto del recordset
         * posee todos los horarios para un aula x.
         */
        private function filtrar_espacios ($aulas, $espacios_concedidos){
            $indice=0;
            $espacios_filtrados=array();
            foreach($aulas as $aula){
                $espacios=$this->obtener_espacios($aula,$espacios_concedidos);
                $espacios_filtrados[$indice]=$espacios;
                $indice += 1;
            }
            return $espacios_filtrados;
        }
        
        /*
         * genera un arreglo con todos los horarios ocupados en un aula x
         * @espacio es un arreglo con el siguiente formato (hora_inicio, hora_fin, aula)
         */
        private function obtener_espacios ($aula, $espacios_concedidos){
            $espacios=array();
            $indice=0;
            foreach($espacios_concedidos as $clave=>$espacio){
                //strcmp($aula, $espacio['aula'])==0
                if($aula['id_aula'] == $espacio['id_aula']){
                    $espacios[$indice]=$espacio;
                    $indice += 1;
                }
            }
            return $espacios;
        }
        
        /*
         * Calculamos los horarios disponibles por cada aula. 
         * @aulas contiene todas las aulas involucradas, indice => array (aula, id_aula)
         * @horarios_ocupados contiene todos los horarios ocupados por aula, segun el dia especificado en la 
         * solicitud. Si debemos calcular disponibilidad total usamos un arreglo vacio.
         */
        private function obtener_horarios_disponibles ($aulas, $horarios_ocupados){
            
            foreach ($aulas as $clave=>$aula){
                //obtenemos los horarios ocupados para un aula especifica
                $horarios_ocupados_por_aula=$this->obtener_horarios_ocupados_por_aula($aula, $horarios_ocupados);
                
                //$aula no es necesario, quitar mas adelante
                //obtenemos todos los horarios ocupados y disponibles
                $horarios=$this->calcular_espacios_disponibles($aula, $horarios_ocupados_por_aula);
                
                //extraemos los horarios disponbles de $horarios
                $horarios_depurados=$this->depurar_horarios($horarios, $aula);
                
                
            }
            
            
        }
        
        /*
         * devuelve los horarios ocupados por aula
         * @$horarios_ocupados contiene todos los horarios ocupados por aula
         */
        private function obtener_horarios_ocupados_por_aula ($aula, $horarios_ocupados){
            $fin=FALSE;
            $i=0;
            $longitud=count($horarios_ocupados);
            
            while ($i<$longitud && !$fin){
                $elemento=$horarios_ocupados[$i];
                //strcmp($elemento[0]['id_aula'], $aula['id_aula']) == 0
                if($elemento[0]['id_aula'] == $aula['id_aula']){
                    $fin=TRUE;
                }
                
                $i += 1;
            }
            
            return $elemento; // indice => Array ( 0 => Array(), 1 => Array() ... )
        }
        
        /*
         * calcula los espacios disponibles en un aula (se tiene en cuenta el dia)
         * @espacios es un arreglo con todos los horarios ocupados en un aula x (se tiene en cuenta el dia)
         */
        private function calcular_espacios_disponibles ($aula, $espacios){
            $indice=0;
                                    
            //creo un arreglo con todos los horarios de cursado por dia
            $horarios=$this->crear_horarios();
            $longitud=count($horarios);
            foreach ($espacios as $clave=>$espacio){
                $fin=FALSE;
                while(($indice < $longitud) && !$fin){
                    
                    if(strcmp(($horarios[$indice][0]), ($espacio['hora_inicio'])) == 0){
                        
                        //para que el arreglo horarios pueda ser modificado en la rutina eliminar_horarios
                        //hay que realizar un pasaje de parametros por referencia (&horarios)
                        $this->eliminar_horario(&$horarios, $indice, $longitud, $espacio['hora_fin']);
                        
                        $fin=TRUE;
                        
                        //para volver a recorrer todo el arreglo en la proxima iteracion.
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
        private function eliminar_horario ($horarios, $indice, $longitud, $hora_fin){
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
        private function crear_horarios (){
            $hora=8;
            $indice=0;
            $prefijo="";
            $horarios=array();
            while($hora <= 23){
                
                $prefijo=($hora <= 9) ? "0".$hora : $hora ;
                
                $horarios[$indice]=array(
                    0 => "$prefijo:00:00",
                    1 => TRUE
                );
                $indice += 1;
                //replica para obtener los horarios disponibles
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
                //replica para obtener los horarios disponibles
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
         * devuelve un arreglo con los horarios disponibles para un aula x
         * @horarios contiene los horarios ocupados y disponibles para un aula
         * @aula contiene una estructura con el siguiente formato (aula, id_aula) 
         * TRUE indica horario disponible
         * FALSE indica horario ocupado 
         */
        private function depurar_horarios ($horarios, $aula){
            $horarios_disponibles=array();
            $indice=0;
            $longitud=count($horarios);
            $indice_horario=0;
            //guarda un horario disponible con el formato (hora_inicio, hora_fin, aula, id_aula)
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
        
        private function obtener_horario ($indice_horario, $horarios, $hora_fin){
            
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
        
        //-------------------------------------------------------------------------------------------------
        //-------------------------------------------------------------------------------------------------
        //-------------------------------------------------------------------------------------------------
        
        /*
         * Agregamos la capacidad a cada aula.
         */
        private function agregar_capacidad (){
            $id_sede=toba::memoria()->get_dato_instancia(0);
            
            //$aulas_con_capacidad=$this->dep('datos')->tabla('aula')->get_aulas_mas_capacidad($id_sede);
            $sql="SELECT id_aula, capacidad
                  FROM aula
                  WHERE id_sede=$id_sede";
            $aulas_con_capacidad=toba::db('rukaja')->consultar($sql);
            
            $longitud=count($this->s__horarios_disponibles);
            
            foreach ($aulas_con_capacidad as $clave=>$valor){
                for($i=0;$i<$longitud;$i++){
                    $elto=$this->s__horarios_disponibles[$i];
                    if($valor['id_aula'] == $elto['id_aula']){
                        $this->s__horarios_disponibles[$i]['capacidad']=$valor['capacidad'];
                    }                                                            
                }
            }
            
            toba::memoria()->limpiar_datos_instancia();
        }
    
}

?>
