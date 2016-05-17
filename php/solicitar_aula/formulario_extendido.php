<?php
class formulario_extendido extends toba_ei_formulario
{
    function extender_objeto_js() {
        
//        {$this->objeto_js}.evt__tipo__validar = function () {
//            var tipo=this.ef('tipo').get_estado().toString();
//            switch (tipo){
//            
//            case 'Denuncia de aula' :    //alert('LLega');
//                                         this.ef('descripcion').mostrar();
//                                         this.ef('fecha').ocultar();
//                                         this.ef('id_aula').mostrar();
//                
//                                         this.ef('fijo3').mostrar();
//                                         this.ef('den1').mostrar();
//                                         this.ef('den2').mostrar();
//                                         this.ef('den3').mostrar();
//                                         this.ef('den4').mostrar();
//                                         break;
//                                         
//            case 'Solicitud de aula' :   this.ef('descripcion').input().value='';
//                                         this.ef('descripcion').ocultar();
//                                         this.ef('fijo3').ocultar();
//                
//                                         this.ef('den1').input().checked=false;
//                                         this.ef('den1').ocultar();
//                
//                                         this.ef('den2').input().checked=false;
//                                         this.ef('den2').ocultar();
//                
//                                         this.ef('den3').input().checked=false;
//                                         this.ef('den3').ocultar();
//                
//                                         this.ef('den4').input().checked=false;
//                                         this.ef('den4').ocultar();
//                
//                                         this.ef('id_aula').ocultar();
//                                         this.ef('fecha').mostrar();
//                                         break;
//                                         
//            case 'nopar' :               this.ef('descripcion').input().value='';
//                                         this.ef('descripcion').ocultar();
//                                         this.ef('fijo3').ocultar();
//                
//                                         this.ef('den1').input().checked=false;
//                                         this.ef('den1').ocultar();
//                
//                                         this.ef('den2').input().checked=false;
//                                         this.ef('den2').ocultar();
//                
//                                         this.ef('den3').input().checked=false;
//                                         this.ef('den3').ocultar();
//                
//                                         this.ef('den4').input().checked=false;
//                                         this.ef('den4').ocultar();
//                
//                                         this.ef('id_aula').ocultar();
//                                         break;
//            
//            }
//            return true;
//        }
        
        echo "
        
        {$this->objeto_js}.evt__tipo_agente__validar = function () {
            var tipo=this.ef('tipo_agente').get_estado();
            
            switch(tipo){
                case 'Docente' : this.ef('nombre_org').ocultar();
                                 this.ef('nombre_org').set_estado('');
                                 this.ef('telefono_org').ocultar();
                                 this.ef('telefono_org').set_estado('');
                                 this.ef('email_org').ocultar();
                                 this.ef('email_org').set_estado('');
                                 this.ef('legajo').mostrar();
                                 this.ef('nombre').mostrar();
                                 this.ef('apellido').mostrar();
                                 break;
                
                case 'Organizacion' : this.ef('nombre_org').mostrar();
                                      this.ef('telefono_org').mostrar();
                                      this.ef('email_org').mostrar();
                                      this.ef('legajo').ocultar();
                                      this.ef('legajo').set_estado('');
                                      this.ef('nombre').ocultar();
                                      this.ef('nombre').set_estado(''),
                                      this.ef('apellido').ocultar();
                                      this.ef('apellido').set_estado('');
                                      break;
                
                case 'nopar' : this.ef('nombre_org').mostrar();
                               this.ef('nombre_org').set_estado('');
                               this.ef('telefono_org').mostrar();
                               this.ef('telefono_org').set_estado('');
                               this.ef('email_org').mostrar();
                               this.ef('email_org').set_estado('');
                               this.ef('legajo').mostrar();
                               this.ef('legajo').set_estado('');
                               this.ef('nombre').mostrar();
                               this.ef('nombre').set_estado('');
                               this.ef('apellido').mostrar();
                               this.ef('apellido').set_estado('');
                               break;
            }
            
            return true;
        }
        
        {$this->objeto_js}.evt__tipo__validar = function (){
            var tipo=this.ef('tipo').get_estado();
            
            if(tipo == 'OTRO'){
                this.ef('tipo_nombre').set_solo_lectura(false);
                this.ef('tipo_nombre').set_obligatorio(true);
            }
            else{
                this.ef('tipo_nombre').set_solo_lectura(true);
                this.ef('tipo_nombre').set_obligatorio(true);
                this.ef('tipo_nombre').set_estado('');
            }
            
            return true;

        }
        
        {$this->objeto_js}.evt__hora_inicio__validar = function (){
            var hora_inicio_disponible=this.ef('inicio').hora();
            var hora_fin_disponible=this.ef('fin').hora();
            
            var hora_inicio=this.ef('hora_inicio').hora();
            var hora_fin=this.ef('hora_fin').get_estado();
            
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
            
        {$this->objeto_js}.evt__legajo__validar = function () {
                this.controlador.ajax_cadenas('autocompletar_form', this.ef('legajo').get_estado(), this, this.atender_respuesta);
                return true;
        }
        
        {$this->objeto_js}.atender_respuesta = function (respuesta) {
                
                var accion = respuesta.get_cadena('accion');
                
                if(accion == 'y'){
                    var nombre = respuesta.get_cadena('nombre');
                    var apellido = respuesta.get_cadena('apellido');
            
                    this.ef('nombre').set_estado(nombre);
                    this.ef('apellido').set_estado(apellido);
                        
                    return false;
                }
                else{
                    
                    alert('El legajo ingresado no pertenece a un docente registrado en el sistema');
                    //this.ef('legajo').set_error('El legajo ingresado no pertenece a un docente registrado en el sistema');
                    this.ef('legajo').set_estado('');
                    this.ef('nombre').set_estado('');
                    this.ef('apellido').set_estado('');
                    
                    return false;
                }
                
        }
        ";
    }
    
    
}
?>