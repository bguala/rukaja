<?php
class ci_registrar_organizacion extends toba_ci
{
	//---- Cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->dep('datos')->tabla('organizacion')->get_listado());
	}

	function evt__cuadro__seleccion($datos)
	{//print_r($datos);exit();
		$this->dep('datos')->tabla('organizacion')->cargar($datos);
	}

	//---- Formulario -------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario_ml $form)
	{
		if ($this->dep('datos')->tabla('organizacion')->esta_cargada()) {
			$form->set_datos($this->dep('datos')->tabla('organizacion')->get_filas());
		}
	}
        
        /*
         * Este evento implicito se ejecuta cuando pulsamos el boton Guardar perteneciente a la pantalla Inicio.
         * El formulario ml sera utilizado para tres propositos:
         * 
         * Alta: el indice del arreglo $datos empieza en 157 (a veces en 156), este numero coincide con el valor
         * del atributo x_dbr_clave. Ademas apex_ei_analisis_fila contiene una letra A, que significa alta.
         * 
         * Modififacion: el indice del arreglo $datos empieza en cero, este numero coincide con x_dbr_clave. 
         * apex_ei_analisis_fila contiene la letra M, que significa modificacion.
         * 
         * Baja: el arreglo $datos solamnete contiene el atributo apex_ei_analisis_fila con la letra B.
         */
        function evt__formulario__aceptar ($datos){
            print_r($datos);
            try{
                foreach($datos as $key=>$organizacion){
                    $accion=$organizacion['apex_ei_analisis_fila'];
                    switch($accion){
                        case 'A' : $this->dep('datos')->tabla('organizacion')->resetear();
                                   //Si queremos dar de alta varios registros al mismo tiempo se supone que el objeto 
                                   //datos_tabla esta vacio.
                                   $this->dep('datos')->tabla('organizacion')->nueva_fila($organizacion);
                                   $this->dep('datos')->tabla('organizacion')->sincronizar();
                                   $this->dep('datos')->tabla('organizacion')->resetear();
                                   break;

                        case 'M' : $organizacion_cargada=$this->dep('datos')->tabla('organizacion')->get();
                                   print_r($organizacion_cargada);
                                   //Despues de presionar el boton 'Editar' del cuadro 'organizaciones existentes' 
                                   //el datos_tabla queda cargado con la informacion que podemos editar desde el 
                                   //form_ml.
                                   //Ahora vamos a modificar el registro referenciado por el cursor del datos_tabla.
                                   $this->dep('datos')->tabla('organizacion')->set($organizacion);
                                   $this->dep('datos')->tabla('organizacion')->sincronizar();
                                   $this->dep('datos')->tabla('organizacion')->resetear();
                                   break;

                        case 'B' : $organizacion_cargada=$this->dep('datos')->tabla('organizacion')->get();
                                   print_r($organizacion_cargada);
                                   //Despues de presionar el boton 'Editar' del cuadro 'organizaciones existentes' 
                                   //el datos_tabla queda cargado con la informacion que podemos eliminar desde el 
                                   //form_ml.
                                   //Ahora vamos a eliminar el registro referenciado por el cursos del datos_tabla.
                                   $this->dep('datos')->tabla('organizacion')->eliminar_todo();
                                   $this->dep('datos')->tabla('organizacion')->sincronizar();
                                   $this->dep('datos')->tabla('organizacion')->resetear();
                                   break;
                    }
                }
            }catch(toba_error $e){
                //print_r($e);
            }
        }
        
	function evt__formulario__alta($datos)
	{
            print_r($datos);exit();
		$this->dep('datos')->tabla('organizacion')->set($datos);
		$this->dep('datos')->sincronizar();
		$this->resetear();
	}

	function evt__formulario__modificacion($datos)
	{
		$this->dep('datos')->tabla('organizacion')->set($datos);
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

}

?>