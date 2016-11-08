<?php
class ci_seguimiento extends toba_ci
{
        
        protected $s__id_sede;
        
        //-----------------------------------------------------------------------------------
        //---- Pant Edicion -----------------------------------------------------------------
        //-----------------------------------------------------------------------------------
        
        function conf__pant_edicion (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab('pant_incidencia')->desactivar();
        }
        
	//---- Cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
            $this->s__id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
	    $cuadro->set_datos($this->dep('datos')->tabla('incidencia')->get_listado($this->s__id_sede));
	}

	function evt__cuadro__seleccion($datos)
	{
            
	}

}

?>