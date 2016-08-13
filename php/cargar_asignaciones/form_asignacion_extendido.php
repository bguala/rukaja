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
                                   //this.ef('tipo').set_estado('nopar');
                                   this.ef('tipo').set_estado('Periodo');
                                   this.ef('tipo').set_solo_lectura(true);
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
                
                switch(tipo){
                    case 'Definitiva': var dia=this.ef('dia_semana').get_estado().toString();
                                       
                                       //array( 0 => hora_inicio, 1 => hora_fin, 2 => id_periodo, 3 => tipo, 4 => dia);
                                       var horario=[ hora_inicio, hora_fin, id_periodo, tipo, dia ];
                                       //alert(horario);
                                       this.controlador.ajax('guardar_horario_en_sesion', horario, this, this.atender_respuesta);
                                       break; 
                    
                    case 'Periodo':    var lista_dias=this.ef('dias').get_estado();
                                       
                                       //Si usamos get_estado(), por algun motivo que desconozco, la fecha se 
                                       //obtiene corrupta. Ese string corrupto no se puede convertir a time, lo 
                                       //que nos complica el proceso de obtencion de fechas pertenecientes a un 
                                       //periodo. Para que esto no ucurra debemos usar la funcion fecha() para
                                       //obtener un objeto date puro, con fecha, hora y zona horaria. 
                                       //Usamos la funcion toDateString(), para pasar a string la parte 
                                       //correspondiente a la fecha. Ese string-fecha viaja al servidor y
                                       //parece que funciona.
                                       var fecha_inicio=this.ef('fecha_inicio').fecha();
                                       var fecha_fin=this.ef('fecha_fin').fecha();
                                       
                                       //array( 0 => hora_inicio, 1 => hora_fin, 2 => id_periodo, 3 => tipo, 4 => lista_dias, 
                                       // 5 => fecha_inicio, 6 => fecha_fin, 7 => Dia1, 8 => Dia2, 9 => Dia3, 10 => Dia4, 11 => Dia5, 12 => Dia6 );
                                       var horario=[ hora_inicio, hora_fin, id_periodo, tipo, lista_dias, fecha_inicio.toDateString(), fecha_fin.toDateString(), lista_dias[0], lista_dias[1], lista_dias[2], lista_dias[3], lista_dias[4], lista_dias[5], lista_dias[6] ];
                                       
                                       this.controlador.ajax('guardar_horario_en_sesion', horario, this, this.atender_respuesta);
                                       break;
                }
                

                return true;
            }
            
                       
        } 
        
        {$this->objeto_js}.atender_respuesta = function (respuesta) {
            //Aqui no hacemos nada, simplemete evitamos alterar el mecanismo ajax definido por el framework.
            //alert(respuesta.get_cadena('clave'));
            return true;
        }
        
        {$this->objeto_js}.evt__fecha_fin__validar = function (){
            var fecha_inicio=this.ef('fecha_inicio').fecha();
            
            //Si no cargamos una fecha de inicio obtenemos un null.
            if(fecha_inicio == null){
                this.ef('fecha_fin').set_error('Seleccione fecha de inicio');
                return false;
            }
            
            var fecha_fin=this.ef('fecha_fin').fecha();
            
            if( fecha_fin < fecha_inicio){
                this.ef('fecha_fin').set_error('La fecha de fin debe ser mayor a la fecha de inicio');
                return false;
            }
            if( fecha_fin.getTime() == fecha_inicio.getTime() ){
                this.ef('fecha_fin').set_error('La fecha de inicio no puede ser igual a la fecha de fin');
                return false;
            }
            
            return true;
        }
        
//        {$this->objeto_js}.evt__fecha_inicio__validar = function (){
//            var fecha_inicio=this.ef('fecha_inicio').fecha();
//            var fecha_fin=this.ef('fecha_fin').fecha();
//            
//            if(fecha_fin != null && fecha_inicio.getTime()==fecha_fin.getTime()){
//                this.ef('fecha_inicio').set_error('La fecha de inicio y fin no pueden ser iguales');
//                return false;
//            }
//            
//            
//        }
        
        
        
        " ;
                        
        
    }
}
?>