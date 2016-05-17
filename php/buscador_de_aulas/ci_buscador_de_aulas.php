<?php

include_once (toba_dir().'/proyectos/gestion_aulas/php/api/HorariosDisponibles.php');

class ci_buscador_de_aulas extends toba_ci
{

	//---- Cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
            //obtenemos la sede a la que pertenece el usuario que se loguea
            $nombre_usuario=toba::usuario()->get_id();
            $id_sede=$this->dep('datos')->tabla('persona')->get_sede_para_usuario_logueado($nombre_usuario);
            
            //obtenemos los datos almacenados en la sesion
            $tipo=toba::memoria()->get_dato_instancia(4);
            
            //con esta instruccion evitamos enviar una clave aleatoria al popup
            $cuadro->desactivar_modo_clave_segura();
            $id_sede=1;
            
            switch($tipo){
                case 'Definitiva' : $hora_inicio=toba::memoria()->get_dato_instancia(1);
                                    
                                    $hora_fin=toba::memoria()->get_dato_instancia(2);
                                    
                                    $id_periodo=toba::memoria()->get_dato_instancia(3);
                                    $dia=toba::memoria()->get_dato_instancia(5);
                                    toba::memoria()->set_dato_instancia(0, $id_sede);
                
                                    $aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($id_sede);
                                    $asignaciones=$this->dep('datos')->tabla('asignacion')->get_asignaciones_definitivas_por_dia($id_sede, $dia, $id_periodo);
                                    //obtenemos las aulas que estan siendo utilizadas para el dia $dia
                                    $aulas=$this->obtener_aulas($asignaciones);
                
                                    $horarios_disponibles=new HorariosDisponibles();
                                    $horarios_disponibles_por_aula=$horarios_disponibles->calcular_horarios_disponibles($aulas, $aulas_ua, $asignaciones);
                                    
                                    $cuadro->set_titulo(utf8_decode("Asignación Definitiva"));
                                    $cuadro->set_datos($this->extraer_aulas_disponibles($horarios_disponibles_por_aula, $hora_inicio, $hora_fin));
                                    
                                    break;
                                
                case 'Periodo' :  $cuadro->set_titulo(utf8_decode("Asignación por Periodo"));
                                  $cuadro->set_datos($this->dep('datos')->tabla('aula')->get_aulas_por_sede($id_sede));
                                  break;
            }
            
            //$cuadro->set_datos($this->dep('datos')->tabla('aula')->get_aulas($id_sede));
            
            
            toba::memoria()->limpiar_datos_instancia();
            
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
        

}

?>