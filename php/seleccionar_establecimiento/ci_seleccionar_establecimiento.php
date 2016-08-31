<?php
class ci_seleccionar_establecimiento extends toba_ci
{
	//---- Cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
                //Sin esta instruccion enviamos al popup una clave aleatoria.
                $cuadro->desactivar_modo_clave_segura();
                //Cargamos al cuadro con todas las unidades academicas.
		$cuadro->set_datos($this->dep('datos')->tabla('unidad_academica')->get_listado());
	}

	function evt__cuadro__seleccion($datos)
	{
		$this->dep('datos')->cargar($datos);
	}

}

?>