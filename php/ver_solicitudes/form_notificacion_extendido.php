<?php
class form_notificacion_extendido extends toba_ei_formulario
{
    function extender_objeto_js() {
        echo "{$this->objeto_js}.evt__borrar = function () {
            this.ef('descripcion').input().value='';
            return true;
        }";
    }
}
?>