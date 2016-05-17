<?php
class formulario_extendido extends toba_ei_formulario
{
    function extender_objeto_js() {
        echo "{$this->objeto_js}.evt__anio_lectivo__validar = function (){
            var anio=this.ef('anio_lectivo').get_estado().toString().length;
            
            if(anio == 4){
                return true;
            }
            else{
                this.ef('anio_lectivo').set_error('El numero ingresado debe tener 4 digitos');
                return false;
            }
        }";
    }
}
?>