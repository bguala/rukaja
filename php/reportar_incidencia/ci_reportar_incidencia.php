<?php
class ci_reportar_incidencia extends toba_ci
{
    
    protected $s__id_sede;
    protected $s__id_aula;
    protected $s__where;
    
    //-----------------------------------------------------------------------------------
    //---- Pant Edicion -----------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    
    function conf__pant_edicion (toba_ei_pantalla $pantalla){
        $this->pantalla()->tab('pant_incidencia')->desactivar();
    }
    
    //-----------------------------------------------------------------------------------
    //---- Filtro -----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    
    function evt__filtro__filtrar (){
        $this->s__where=$this->dep('filtro')->get_sql_where('OR');
    }
    //---- Cuadro -----------------------------------------------------------------------

    function conf__cuadro(toba_ei_cuadro $cuadro)
    {
        if(isset($this->s__where)){
            $cuadro->set_datos();
        }else{
            $this->s__id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
            $aulas=$this->dep('datos')->tabla('aula')->get_aulas_incidencia($this->s__id_sede);

            if(count($aulas)>0){
                $cuadro->set_datos($aulas);
            }else{
                $mensaje=" No existen aulas registradas en el sistema. ";
                toba::notificacion()->agregar($mensaje);
            }
        }
    }

    function evt__cuadro__seleccion($datos)
    {
        $this->s__id_aula=$datos['id_aula'];
        $this->set_pantalla('pant_incidencia');
    }

    //-----------------------------------------------------------------------------------
    //---- Pant Incidencia --------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    
    function conf__pant_incidencia (toba_ei_pantalla $pantalla){
        $this->pantalla()->tab('pant_edicion')->desactivar();
    }
    
    //---- Formulario -------------------------------------------------------------------

    function conf__formulario(toba_ei_formulario $form)
    {
            if ($this->dep('datos')->esta_cargada()) {
                    $form->set_datos($this->dep('datos')->tabla('incidencia')->get());
            }
    }

    function evt__formulario__alta($datos)
    {
            $this->dep('datos')->tabla('incidencia')->set($datos);
            $this->dep('datos')->sincronizar();
            $this->resetear();
    }

    function evt__formulario__modificacion($datos)
    {
            $this->dep('datos')->tabla('incidencia')->set($datos);
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