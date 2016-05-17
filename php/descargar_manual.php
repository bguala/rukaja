<?php
    echo "<script type='text/javascript' language='javascript'>
        
            var ventana=window.open('', '_blank');
            ventana.focus();
            
          </script>";
    //ob_start() activa el almacenamiento en el buffer de salida. Mientras el almacenamiento interno este activo
    //no se enviara ninguna salida (como un archivo) desde el script php. Si el buffer esta activo la salida se
    //almacena en el buffer interno.
    //los headers se utilizan para enviar encabezados HTTP sin formato
    header('Content-Description: File Transfer');
    header("Content-Type: application/pdf");//con esta sentencia se especifica el tipo de archivo que vamos a descargar  force-download
    header('Content-Disposition: inline; filename='.basename('C:\proyectos\toba_2.6.3\proyectos\Examen final de TyDBD.pdf'));//especificamos en nombre que recibe el archivo en la descarga
    //basename devuelve el ultimo componente de una ruta, en este caso 'Examen final de TyDBD'
    header('Content-Transfer-Encoding: binary'); //
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); //evita que el navegador cliente agregue en memoria cache contenido dinamico generado por el script php, se debe usar antes de must-revalidate no-cache
    header('Pragma: public'); 
    flush(); //vacia el buffer de salida del sistema
    ob_clean(); //elimina el buffer de salida
    readfile('C:\proyectos\toba_2.6.3\proyectos\gestion_aulas\Manual_de_Usuario.pdf');//Lee el archivo fuente que se va a descargar
    
    exit();

?>