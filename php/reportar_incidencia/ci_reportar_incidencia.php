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
        $this->s__id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
                          
        $aulas=$this->dep('datos')->tabla('aula')->get_aulas_incidencia($this->s__where, $this->s__id_sede);

        if(count($aulas)>0){
            $cuadro->set_datos($aulas);
        }else{
            $mensaje=" No existen aulas registradas en el sistema. ";
            toba::notificacion()->agregar($mensaje);
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
    
    function evt__volver (){
        $this->set_pantalla('pant_edicion');
    }
    
    //---- Formulario -------------------------------------------------------------------

    function evt__form_ingreso_incidencia__registrar_incidencia($datos)
    {
        print_r("Este es el contenido de datos: <br>");
        print_r($datos);
        $incidencia=array(
            'fecha' => date('d-m-Y'),
            'hora' => date('hh:mm'),
            'id_aula' => $this->s__id_aula,
            'estado' => 'PENDIENTE'
        );
        
        $this->dep('datos')->tabla('incidencia')->nueva_fila($incidencia);
        $this->dep('datos')->tabla('incidencia')->sincronizar();
        $this->dep('datos')->tabla('incidencia')->resetear();
        
        $id_incidencia=  recuperar_secuencia('incidencia_id_incidencia_seq');
        $incidencias=array();
        //Vamos a utilizar el tipo como postfijo.
        if($datos['conductor'] == 1){
            $incidencias[]=array(
                'tipo' => 'ILUMINACION',
                'nombre' => 'CONDUCTOR',
                'id_incidencia' => $id_incidencia,
                'cantidad' => $datos['cantidad_conductor'],
                'descripcion' => $datos['desc_conductor'],
            );
        }
        if($datos['interruptor'] == 1){
            $incidencias[]=array(
                'tipo' => 'ILUMINACION',
                'nombre' => 'INTERRUPTOR',
                'id_incidencia' => $id_incidencia,
                'cantidad' => $datos['cantidad_interruptor'],
                'descripcion' => $datos['desc_interruptor'],
            );
        }
        if($datos['tubos'] == 1){
            $incidencias[]=array(
                'tipo' => 'ILUMINACION',
                'nombre' => 'TUBO FLUORESCENTE',
                'id_incidencia' => $id_incidencia,
                'cantidad' => $datos['cantidad_tubos'],
                'descripcion' => $datos['desc_tubos'],
            );
        }
        if($datos['tomacorriente'] == 1){
            $incidencias[]=array(
                'tipo' => 'ILUMINACION',
                'nombre' => 'TOMACORRIENTE',
                'id_incidencia' => $id_incidencia,
                'cantidad' => $datos['cantidad_tomacorriente'],
                'descripcion' => $datos['desc_tomacorriente'],
            );
        }
        if($datos['perdida_gas'] == 1){
            
        }
        if($datos['sin_funcionamiento'] == 1){
            
        }
        if($datos['pupitre'] == 1){
            
        }
        if($datos['puerta_sin_cerradura'] == 1){
            
        }
        if($datos['aula_sin_escritorio'] == 1){
            
        }
        
        //Guardamos informacion en la tabla cantidad_material.
        foreach($incidencias as $clave=>$incidencia){
            $this->dep('datos')->tabla('cantidad_material')->nueva_fila($incidencia);
            $this->dep('datos')->tabla('cantidad_material')->sincronizar();
            $this->dep('datos')->tabla('cantidad_material')->resetear();
        }
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