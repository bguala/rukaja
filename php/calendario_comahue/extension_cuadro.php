<?php

class extension_cuadro extends toba_ei_cuadro {
    
        
    function vista_pdf (toba_vista_pdf $salida){
        ob_end_clean();
        
        $salida->set_nombre_archivo("Horarios Disponibles.pdf");
        $pdf=$salida->get_pdf();
        $encabezado=$this->generar_encabezado(125);
        //sin margenes se superpone el texto con la imagen encabezado_reporte
        $pdf->ezSetMargins(68, 35, 35, 35);
        $pdf->addJpegFromFile(toba_dir().'/www/img/encabezado_reporte.jpg', 35, 785, 260, 58);
        $pdf->ezText($encabezado, 8, array('justification'=>'center'));
        
        //incluimos la salida por defecto en el nuevo reporte
        parent::vista_pdf($salida);
        
        //definimos el formato del pie de pagina
        $pie_de_pagina="Página {PAGENUM} de {TOTALPAGENUM}";
        
        //agregamos el numero de pagina al pdf
        $pdf->ezStartPageNumbers(550, 20, 8, 'left', utf8_d_seguro($pie_de_pagina));
        
        foreach ($pdf->ezPages as $pageNum=>$id){
            $pdf->reopenObject($id);
            
            //$pdf->addJpegFromFile(toba_dir().'/www/img/encabezado_reporte.jpg', 35, 785, 260, 58);
            //$pdf->ezText($encabezado, 8, array('justification'=>'center'));
            
            $pdf->ezStartPageNumbers(550, 20, 8, 'left', utf8_d_seguro($pie_de_pagina));
            $pdf->closeObject();
        }
        

    }
        
    function generar_encabezado ($fin){
        $fecha=date('d-m-Y', strtotime(toba::memoria()->get_dato_operacion(0)));
        $encabezado="Fecha de Consulta : $fecha";
        $i=0;
        while ($i < $fin){
            $encabezado .= '-';
            $i += 1;
        }

        $hora=date('H:i:s');
        return ($encabezado."Hora : $hora");
    }
        
}

?>