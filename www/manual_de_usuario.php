<?php
    //echo "Hello World!";
    //exit();
    //ob_start() activa el almacenamiento en el buffer de salida. Mientras el almacenamiento interno este activo
    //no se enviara ninguna salida (como un archivo) desde el script php. Si el buffer esta activo la salida se
    //almacena en el buffer interno.
    
    //-------------------------------------------------------------------------------------------------------
    //---- Suponemos que el buffer de salida esta activa. ---------------------------------------------------
    //-------------------------------------------------------------------------------------------------------
    
    //Los headers php se utilizan para enviar encabezados HTTP sin formato al cliente.
    header('Content-Description: File Transfer');
    //Con esta sentencia se especifica el tipo de archivo que vamos a descargar  force-download.
    header("Content-Type: application/pdf");
    //basename devuelve el ultimo componente de una ruta, en este caso 'Examen final de TyDBD'.
    //Especificamos el tipo de descarga y el nombre que recibe el archivo en la descarga. Con 'inline' 
    //visualizamos el archivo en una pestaña del navegador.
    header('Content-Disposition: inline; filename='.basename('C:\proyectos\toba_2.6.3\proyectos\rukaja\rukaja_v2_0.pdf'));
    
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    //Evita que el navegador cliente agregue en memoria cache contenido dinamico generado por el script php, 
    //se debe usar antes de must-revalidate no-cache
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); 
    header('Pragma: public'); 
    
    //Vaciamos el buffer de salida del sistema.
    flush();
    
    //Limpia el buffer de salida.
    ob_end_clean();
    //Limpia el buffer de salida y deshabilita el almacenamiento en el mismo.
    //ob_end_clean();
    
    //Lee el archivo fuente y lo escribe en el buffer de salida.
    readfile('C:\proyectos\toba_2.6.3\proyectos\rukaja\rukaja_v2_0.pdf');
    
    exit();

?>

