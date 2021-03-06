<?php
class ci_modificar_aula extends toba_ci
{
        protected $s__contador=0;
        protected $s__facultad;
        protected $s__id_aula;
        protected $s__id_sede;
        protected $s__datos_filtro;
        protected $s__where;                                                                //guarda el where del filtro
        protected $s__formatos_admitidos=array('image/jpeg','image/jpg','image/png');       //guarda los formatos de imagen correctos
        protected $s__error=array();                                                        //guarda las aulas que tienen un archivo adjunto con formato incorrecto
        
        //---- Filtro -----------------------------------------------------------------------
        
        function conf__filtro (toba_ei_filtro $filtro){
            
            if(isset($this->s__datos_filtro)){
                $filtro->set_datos($this->s__datos_filtro);
            }
        }
        
        /*
         * @datos contiene la informacion cargada para filtrar
         */
        function evt__filtro__filtrar ($datos){
            if(count($datos)>0){
                $this->s__where=$this->dep('filtro')->get_sql_where('OR');
            }
            
        }
        
        /*
         * unset destruye una variable en tiempo de ejecucion, liberando el espacio que ocupa em memoria
         */
        function evt__filtro__cancelar ($datos){
            unset($this->s__where);
        }
        
	//---- Cuadro -----------------------------------------------------------------------
        
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
                if($this->s__contador == 0){
                    
                    $this->s__id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
                    $this->s__facultad=$this->dep('datos')->tabla('unidad_academica')->get_unidad_academica ($this->s__id_sede);
                    
                }
                $cuadro->set_titulo("Listado de aulas de ".$this->s__facultad[0]['establecimiento']);
                if(isset($this->s__where)){
                    $cuadro->set_datos($this->dep('datos')->tabla('aula')->get_listado(" ({$this->s__where}) AND (t_a.id_sede = {$this->s__id_sede})"));
                }
                else{
                    $cuadro->set_datos($this->dep('datos')->tabla('aula')->get_aulas($this->s__id_sede));
                }
	}
        
        /*
         * @datos contiene la clave del registro seleccionado
         */
	function evt__cuadro__seleccion($datos)
	{
                //cargamos el datos tabla aula utilizando la clave del registro seleccionado en el cuadro
		$this->dep('datos')->tabla('aula')->cargar(array('id_aula'=>$datos['id_aula']));
                $this->s__id_aula=$datos['id_aula'];
                $this->s__contador += 1;
	}

	//---- Formulario -------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
            if(($this->s__contador % 2)==0){
                $form->colapsar();
            }
            else{
                $form->descolapsar();
                //verificamos si el datos tabla aula tiene 1 registro
		if ($this->dep('datos')->tabla('aula')->esta_cargada()) {
                        //el metodo get devuleve el registro del datos tabla aula que esta siendo 
                        //referenciado por el cursor
			$form->set_datos($this->dep('datos')->tabla('aula')->get());
		}
            }
            
	}

	function evt__formulario__alta($datos)
	{
		$this->dep('datos')->tabla('aula')->set($datos);
		$this->dep('datos')->sincronizar();
		$this->resetear();
	}
        
        /*
         * Esta funcion permite editar la informacion relacionada con un aula.
         *@datos contiene la informacion editada del registro
         */
	function evt__formulario__modificacion($datos)
	{
//		$this->dep('datos')->tabla('aula')->set($datos);
//		$this->dep('datos')->sincronizar();
//		$this->resetear();
                $this->registrar_aula($datos);
                
                if(count($this->s__error)>0){
                    $cadena=$this->generar_cadena();
                    $mensaje="Para las siguientes aulas $cadena no se registraron sus imágenes debido a que no tienen un formato"
                             . " adecuado. Los formatos admitidos por el sistema son jpeg, jpg y png";
                    toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                }
                
                if(isset($this->s__datos_filtro)){
                    $this->s__datos_filtro=NULL;
                }
                
                $this->s__contador += 1;
	}
        
        /*
         *Esta funcion permite eliminar un aula del sistema.
         *@datos contiene el registro que se quiere eliminar
         */
	function evt__formulario__baja($datos)
	{
                $datos['eliminada']=TRUE;
                $datos['id_sede']=$this->s__id_sede;
                $datos['id_aula']=$this->s__id_aula;
                
                $this->dep('datos')->tabla('aula')->set($datos);
                $this->dep('datos')->tabla('aula')->sincronizar();
		$this->dep('datos')->tabla('aula')->resetear();
                
                if(isset($this->s__datos_filtro)){
                    $this->s__datos_filtro=NULL;
                }
                
                $this->s__contador += 1;
	}

	function evt__formulario__cancelar()
	{
		$this->resetear();
                $this->s__contador += 1;
	}

	function resetear()
	{
		$this->dep('datos')->resetear();
	}
        
        //---- Metodos para registrar  cambios en un aula-----------------------------------------------
               
        /*
         * Esta funcion arma una cadena con las aulas que tenian un archivo con formato incorrecto
         */
        function generar_cadena (){
            $cadena="";
            foreach ($this->s__error as $clave=>$valor){
                $cadena .= ($valor.", ");
            }
            
            return $cadena;
        }
        
        /*
         * Esta funcion permite registrar un aula en el sistema. Si el aula no posee una imagen 
         * adjunta o su formato es incorrecto se registra igual.
         */
        function registrar_aula ($aula){
            $this->dep('datos')->tabla('aula')->set($aula);
                    
            if($this->tiene_formato_admitido($aula['imagen']['type'])){
                                                              
                $fp=  fopen($aula['imagen']['tmp_name'], 'rb');
                       
                $this->dep('datos')->tabla('aula')->set_blob('imagen', $fp);
                
            }
            else{
                if(isset($aula['imagen']))
                    $this->s__error[]=$aula['nombre'];
            }
                        
            $this->dep('datos')->tabla('aula')->sincronizar();
            $this->dep('datos')->tabla('aula')->resetear();
        }
        
        /*
         * Esta funcion devuelve true si el archivo que se quiere registrar tiene un formato admitido por
         * el sistema
         */
        function tiene_formato_admitido ($formato){
            $longitud=count($this->s__formatos_admitidos);
            $i=0;
            $fin=FALSE;
            while (($i < $longitud) && !$fin){
                $extension="'{$this->s__formatos_admitidos[$i]}'";
                
                if(strcmp($extension, "'$formato'")==0){
                    $fin=TRUE;
                }
                
                $i += 1;
            }
            
            return $fin;
        }
        
}

?>