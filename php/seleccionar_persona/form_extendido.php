<?php
class form_extendido extends toba_ei_formulario
{
    function extender_objeto_js() {
        //Con esta simple sentencia lo que hacemos es redimensionar el popup de la operacion.
        echo "window.resizeTo(1000, 600);";
    }
}
?>