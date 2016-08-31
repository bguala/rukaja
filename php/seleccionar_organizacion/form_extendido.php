<?php
class form_extendido extends toba_ei_formulario
{
    function extender_objeto_js() {
        //Redimensionamos la ventana del pop-up.
        echo "window.resizeTo(1000, 500);";
    }
}
?>