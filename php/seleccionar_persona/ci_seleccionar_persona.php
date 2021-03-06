<?php
class ci_seleccionar_persona extends toba_ci
{
        protected $s__personas;
        
        //---- Filtro -----------------------------------------------------------------------
                
        function evt__filtro__filtrar (){
            $this->s__personas=$this->dep('datos')->tabla('persona')->get_docentes($this->dep('filtro')->get_sql_where());
        }
    
	//---- Cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
            if(isset($this->s__personas)){
                $cuadro->desactivar_modo_clave_segura();
                $cuadro->set_datos($this->s__personas);
            }
	}

	function evt__cuadro__seleccion($datos)
	{
            $this->dep('datos')->cargar($datos);
	}
        
        /*
         * Este formulario nos ayuda a redimensionar el tamanio de la ventana emergente que implementa un pop-up.
         */
        function conf__form (toba_ei_formulario $form){
            $form->colapsar();
        }

}

?>