<?php
class ci_novedades_de_bedelia extends toba_ci
{
        protected $s__datos_incidencia;
        protected $s__id_sede;
        protected $s__datos_form;
        protected $s__cabecera;


        //---- Pant Edicion -----------------------------------------------------------------
        
        function conf__pant_edicion (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab('pant_reporte')->desactivar();
        }
        
	//---- Cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
            $this->s__id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
            $cuadro->set_datos($this->dep('datos')->tabla('incidencia')->get_listado($this->s__id_sede));
	}

	function evt__cuadro__seleccion($datos)
	{
            $this->s__datos_incidencia=$datos;
            $this->set_pantalla('pant_reporte');
	}
        
        //---- Pant Reporte -----------------------------------------------------------------
        
        function conf__pant_reporte (toba_ei_pantalla $pantalla){
            $this->pantalla()->tab('pant_edicion')->desactivar();
        }

	//---- Formulario -------------------------------------------------------------------

	function conf__form(toba_ei_formulario $form)
	{
            //--Obtenemos datos relacionados al establecimiento. 
            $ua=$this->dep('datos')->tabla('unidad_academica')->get_unidad_academica ($this->s__id_sede);
            //--Configuramos la cabecera del formulario.
            $producido_por=  utf8_decode("Dirección de ").$ua[0]['establecimiento'];
            $para="Servicios";
            $fecha_actual=date('d-m-Y');
                        
            $form->ef('producido_por')->set_estado($producido_por);
            $form->ef('para')->set_estado($para);
            $form->ef('fecha_actual')->set_estado($fecha_actual);
            
            $this->s__cabecera=array(
                $producido_por, $para, $fecha_actual
            );
            
            //--Obtenemos los datos relacionados a la incidencia.
            $form->ef('fecha')->set_estado(date('d-m-Y', strtotime($this->s__datos_incidencia['fecha'])));
            $form->ef('hora')->set_estado($this->s__datos_incidencia['hora']);
            $form->ef('nombre')->set_estado($this->s__datos_incidencia['nombre']);
	}
        
        function conf__incidencias (toba_ei_cuadro $cuadro){
            $cuadro->set_datos(
                $this->dep('datos')->tabla('incidencia')->get_cantidad_material($this->s__datos_incidencia['id_incidencia'])
            );
        }
        
        function vista_pdf (toba_vista_pdf $salida){
            
            //--Agregamos el encabezado del documento.
            $salida->set_nombre_archivo(utf8_d_seguro("Incidencias Aula {$this->s__datos_incidencia['nombre']}.pdf"));
            $pdf=$salida->get_pdf();
            
            $pdf->ezSetMargins(58, 30, 35, 35);
            $pdf->addJpegFromFile(toba_dir().'/proyectos/rukaja/www/img/encabezado_reporte.jpg', 35, 785, 260, 58);
            
            $titulo="\n Reporte de Incidencias ............... / ............... \n";
            
            $pdf->ezText($titulo, 8, array('justification' => 'center'));
            
            $pdf->ezText("\nProducido por: ".$this->s__cabecera[0], 8);
            $pdf->ezText(utf8_decode("\nPara el área de: ").$this->s__cabecera[1], 8);
            $pdf->ezText("\nFecha actual: ".$this->s__cabecera[2]."\n", 8);
            
            $titulo="\nDatos relacionados a la incidencia\n";
            $fecha="Fecha: ".date('d-m-Y', strtotime($this->s__datos_incidencia['fecha']));
            $hora="\nHora: ".$this->s__datos_incidencia['hora'];
            $aula="\nAula: ".$this->s__datos_incidencia['nombre'];
            $pdf->ezText($titulo, 8);
            $pdf->ezText($fecha, 8);
            $pdf->ezText($hora, 8);
            $pdf->ezText($aula, 8);
            
            $pdf->ezText("\n\n");
            
            //--Definimos el estilo de la tabla.
            $estilo=array(
                'splitRows' => 0,
                'rowGraph' => 0,
                'showHeadings' => true,
                'titleFontSize' => 5,
                'fontSize' => 5, //--Definimos el tamanio de fuente.
                'shadeCol' => array(0.9,0.9,0.9),//--Especificamos el color de cada fila.
                'xOrientation' => 'center',
                //'width' => 500,
                'xPos' => 'centre',
                'yPos' => 'centre',
            );
            
            $columnas=array(
                'nombre' => 'Incidencia', 
                'descripcion' => utf8_d_seguro(' Descripción de la incidencia '), 
                'cantidad' => 'Cantidad de elementos' 
            );
                        
            //--Agregamos una tabla con la cantidad de material que se necesita.
            $cantidad_materiales=$this->dep('datos')->tabla('incidencia')->get_cantidad_material($this->s__datos_incidencia['id_incidencia']);
            $pdf->ezTable($cantidad_materiales, $columnas, $estilo);
            
            //--Cambiamos el estado de la incidencia. Cuando se genera el reporte debe pasar a estado iniciada.
            //Hacemos uso de la interfaz datos_tabla.
            $this->dep('datos')->tabla('incidencia')->cargar(array('id_incidencia'=>$this->s__datos_incidencia['id_incidencia']));
            $incidencia=$this->dep('datos')->tabla('incidencia')->get();
            $incidencia['estado']='INICIADA';
            $this->dep('datos')->tabla('incidencia')->set($incidencia);
            $this->dep('datos')->tabla('incidencia')->sincronizar();
            $this->dep('datos')->tabla('incidencia')->resetear();
            
        }
        
        function evt__volver (){
            $this->set_pantalla('pant_edicion');
        }

}

?>