<?php
class ci_reportar_incidencia extends toba_ci
{
    
    protected $s__id_sede;
    protected $s__id_aula;
    protected $s__where;
    protected $s__incidencias=array();
    protected $s__datos_aula;
    
    //-----------------------------------------------------------------------------------
    //---- Pant Edicion -----------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    
    function conf__pant_edicion (toba_ei_pantalla $pantalla){
        $this->pantalla()->tab('pant_incidencia')->desactivar();
    }
    
    //---- Filtro -----------------------------------------------------------------------
        
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
        $this->s__datos_aula=$datos;
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
    
    function get_tipos_incidencias ($tipo){
        $sql="SELECT nombre
              FROM material 
              WHERE tipo='$tipo'";
        
        return toba::db('rukaja')->consultar($sql);
    }
    
    function evt__form___agregar ($datos){
        if($this->operar_sobre_incidencia($datos, 'v')){
            $this->operar_sobre_incidencia($datos, 'e');
            $this->s__incidencias[]=$datos;
        }else{
            $this->s__incidencias[]=$datos;
        }
    }
    
    function conf__cuadro_ (toba_ei_cuadro $cuadro){
        if(count($this->s__incidencias)>0)
            $cuadro->set_datos($this->s__incidencias);
    }
    
    /*
     * Guardamos todas las incidencias en la db.
     */
    function evt__cuadro___registrar ($datos){
        
        if(count($this->s__incidencias)==0){
            $mensaje=" No existen incidencias seleccionadas ";
            toba::notificacion()->agregar($mensaje, 'info');
            return ;
        }
        //--Configuramos la zona horaria de Buenos_Aires. Por defecto toma la zona horaria de Sao_Paulo y nos
        //devuelve una hora incorrecta.
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        
        $fecha=date('d-m-Y');
        $hora=date('G:i:s');
        
        $incidencia=array(
            'fecha' => $fecha,
            'hora' => $hora,
            'id_aula' => $this->s__id_aula,
            'id_sede' => $this->s__id_sede,
            'estado' => 'PENDIENTE'
        );
        
        $this->dep('datos')->tabla('incidencia')->nueva_fila($incidencia);
        $this->dep('datos')->tabla('incidencia')->sincronizar();
        $this->dep('datos')->tabla('incidencia')->resetear();
        
        $id_incidencia=  recuperar_secuencia('incidencia_id_incidencia_seq');       
                        
        //--Guardamos informacion en la tabla cantidad_material.
        foreach($this->s__incidencias as $clave=>$incidencia){
            $incidencia['id_incidencia']=$id_incidencia;
            $this->dep('datos')->tabla('cantidad_material')->nueva_fila($incidencia);
            $this->dep('datos')->tabla('cantidad_material')->sincronizar();
            $this->dep('datos')->tabla('cantidad_material')->resetear();
        }
        
        //--Limpiamos el areglo s__incidencias.
        $this->s__incidencias=array();
    }

    //---- Form Datos --------------------------------------------------------------------
    
    function conf__form_datos (toba_ei_formulario $form){
        $form->set_datos($this->s__datos_aula);
    }
    
    function evt__cuadro___eliminar ($datos){
        $this->operar_sobre_incidencia($datos, 'e');
    }
    
    /*
     * Esta funcion puede eliminar o verificar repeticion de eltos.
     */
    function operar_sobre_incidencia ($datos, $accion){
        $existe=FALSE;
        //--Debemos usar foreach porque unset genera huecos en el arreglo, lo que hace que sea inapropiado usar
        //while porque perdemos continuidad en los indices.
        foreach($this->s__incidencias as $clave=>$incidencia){
            
            $tipo=$datos['tipo'];
            $nombre=$datos['nombre'];
            
            switch($accion){
                case 'e' : if(strcmp($incidencia['tipo'], $tipo)==0 && strcmp($incidencia['nombre'], $nombre)==0){
                                unset($this->s__incidencias[$clave]);
                           }
                           break;
                           
                case 'v' : if(strcmp($incidencia['tipo'], $tipo)==0 && strcmp($incidencia['nombre'], $nombre)==0){
                                $existe=TRUE;
                           }
                           break;
            }
          
        }
        
        return $existe;
    }

}

?>