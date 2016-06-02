<?php
class ci_seleccionar_organizacion extends toba_ci
{
        protected $s__where;
        
        //---- Filtro -----------------------------------------------------------------------
        
        function evt__filtro__filtrar (){
            $this->s__where=$this->dep('filtro')->get_sql_where('OR');
        }
        
	//---- Cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
            if(isset($this->s__where)){
                $cuadro->desactivar_modo_clave_segura();
                $cuadro->set_datos($this->dep('datos')->tabla('organizacion')->get_organizaciones($this->s__where));
            }
	}

	function evt__cuadro__seleccion($datos)
	{
            $this->dep('datos')->cargar($datos);
	}

}

?>