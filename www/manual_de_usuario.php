<?php
      
    //El encabezado Location realiza una redireccion en el navegador.  En este caso se utiliza el alias de apache
    //relacionado al proyecto rukaja, http://localhost/rukaja/1.0. Este alias contiene el path a la carpeta
    //rukaja/www del proyecto. En esta carpeta se deben colocar los recursos que puede proveer el servidor. 
    //Solamente hace falta especificar el nombre del recurso para formar la direccion url:
    //http://localhost/rukaja/1.0/rukaja_v2_0. Esta direccion nos permite ver el manual de usuario en una 
    //pestaña del navegador.
    header("Location: rukaja_v2_0.pdf");exit();
    
    //---------------------------------------------------------------------------------------------
    //---- Esta seccion intenta explicar un poco mas el uso de headers php ------------------------
    //---------------------------------------------------------------------------------------------
    
    //ob_start() activa el almacenamiento en el buffer de salida. Mientras el almacenamiento interno este activo
    //no se enviara ninguna salida (como un archivo) desde el script php. Si el buffer esta activo la salida se
    //almacena en el buffer interno.
    
    //Los headers php se utilizan para enviar encabezados HTTP sin formato al cliente.
    header('Content-Description: File Transfer');
    
    //Con esta sentencia se especifica el tipo de archivo que vamos a descargar  force-download.
    header("Content-Type: application/pdf");
    //basename devuelve el ultimo componente de una ruta, en este caso 'rukaja_v2_0.pdf'.
    //Especificamos el tipo de descarga y el nombre que recibe el archivo en la descarga. Con 'inline' 
    //visualizamos el archivo en una pestaña del navegador, con 'atachment' iniciamos una descarga desde el 
    //servidor hacia el cliente. 
    //La ruta desde donde se lee el archivo es: C:\proyectos\toba_2.6.3\proyectos\rukaja\rukaja_v2_0.pdf.
    header('Content-Disposition: inline; filename = rukaja_v2_0.pdf');
    
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
    
    //Lee el archivo fuente y lo escribe en el buffer de salida.
    //El path al archivo lo especificamos mediante una ruta absoluta utilizando la direccion url del servidor
    //mas el alias generado por apache. Este ultimo es hasta la carpeta /rukaja/www.
    //La ruta absoluta en el servidor remoto es: http://mocovi.uncoma.edu.ar/rukaja/1.0/rukaja_v2_0.pdf
    //La ruta absoluta en el servidor local es: http://localhost/rukaja/1.0/rukaja_v2_0.pdf
    readfile("localhost/rukaja/1.0/rukaja_v2_0.pdf");
    
    exit();

?>

