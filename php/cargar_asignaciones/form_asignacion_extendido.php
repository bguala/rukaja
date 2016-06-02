<?php
class form_asignacion_extendido extends toba_ei_formulario
{
    function extender_objeto_js() {
        echo "{$this->objeto_js}.evt__tipo__validar = function () {
            var estado=this.ef('tipo').get_estado().toString();
            
            switch (estado){
            
             case 'Definitiva' : this.ef('fecha_inicio').ocultar();
                                 this.ef('fecha_inicio').set_obligatorio(false);
                                 this.ef('fecha_fin').ocultar();
                                 this.ef('fecha_fin').set_obligatorio(false);
                                 this.ef('dias').ocultar();
                                 this.ef('dias').set_obligatorio(false);
                                 this.ef('dia_semana').mostrar();
                                 this.ef('dia_semana').set_obligatorio(true);
                                 break;
                                 
             case 'Periodo' : this.ef('fecha_inicio').mostrar();
                              this.ef('fecha_inicio').set_obligatorio(true);
                              this.ef('fecha_fin').mostrar();
                              this.ef('fecha_fin').set_obligatorio(true);
                              this.ef('dias').mostrar();
                              this.ef('dias').set_obligatorio(true);
                              this.ef('dia_semana').ocultar();
                              this.ef('dia_semana').set_obligatorio(false);
                              break;
                              
             case 'nopar' : this.ef('fecha_inicio').mostrar();
                              this.ef('fecha_inicio').set_obligatorio(true);
                              this.ef('fecha_fin').mostrar();
                              this.ef('fecha_fin').set_obligatorio(true);
                              this.ef('dias').mostrar();
                              this.ef('dias').set_obligatorio(true);
                              this.ef('dia_semana').mostrar();
                              this.ef('dia_semana').set_obligatorio(true);
                              break;
             
            }
            
            return true;
        }
        
        
               
        {$this->objeto_js}.evt__tipo_asignacion__validar = function (){
            var tipo_asignacion=this.ef('tipo_asignacion').get_estado().toString();
            
            switch(tipo_asignacion){
            case 'EXAMEN FINAL' :
            case 'EVENTO' :
            case 'CONSULTA' :
            case 'EXAMEN PARCIAL' ://Debemos ocultar el campo dia_semana.
                                   this.ef('dia_semana').ocultar();
                                   this.ef('dia_semana').set_obligatorio(false);
                                   
                                   //Pero debemos mostrar los campos que se ocultaron con las otras opciones del 
                                   //combo.
                                   this.ef('fecha_inicio').mostrar();
                                   this.ef('fecha_inicio').set_obligatorio(true);
                                   this.ef('fecha_fin').mostrar();
                                   this.ef('fecha_fin').set_obligatorio(true);
                                   this.ef('tipo').set_estado('nopar');
                                   this.ef('tipo').set_solo_lectura(false);
                                   this.ef('dias').mostrar();
                                   this.ef('dias').set_obligatorio(true);
                                   
                                   //Habilitamos el boton 'Analizar Periodo'.
                                   this.activar_boton('agregar_dias');
                                   
                                   break;
                        
            case 'CURSADA' : 
                            //Debemos mostrar los campos ocultados en las otras opciones del combo.
                            this.ef('dia_semana').mostrar();
                            this.ef('dia_semana').set_obligatorio(true);
                            
                            //Ocultamos campos innecesaios para esta opcion.
                            this.ef('fecha_inicio').ocultar();
                            this.ef('fecha_inicio').set_obligatorio(false);
                            this.ef('fecha_fin').ocultar();
                            this.ef('fecha_fin').set_obligatorio(false);
                            this.ef('tipo').set_estado('Definitiva');
                            this.ef('tipo').set_solo_lectura(true);
                            this.ef('dias').ocultar();
                            this.ef('dias').set_obligatorio(false);
                            
                            //Debemos desactivar el boton 'Analizar Periodo'.
                            this.desactivar_boton('agregar_dias');
                            
                            break;
                            
            case 'nopar' :
                          this.ef('dia_semana').mostrar();
                          this.ef('dia_semana').set_obligatorio(true);
                          
                          this.ef('fecha_inicio').mostrar();
                          this.ef('fecha_inicio').set_obligatorio(true);
                          this.ef('fecha_fin').mostrar();
                          this.ef('fecha_fin').set_obligatorio(true);
                          this.ef('tipo').set_estado('nopar');
                          this.ef('tipo').set_solo_lectura(false);
                          this.ef('dias').mostrar();
                          this.ef('dias').set_obligatorio(true);
                          
                          this.activar_boton('agregar_dias');
                          break;
                
            }

            return true;
        }
        
        
        
        {$this->objeto_js}.evt__hora_fin__validar = function (){
            
            var tipo=this.ef('tipo').get_estado().toString();
            var id_periodo=this.ef('id_periodo').get_estado();
            
            if(id_periodo == 'nopar'){
                alert('Debe elegir un periodo de tiempo');
                return false;
            }
            else{
                //get_estado devuelve un arreglo con los parametros asociados a la hora del ef_editable_hora
                var hora_fin=this.ef('hora_fin').get_estado(); 
                var hora_inicio=this.ef('hora_inicio').get_estado();

                var dia=this.ef('dia_semana').get_estado().toString();

                var horario=[ hora_inicio, hora_fin, id_periodo, tipo, dia ];
                //alert(horario);
                this.controlador.ajax('guardar_horario_en_sesion', horario, this, this.atender_respuesta);

                return true;
            }
            
                       
        } 
        
        {$this->objeto_js}.atender_respuesta = function (respuesta) {
            //aqui no hacemos nada, simplemete evitamos alterar el mecanismo ajax definido.
            //alert(respuesta.get_cadena('clave'));
            return true;
        }
        
        
        
        
        
        " ;
                        
        
    }
}
?>