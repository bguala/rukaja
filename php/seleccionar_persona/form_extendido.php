<?php
class form_extendido extends toba_ei_formulario
{
    function extender_objeto_js() {
        //Redimensionamos la ventana del popup de la operacion.
        echo "window.resizeTo(1000, 600);";
    }
}
?>