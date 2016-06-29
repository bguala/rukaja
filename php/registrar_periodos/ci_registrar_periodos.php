<?php
class ci_registrar_periodos extends toba_ci
{
        protected $s__id_periodo;
        protected $s__id_sede;
        
	//---- Cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
            $this->s__id_sede=$this->dep('datos')->tabla('persona')->get_sede_para_usuario_logueado(toba::usuario()->get_id());
            $cuadro->set_datos($this->dep('datos')->tabla('periodo')->get_listado(date('Y'), $this->s__id_sede));
	}

	function evt__cuadro__seleccion($datos)
	{
            
            $this->s__id_periodo=$datos['id_periodo'];
	
	}

	//---- Formulario -------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
	    if(isset($this->s__id_periodo)){
                
                $periodo=$this->dep('datos')->tabla('periodo')->get_periodo($this->s__id_periodo);
                
                $tipo_periodo=$periodo[0]['tipo_periodo'];
                
                switch($tipo_periodo){
                    case "Cuatrimestre" : $desactivar_efs=array(
                                              'turno', 'facultad', 'nombre'
                                          );
                                          $form->set_solo_lectura($desactivar_efs);
                                          $form->set_datos($periodo[0]);
                                          break;
                                      
                    case "Examen Final" : $desactivar_efs=array(
                                              'facultad', 'nombre'
                                          );
                                          $form->set_solo_lectura($desactivar_efs);
                                          $form->set_datos($periodo[0]);
                                          break;
                                      
                    case 'Curso de Ingreso' : $desactivar_efs=array(
                                                   'numero', 'turno', 
                                              );
                                              $form->set_solo_lectura($desactivar_efs);
                                              $form->set_datos($periodo[0]);
                                              break;
                                          
                    default : print_r("No hay clave en la variable s__id_periodo");
                }
            }
                
        }

	function evt__formulario__alta($datos)
	{
	    
            $tipo_periodo=$datos['tipo_periodo'];
            $periodo=array(
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin'    => $datos['fecha_fin'],
                'anio_lectivo' => date('Y'),
                'id_sede' => $this->s__id_sede
            );
            $this->dep('datos')->tabla('periodo')->nueva_fila($periodo);
            $this->dep('datos')->tabla('periodo')->sincronizar();
            $this->dep('datos')->tabla('periodo')->resetear();
            
            $secuencia=  recuperar_secuencia('periodo_id_periodo_seq');
            
            switch($tipo_periodo){
                case 'Cuatrimestre' :     $cuatrimestre=array(
                                              'numero' => $datos['numero'],
                                              'id_periodo' => $secuencia
                                          );
                                          $this->dep('datos')->tabla('cuatrimestre')->nueva_fila($cuatrimestre);
                                          $this->dep('datos')->tabla('cuatrimestre')->sincronizar();
                                          $this->dep('datos')->tabla('cuatrimestre')->resetear();
                                          break;
                                      
                case 'Examen Final' :     $examen_final=array(
                                              'numero' => $datos['numero'],
                                              'turno' => $datos['turno'],
                                              'id_periodo' => $secuencia
                                          );
                                          $this->dep('datos')->tabla('examen_final')->nueva_fila($examen_final);
                                          $this->dep('datos')->tabla('examen_final')->sincronizar();
                                          $this->dep('datos')->tabla('examen_final')->resetear();
                                          break;
                                      
                case 'Curso de Ingreso' : $curso_ingreso=array(
                                               'facultad' => $datos['facultad'],
                                               'nombre' => $datos['nombre'],
                                               'id_periodo' => $secuencia
                                          );
                                          $this->dep('datos')->tabla('curso_ingreso')->nueva_fila($curso_ingreso);
                                          $this->dep('datos')->tabla('curso_ingreso')->sincronizar();
                                          $this->dep('datos')->tabla('curso_ingreso')->resetear();
                                          break;
            }
	}

	function evt__formulario__modificacion($datos)
	{
            $this->dep('datos')->tabla('periodo')->cargar(array('id_periodo'=>$this->s__id_periodo));
                        
            $tipo_periodo=$datos['tipo_periodo'];
            $periodo=array(
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin' => $datos['fecha_fin'],
                'id_periodo' => $this->s__id_periodo,
                'id_sede' => $this->s__id_sede
            );
            switch($tipo_periodo){
                case 'Cuatrimestre' : $this->dep('datos')->tabla('cuatrimestre')->cargar(array('id_periodo'=>$this->s__id_periodo));
                                      
                                      $cuatrimestre=array(
                                           'numero' => $datos['numero'],
                                           'id_peiodo' => $this->s__id_periodo
                                      );
                                      $this->dep('datos')->tabla('periodo')->set($periodo);
                                      $this->dep('datos')->tabla('cuatrimestre')->set($cuatrimestre);
                                      
                                      $this->dep('datos')->tabla('periodo')->sincronizar();
                                      $this->dep('datos')->tabla('cuatrimestre')->sincronizar();
                                      
                                      $this->dep('datos')->tabla('periodo')->resetear();
                                      $this->dep('datos')->tabla('cuatrimestre')->resetear();
                                      break;
                                  
                case 'Examen Final' : $this->dep('datos')->tabla('examen_final')->cargar(array('id_periodo'=>$this->s__id_periodo));
                    
                                      $examen_final=array(
                                           'turno' => $datos['turno'],
                                           'numero' => $datos['numero'],
                                           'id_periodo' => $this->s__id_periodo
                                      );
                                      
                                      $this->dep('datos')->tabla('periodo')->set($periodo);
                                      $this->dep('datos')->tabla('examen_final')->set($examen_final);
                                      
                                      $this->dep('datos')->tabla('periodo')->sincronizar();
                                      $this->dep('datos')->tabla('examen_final')->sincronizar();
                                      
                                      $this->dep('datos')->tabla('periodo')->resetear();
                                      $this->dep('datos')->tabla('examen_final')->resetear();
                                      break;
                                  
                case 'Curso de Ingreso' : $this->dep('datos')->tabla('curso_ingreso')->cargar(array('id_periodo'=>$this->s__id_periodo));
                    
                                          $curso_ingreso=array(
                                               'facultad' => $datos['facultad'],
                                               'nombre' => $datos['nombre'],
                                               'id_periodo' => $this->s__id_periodo
                                          );
                                          
                                          $this->dep('datos')->tabla('periodo')->set($periodo);
                                          $this->dep('datos')->tabla('curso_ingreso')->set($curso_ingreso);
                                          
                                          $this->dep('datos')->tabla('periodo')->sincronizar();
                                          $this->dep('datos')->tabla('curso_ingreso')->sincronizar();
                                          
                                          $this->dep('datos')->tabla('periodo')->resetear();
                                          $this->dep('datos')->tabla('curso_ingreso')->resetear();
                                          break;
            }
            
            $this->s__id_periodo=null;
            
	}

	function evt__formulario__baja()
	{
            $asignaciones_por_periodo=$this->dep('datos')->tabla('asignacion')->get_asignaciones_por_periodo($this->s__id_periodo);
            
            if(count($asignaciones_por_periodo)==0){
                $this->dep('datos')->tabla('periodo')->cargar(array('id_periodo'=>$this->s__id_periodo));
                //debemos usar la funcion eliminar_todo porque eliminar_fila, en el datos_tabla, desacopla el 
                //cursor del registro lo que provoca problemas a la hora de sincronizar.
                $this->dep('datos')->tabla('periodo')->eliminar_todo();

            }
            else{
                $mensaje=" El período seleccionado posee asignaciones por lo tanto no se puede eliminar ";
                toba::notificacion()->agregar(utf8_decode($mensaje), $nivel);
            }
            
            $this->s__id_periodo=null;
	}

	function evt__formulario__cancelar()
	{
            $this->s__id_periodo=null;
            $this->resetear();
	}

	function resetear()
	{
		$this->dep('datos')->resetear();
	}

}

?>