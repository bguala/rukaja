<?php
class ci_registrar_aula extends toba_ci
{
        
        protected $s__sede;
        protected $s__id_sede;
        protected $s__formatos_admitidos=array('image/jpeg','image/jpg','image/png');
        protected $s__error=array();                                                        //guarda las aulas que tienen un archivo adjunto con formato incorrecto
        
	//--------------------------------------------------------------------------------------------        
        //---- Pant ML -------------------------------------------------------------------------------
        //--------------------------------------------------------------------------------------------
        
        //---- Formulario ML -------------------------------------------------------------------------
        
        function conf__form_ml (toba_ei_formulario_ml $form_ml){
            
            if(!isset($this->s__id_sede)){                  
                  $this->s__sede=  utf8_decode('Neuquén Capital');
                  $this->s__id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
            }
            
            $form_ml->ef('sede')->set_estado_defecto($this->s__sede);
                        
        }
        
        function evt__form_ml__aceptar ($datos){
                        
            $this->agregar_datos_aula(&$datos);
            
            foreach ($datos as $clave=>$aula){
                //EN ESTE CASO:
                //Si estamos dando de alta aulas $accion contiene un valor nulo si usamos apex_analisis_fila. Para
                //saber si estamos dando de alta eltos. desde un form_ml debemos usar el indice x_dbr_clave, 
                //que empieza en 156.
                //$accion=$aula['apex_analisis_fila'];
                //Pero si comparamos un valor nulo con "A" strcmp devuelve un numero negativo, que se considera
                //TRUE. Por lo tanto esto nos si sirve para pasar la condicion y guardar el aula en la base de
                //datos.
                //if(strcmp($accion, "A")){
                //Finalmente no tiene sentido tener una condicion porque este formulario ml solamente se 
                //utiliza para guardar aulas en la base de datos, no permite hacer bajas y modificaciones.
                    $this->registrar_aula($aula);                    
                //}
            }
            
            if(count($this->s__error)>0){
                $cadena=$this->generar_cadena();
                $mensaje="Para las siguientes aulas $cadena no se registraron sus imágenes debido a que no tienen un formato"
                        . " adecuado. Los formatos admitidos por el sistema son jpeg, jpg y png";
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
            }
        }
        
        /*
         * Esta funcion arma una cadena con las aulas que tenian un archivo con formato incorrecto.
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
                    
            if(isset($aula['imagen']) && $this->tiene_formato_admitido($aula['imagen']['type'])){
                                                             
                $fp=  fopen($aula['imagen']['tmp_name'], 'rb');
                       
                $this->dep('datos')->tabla('aula')->set_blob('imagen', $fp);

            }else{
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
        
        function agregar_datos_aula ($datos){
            foreach($datos as $clave=>$aula){
                $datos[$clave]['id_sede']=$this->s__id_sede;
                $datos[$clave]['eliminada']=FALSE;
            }
        }
        
        function get_tipos (){
            $sql="SELECT id_tipo, descripcion 
                  FROM tipo ";
            return toba::db('rukaja')->consultar($sql);
        }

}

?>