<?php
class formulario_extendido extends toba_ei_formulario
{
    function extender_objeto_js (){
        echo "{$this->objeto_js}.evt__tipo__validar = function(){
            //alert('Se ejecuta ext obj js');
            var estado = (this.ef('tipo').get_estado()).toString();
            if(estado == 'Definitiva'){
                //alert('VITTO');
                //this.ef('dia').mostrar(true);
                this.ef('dia').activar(true);
            }
            else{
                //alert('RAKY');
                //this.ef('dia').ocultar(true);
                this.ef('dia').desactivar(true);
            } "
        . "return true; "
       . "}";
        
        
    }
}
?>