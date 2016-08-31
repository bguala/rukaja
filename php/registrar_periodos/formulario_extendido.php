<?php
class formulario_extendido extends toba_ei_formulario {
    function extender_objeto_js() {
        echo "{$this->objeto_js}.evt__tipo_periodo__validar = function (){
            var tipo = this.ef('tipo_periodo').get_estado();
            
            switch(tipo){
            
                case 'Cuatrimestre' : this.ef('facultad').ocultar();
                                      this.ef('nombre').ocultar();
                                      this.ef('turno').ocultar();
                                      this.ef('numero').mostrar();
                                      this.ef('numero').set_obligatorio(true);
                                      break;
                                      
                case 'Examen Final' : this.ef('facultad').ocultar();
                                      this.ef('nombre').ocultar();
                                      this.ef('turno').mostrar();
                                      this.ef('numero').mostrar();
                                      this.ef('numero').set_obligatorio(true);
                                      break;
                                      
                case 'Curso de Ingreso' : this.ef('turno').ocultar();
                                          this.ef('numero').ocultar();
                                          this.ef('facultad').mostrar();
                                          this.ef('nombre').mostrar();
                                          this.ef('numero').set_obligatorio(false);
                                          break;
                
                case 'nopar' : this.ef('facultad').mostrar();
                             this.ef('nombre').mostrar();
                             this.ef('turno').mostrar();
                             this.ef('numero').mostrar();
                             this.ef('numero').set_obligatorio(true);
                             break;
            
            }
            
            return true;
        }";
    }
}
?>