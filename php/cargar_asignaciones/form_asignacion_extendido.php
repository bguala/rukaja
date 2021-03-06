<?php
class form_asignacion_extendido extends toba_ei_formulario
{
    function extender_objeto_js() {
        echo "
        
        //Simulamos un ini__operacion a nivel JS.
        window.setTimeout('ocultar_campos()', 1000);
        
        function ocultar_campos () {
            var form={$this->objeto_js};
            var tipo_asignacion=form.ef('tipo_asignacion').get_estado().toString();
            
            switch (tipo_asignacion){
                case 'CURSADA'        : form.ef('fecha_inicio').ocultar();
                                        form.ef('fecha_inicio').set_obligatorio(false);
                                        form.ef('fecha_fin').ocultar();
                                        form.ef('fecha_fin').set_obligatorio(false);
                                        form.ef('dias').ocultar();
                                        form.ef('dias').set_obligatorio(false);
                                        form.ef('tipo').set_solo_lectura(true);
                                        break;
                
                case 'EXAMEN PARCIAL' : 
                case 'EXAMEN FINAL'   : form.ef('fecha_fin').ocultar();
                                        form.ef('fecha_fin').set_obligatorio(false);
                                        form.ef('dias').ocultar();
                                        form.ef('dias').set_obligatorio(false);
                                        form.ef('dia_semana').ocultar();
                                        form.ef('dia_semana').set_obligatorio(false);
                                        form.ef('tipo').set_solo_lectura(true);
                                        break;
                
                case 'CONSULTA'       : 
                case 'EVENTO'         : form.ef('dia_semana').ocultar();
                                        form.ef('dia_semana').set_obligatorio(false);
                                        form.ef('tipo').set_solo_lectura(true);
                                        break;
            }
            
        }
        
        {$this->objeto_js}.evt__dia_semana__validar = function () {
            return this.disparar_llamada_ajax();
        }
               
        {$this->objeto_js}.evt__tipo_asignacion__validar = function (){
            var tipo_asignacion=this.ef('tipo_asignacion').get_estado().toString();
            
            switch(tipo_asignacion){
            case 'EXAMEN PARCIAL' : 
            case 'EXAMEN FINAL'   : //Debemos ocultar el campo dia_semana.
                                    this.ef('dia_semana').ocultar();
                                    this.ef('dia_semana').set_obligatorio(false);
                                    this.ef('fecha_fin').ocultar();
                                    this.ef('fecha_fin').set_obligatorio(false);
                                    this.ef('dias').ocultar();
                                    this.ef('dias').set_obligatorio(false);
                                    
                                    this.ef('tipo').set_estado('Periodo');
                                    this.ef('tipo').set_solo_lectura(true);
                                    break;
            case 'EVENTO'   :
            case 'CONSULTA' :      //Debemos ocultar el campo dia_semana.
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
                                   //this.activar_boton('agregar_dias');
                                   
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
                            
//                            var nodo=this.ef('finalidad').nodo();
//                            alert(nodo.id);
                            //Debemos desactivar el boton 'Analizar Periodo'.
                            //this.desactivar_boton('agregar_dias');
                            
                            break;
                            
            case 'nopar' ://Restauramos el estado inicial del formulario.
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
                          
                          //this.activar_boton('agregar_dias');
                          break;
                
            }

            return true;
        }
        
        
        
        {$this->objeto_js}.evt__hora_fin__validar = function (){             
                return this.disparar_llamada_ajax();        
        } 
        
        {$this->objeto_js}.atender_respuesta = function (respuesta) {
            //Aqui no hacemos nada, simplemete evitamos alterar el mecanismo ajax definido por el framework.
            //alert(respuesta.get_cadena('clave'));
            return true;
        }
        
        {$this->objeto_js}.evt__fecha_fin__validar = function (){
            var tipo=this.ef('tipo').get_estado();
            if(tipo == 'Periodo'){
            var tipo_asignacion=this.ef('tipo_asignacion').get_estado().toString();
            var fecha_inicio=this.ef('fecha_inicio').fecha();
            
                switch(tipo_asignacion){
                    case 'EXAMEN PARCIAL' : 
                    case 'EXAMEN FINAL'   : return true;

                    case 'CONSULTA'       :
                    case 'EVENTO'         : 
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
                 }//Cierre del switch.
            }
            
            return true;
        }
        
        {$this->objeto_js}.evt__fecha_inicio__validar = function (){
            var tipo=this.ef('tipo').get_estado();
            if(tipo == 'Periodo'){
                var fecha_inicio=this.ef('fecha_inicio').fecha();
                var fecha_fin=this.ef('fecha_fin').fecha();

                if(fecha_fin != null && fecha_inicio.getTime()==fecha_fin.getTime()){
                    this.ef('fecha_inicio').set_error('La fecha de inicio y fin no pueden ser iguales');
                    return false;
                }
            }
            
            return true;
        }
        
        //Para ambos tipos de asignaciones debemos guardar los datos del formulario en sesion.
        {$this->objeto_js}.evt__hora_inicio__validar = function (){
                return this.disparar_llamada_ajax();
        }

          //Agrupamos las sentencias usadas para guardar datos en sesion mediante una llamada ajax.
          //Siempre se deben guardar los mismos datos. Estos dependen del tipo de asignacion.
          //Internamente distiguimos entre asignacion definitivas y periodicas.
          {$this->objeto_js}.disparar_llamada_ajax = function (){
            var tipo=this.ef('tipo').get_estado().toString();
            var id_periodo=this.ef('id_periodo').get_estado();
            
            if(id_periodo == 'nopar'){
                //alert('Debe elegir un periodo de tiempo');
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
                                       var tipo_asignacion=this.ef('tipo_asignacion').get_estado().toString();
                                       var fecha_inicio;
                                       var fecha_fin;
                                       switch(tipo_asignacion){
                                          case 'EXAMEN PARCIAL' : 
                                          case 'EXAMEN FINAL'   : fecha_inicio=this.ef('fecha_inicio').fecha();
                                                                  //Duplicamos la fecha para no alterar la llamada ajax, para evitar posibles problemas 
                                                                  //con el formulario.
                                                                  fecha_fin=this.ef('fecha_inicio').fecha();
                                                                  break;
                                          
                                          case 'CONSULTA' : 
                                          case 'EVENTO'         : fecha_inicio=this.ef('fecha_inicio').fecha();
                                                                  fecha_fin=this.ef('fecha_fin').fecha();
                                                                  break;
          
                                       }
                                                                         
                                       //array( 0 => hora_inicio, 1 => hora_fin, 2 => id_periodo, 3 => tipo, 4 => lista_dias, 
                                       // 5 => fecha_inicio, 6 => fecha_fin, 7 => tipo_asignacion, 8 => Dia1, 9 => Dia2, 10 => Dia3, 11 => Dia4, 12 => Dia5, 13 => Dia6, 14 => Dia7 );
                                       var horario=[ hora_inicio, hora_fin, id_periodo, tipo, lista_dias, fecha_inicio.toDateString(), fecha_fin.toDateString(), tipo_asignacion, lista_dias[0], lista_dias[1], lista_dias[2], lista_dias[3], lista_dias[4], lista_dias[5], lista_dias[6] ];
                                       
                                       this.controlador.ajax('guardar_horario_en_sesion', horario, this, this.atender_respuesta);
                                       break;
                }
                

                return true;
            }
          }
        
        
        
        " ;
                        
        
    }
}
?>