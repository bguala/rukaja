<?php
class seleccionar_tipo_extendido extends toba_ei_formulario
{
    function extender_objeto_js() {
       echo   "{$this->objeto_js}.evt__tipo__validar = function (){
                   var estado = (this.ef('tipo').get_estado()).toString();
                   //alert(estado);
                   switch (estado){
                   case 'Docente' : this.ef('legajo').mostrar(); //mostrar
                                    this.ef('legajo').set_obligatorio(true);
                                    this.ef('titulo').mostrar();
                                    break;
                                    
                   case 'Agente Externo' : this.ef('legajo').ocultar();
                                           //this.ef('legajo').set_obligatorio(false);
                                           this.ef('titulo').ocultar();
                                           break;
                   case 'nopar' : this.ef('legajo').mostrar();
                                  this.ef('legajo').set_obligatorio(true);
                                  this.ef('titulo').mostrar();
                                  break;
                   }
                   return true;
            }";
    }
}
?>