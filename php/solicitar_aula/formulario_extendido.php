<?php
class formulario_extendido extends toba_ei_formulario
{
    function extender_objeto_js() {
                
        echo "
        
        //La funcion setTimeout se ejecuta solamente una vez. En este caso cuando se carga el documento html
        //transcurren 1,2 segundos y se ejecuta la operacion ocultar_campos. Considero que 1,2 segundos es el tiempo
        //adecuado para realizar el chequeo correspondiente. El tiempo se debe especificar en milisegundos.
        //setInterval(funcion, tiempo) se ejecuta periodicamente, su funcionamiento es similar a un timer por SW.
        
        var id= setTimeout('ocultar_campos()', 1200);
        
        function ocultar_campos (){
            var form={$this->objeto_js};
            var tipo_agente=form.ef('tipo_agente').get_estado().toString();
            var tipo_asignacion=form.ef('tipo_asignacion').get_estado().toString();
            
            switch(tipo_agente){
                case 'Docente'      : //Ocultamos los campos pertenecientes a organizacion.
                                      
                                      form.ef('org').ocultar();
                                      form.ef('nombre_org').ocultar();
                                      form.ef('telefono_org').ocultar();
                                      form.ef('email_org').ocultar();
                                      break;
                                 
                case 'Organizacion' : //Ocultamos los campos pertenecientes a docente.
                                      
                                      form.ef('legajo').ocultar();
                                      form.ef('nombre').ocultar();
                                      form.ef('apellido').ocultar();
                                      break;
                                      
                default : 
                          break;
            }
            
            switch(tipo_asignacion){
                case 'EXAMEN PARCIAL' : 
                case 'EXAMEN FINAL'   : form.ef('fecha_fin').ocultar();
                                        form.ef('dias').ocultar();
                                        form.ef('tipo_asignacion').set_solo_lectura(true);
                                        form.ef('tipo_nombre').ocultar();
                                        break;
                
                case 'CONSULTA'       : 
                case 'EVENTO'         : form.ef('tipo_asignacion').set_solo_lectura(true);
                                        form.ef('tipo_nombre').ocultar();
                                        break;
            }
            
            
        }
        
        {$this->objeto_js}.evt__tipo_agente__validar = function () {
            var tipo=this.ef('tipo_agente').get_estado();
            
            switch(tipo){
                case 'Docente' : //Ocultamos campos pertenecientes a una organizacion.
                                 this.ef('org').ocultar();
                                 this.ef('org').set_estado('');
                                 this.ef('nombre_org').ocultar();
                                 this.ef('nombre_org').set_estado('');
                                 this.ef('telefono_org').ocultar();
                                 this.ef('telefono_org').set_estado('');
                                 //if (!/^([0-9])*[.]?[0-9]*$/.test(numero)) para validar numero decimal
                                 this.ef('email_org').ocultar();
                                 this.ef('email_org').set_estado('');
                                 
                                 
                                 //Mostramos campos pertenecientes a un docente.
                                 this.ef('legajo').mostrar();
                                 this.ef('legajo').set_estado('');
                                 

                                 this.ef('nombre').mostrar();
                                 this.ef('nombre').set_solo_lectura(true);
                                 this.ef('apellido').mostrar();
                                 this.ef('apellido').set_solo_lectura(true);
                                 
                                 break;
                
                case 'Organizacion' : //Mostramos campos pertenecientes a una organizacion
                                      this.ef('nombre_org').mostrar();
                                      this.ef('nombre_org').set_solo_lectura(false);
                                      this.ef('telefono_org').mostrar();
                                      this.ef('telefono_org').set_solo_lectura(false);
                                      this.ef('email_org').mostrar();
                                      this.ef('email_org').set_solo_lectura(false);
                                      this.ef('org').mostrar();
                                      
                                      //Ocultamos campos pertenecientes a un docente y borramos informacion 
                                      //cargada en ellos.
                                      this.ef('legajo').ocultar();
                                      this.ef('legajo').set_estado('');
                                      
                                      this.ef('nombre').ocultar();
                                      this.ef('nombre').set_estado(''),
                                      this.ef('apellido').ocultar();
                                      this.ef('apellido').set_estado('');
                                      
                                      break;
                
                case 'nopar' : //Restauramos el formulario original y borramos informacion cargada en los campos.
                               this.ef('org').mostrar();
                               this.ef('org').set_estado('');
                               this.ef('nombre_org').mostrar();
                               this.ef('nombre_org').set_estado('');
                               this.ef('nombre_org').set_solo_lectura(true);
                               this.ef('telefono_org').mostrar();
                               this.ef('telefono_org').set_estado('');
                               this.ef('telefono_org').set_solo_lectura(true);
                               this.ef('email_org').mostrar();
                               this.ef('email_org').set_estado('');
                               this.ef('email_org').set_solo_lectura(true);
                               
                               this.ef('legajo').mostrar();
                               this.ef('legajo').set_estado('');
                               
                               this.ef('nombre').mostrar();
                               this.ef('nombre').set_estado('');
                               this.ef('nombre').set_solo_lectura(true);
                               this.ef('apellido').mostrar();
                               this.ef('apellido').set_estado('');
                               this.ef('apellido').set_solo_lectura(true);
                               break;
            }
            
            return true;
        }
        
        {$this->objeto_js}.evt__tipo__validar = function (){
            var tipo=this.ef('tipo').get_estado().toString();
            
            if(tipo == 'OTRO'){
                this.ef('tipo_nombre').mostrar();
                this.ef('tipo_nombre').set_solo_lectura(false);
                this.ef('tipo_nombre').set_obligatorio(true);
            }
            else{
                this.ef('tipo_nombre').ocultar();
                this.ef('tipo_nombre').set_obligatorio(false);
                this.ef('tipo_nombre').set_estado('');
            }
            
            return true;

        }
        
        {$this->objeto_js}.evt__hora_inicio__validar = function (){
            var hora_inicio_disponible=this.ef('inicio').hora();
            var hora_fin_disponible=this.ef('fin').hora();
            
            var hora_inicio=this.ef('hora_inicio').hora();
            var hora_fin=this.ef('hora_fin').hora();
            
            if(!this.es_mayor_x(hora_inicio, hora_inicio_disponible, '>=')){
                this.ef('hora_inicio').set_error('La hora de inicio '+(this.ef('hora_inicio').get_estado())+' hs debe ser mayor o igual que la hora de inicio disponible '+(this.ef('inicio').get_estado())+' hs');
                return false;
            }
            if(!this.es_mayor_x(hora_fin_disponible, hora_inicio, '>')){
                this.ef('hora_inicio').set_error('La hora de inicio '+(this.ef('hora_inicio').get_estado())+' hs debe ser menor que la hora de fin disponible '+(this.ef('fin').get_estado())+' hs');
                return false;
            }
            if ((hora_fin != ' ') && (!this.es_mayor_x(this.ef('hora_fin').hora(), hora_inicio, '>'))){    
                this.ef('hora_inicio').set_error('La hora de inicio '+(this.ef('hora_inicio').get_estado())+' hs debe ser menor que la hora de fin '+(this.ef('hora_fin').get_estado())+' hs');
                return false;
            }
            
            return true;
            
        }
        
        //verifica si hora_inicio es mayor-igual o mayor estricto a hora_fin
        {$this->objeto_js}.es_mayor_x = function (hora_inicio, hora_fin, op){
            var hi=hora_inicio.getHours();
            var mi=hora_inicio.getMinutes();
            
            var hf=hora_fin.getHours();
            var mf=hora_fin.getMinutes();
            
            if(op == '>'){
                if(hi > hf){
                    return true;
                }
                else{
                    if(hi == hf){
                        if(mi > mf){
                            return true;
                        }
                        else{
                            return false;
                        }
                    }
                }
            }
            else{
                if(hi >= hf){
                    if(hi == hf){
                        if(mi >= mf){
                            return true;
                        }
                        else{
                            return false;
                        }
                    }
                    else{
                        return true; 
                    }           

                }
                else{
                    return false;
                }
            }
            
        }
        
        {$this->objeto_js}.evt__hora_fin__validar = function (){
            var hora_inicio_disponible=this.ef('inicio').hora();
            var hora_fin_disponible=this.ef('fin').hora();
            
            var hora_fin=this.ef('hora_fin').hora();
            var hora_inicio=this.ef('hora_inicio').hora();
                        
            if(!this.es_mayor_x(hora_fin, hora_inicio_disponible, '>')){
                this.ef('hora_fin').set_error('La hora de fin '+(this.ef('hora_fin').get_estado())+' hs debe ser mayor que la hora de inicio disponible '+(this.ef('hora_inicio_disponible').get_estado())+' hs');
                return false;
            }
            if(!this.es_mayor_x(hora_fin_disponible, hora_fin, '>=')){
                this.ef('hora_fin').set_error('La hora de fin '+(this.ef('hora_fin').get_estado())+' hs debe ser menor o igual a la hora fin disponible '+(this.ef('fin').get_estado())+' hs');
                return false;
            }
            if(!this.es_mayor_x(hora_fin, hora_inicio, '>')){
                this.ef('hora_fin').set_error('La hora de fin '+(this.ef('hora_fin').get_estado())+' hs debe ser mayor que la hora de inicio '+(this.ef('hora_inicio').get_estado())+' hs');
                return false;
            }
            
            return true;
        }
            
        {$this->objeto_js}.evt__org__validar = function (){
            var tipo_agente=this.ef('tipo_agente').get_estado().toString();
            if(tipo_agente == 'Organizacion'){
            
            this.controlador.ajax_cadenas('autocompletar_org', this.ef('org').get_estado(), this, this.atender_respuesta);
            
            }
            return true;
        }
        
        {$this->objeto_js}.atender_respuesta = function (respuesta) {
                
                var agente = respuesta.get_cadena('agente');
                
                //Con esta condicion evitamos eliminar el id del responsable de aula.
                if(agente == '2'){
                    return false;
                }

                if(agente == 'docente'){
                
                    var nombre = respuesta.get_cadena('nombre');
                    var apellido = respuesta.get_cadena('apellido');
                    
                    this.ef('nombre').set_estado(nombre);
                    this.ef('apellido').set_estado(apellido);

                    return false;
                    
                }else{
                    if(agente == 'organizacion'){
                    
                    this.ef('nombre_org').set_estado(respuesta.get_cadena('nombre'));
                    this.ef('telefono_org').set_estado(respuesta.get_cadena('telefono'));
                    this.ef('email_org').set_estado(respuesta.get_cadena('email'));
                    
                    }
                    
                }   
                
                return false;
        }
        
        {$this->objeto_js}.evt__legajo__validar = function (){
            var tipo_agente=this.ef('tipo_agente').get_estado().toString();
            if(tipo_agente == 'Docente'){
            
            this.controlador.ajax_cadenas('autocompletar_form', this.ef('legajo').get_estado(), this, this.atender_respuesta);
                   
            }
            return true;
        }
        ";
    }
    
    
}
?>