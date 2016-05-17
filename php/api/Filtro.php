<?php

/*
 * Esta clase permite filtrar horarios disponibles. Se aplica sobre datos que poseen :
 * (capacidad, hora_inicio, hora_fin).
 * Se usa en las operaciones Calendario Comahue y Generar Solicitud.
 */
class Filtro {
    
    private $s__horarios_disponibles;
    private $s__datos_filtrados;
    
    public function __construct ($horarios_disponibles){
        $this->s__horarios_disponibles=$horarios_disponibles;
    }
    
    public function filtrar ($datos){
        $this->s__datos_filtrados=array();
            //print_r($datos);            
            foreach ($datos as $clave=>$valor){
                                           
                switch($clave){
                    case 'capacidad' : $this->filtrar_horarios($valor['valor'], 'capacidad', "{$valor['condicion']}"); 
                                       break;
                                   
                    case 'hora_inicio' : $condicion="{$valor['condicion']}";
                                         $valor_filtro=(strcmp($condicion, "entre")==0) ? ($valor['valor']) : ("{$valor['valor']}:00") ;
                                         $this->filtrar_horarios($valor_filtro, 'hora_inicio', $condicion);
                                         break;
                                     
                    case 'hora_fin' : $condicion="{$valor['condicion']}";
                                      $valor_filtro=(strcmp($condicion, "entre")==0) ? ($valor['valor']) : ("{$valor['valor']}:00") ;
                                      $this->filtrar_horarios($valor_filtro, 'hora_fin', $condicion);
                                      break;
                                  
                    default : print_r('Algo anda mal'); break;              
                }
            }
            
            //print_r("Estos son los horarios filtrados");
            //print_r($this->s__datos_filtrados);
            
            return $this->s__datos_filtrados;
    }
    
    /*
     * Esta funcion recorre el arreglo s__horarios_disponibles y extrae los datos que cumplen las 
     * condiciones especificadas por el usuario.
     * @$valor_filtro : contiene la informacion de busqueda especificada por el usuario.
     * @$columna_filtro : contiene la columna de busqueda seleccionada por el usuario, puede ser capacidad, 
     * hora_inicio u hora_fin.
     * @$operador : contiene el operador de busqueda seleccionado, puede ser =, >, >= etc.  
     */
    private function filtrar_horarios ($valor_filtro, $columna_filtro, $operador){
        foreach ($this->s__horarios_disponibles as $clave=>$valor){
            $dato=$this->obtener_opcion_busqueda($valor, $columna_filtro);
            print_r("Este es el dato obtenido : $dato \n");
            $agregar_al_filtro=FALSE;
            switch($operador){
                case 'es_igual_a' : $tipo=  gettype($valor_filtro);
                                    $true=$valor_filtro == '08:00:00';
                                    print_r("Este es el tipo : $tipo : $true\n");
                                    if($valor_filtro == $dato){
                                        $agregar_al_filtro=TRUE;
                                    } break;

                case 'es_distinto_de' : if($valor_filtro != $dato){
                                            $agregar_al_filtro=TRUE;
                                        } break;

                case 'es_mayor_que' : if($dato > $valor_filtro){
                                            $agregar_al_filtro=TRUE;
                                      } break;

                case 'es_mayor_igual_que' : if($dato >= $valor_filtro){
                                                    $agregar_al_filtro=TRUE;
                                              } break;

                case 'es_menor_que' : if($dato < $valor_filtro){
                                            $agregar_al_filtro=TRUE;
                                      } break;

                case 'es_menor_igual_que' : if($dato <= $valor_filtro){
                                                    $agregar_al_filtro=TRUE;
                                              } break;

                case 'entre' : $hora_inicio="{$valor_filtro['desde']}:00";
                               $hora_fin="{$valor_filtro['hasta']}:00";
                               if(($dato >= $hora_inicio) && ($dato <= $hora_fin)){
                                    $agregar_al_filtro=TRUE;
                               } break;

                default : print_r("La condicion de busqueda no se esta considerando string"); break;               
            }

            if($agregar_al_filtro){
                //agregamos el horario disponible al arreglo s__datos_filtrados. Se reutiliza la funcion
                //existe.
                print_r("Este es el valor : $valor \n");
                $existe=$this->existe($this->s__datos_filtrados, $valor);
                if(!$existe){
                    //agregamos al final
                    $this->s__datos_filtrados[]=$valor;
                }

            }
        }
    }

    /*
     * Esta funcion devuleve un valor exacto del arreglo s__horarios_disponibles para realizar 
     * conparaciones en filtrar_horarios.
     * @$valor : contiene un elto. del arreglo s__horarios_disponibles.
     * @$columna_filtro : contiene la columna de busqueda seleccionada por el usuario.
     */
    private function obtener_opcion_busqueda ($valor, $columna_filtro){
        $dato='';
        switch($columna_filtro){
            case 'capacidad' : $dato=$valor['capacidad']; break;
            case 'hora_inicio' : $dato=$valor['hora_inicio']; break;
            case 'hora_fin' : $dato=$valor['hora_fin']; break;
        }

        return $dato;
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
    
}

?>

