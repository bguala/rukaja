<?php

class login extends toba_tp_basico_titulo {
    
        protected $clase_encabezado = 'encabezado';	
    
        function barra_superior()
	{
//		echo "<div id='barra_superior' class='barra-superior barra-superior-tit'>\n";		
//		$this->info_version();
//		echo "<div class='item-barra'>";
//		$this->generar_ayuda();		
//		echo "<div class='item-barra-tit'>".$this->titulo_item()."</div>";
//		echo "</div>\n\n";
            echo "<div align='center'><div class='encabezado' ><img src=\"rukaja\" /></div></div>"
            ;
	}
	
	protected function estilos_css()
	{
		parent::estilos_css();
		echo "
		<style type='text/css'>
			#barra_superior {
				display:block;
			}
                        
                        #general {
                            background : red;
                            background : -webkit-linear-gradient (red, yellow);
                            background : -o-linear-gradient (red, yellow);
                            background : -moz-linear-gradient (red, yellow);
                            background : linear-gradient (red, yellow);
                        }
                        
                        .encabezado {
                            /*background-color : red;*/
                            width : 700px;
                            height : 120px;
                            margin : 0 auto; /*para centar horizontalmente*/
                            padding-top : 25px;
                            /*background-image : url('rukaja.png');*/
                            /*font-size : 30px;
                            font-family : cursive;
                            color : #B1015C;
                            font-weight : bold;*/
                        }
                        
                        #pre-contenido {
                            background-color : #56529f;
                            width : 900px;
                            height : 32px;
                            display : block;
                            border-top-left-radius : 15px;
                            border-top-right-radius : 15px;
                        }
                        
                                                
                        #cuerpo {
                            background-color : #C5C4CB;
                            width : 900px;
                            height : 180px;
                            padding-top : 1px;
                            padding-bottom : 1px;
                            border-top-left-radius : 15px;
                            border-top-right-radius : 15px; 
                        }
                        
                                              
                        .pie {
                            background-color : #56529f;
                            width : 900px;
                            height : 27px;
                            padding-top : 5px;
                            color : white ;
                            border-bottom-left-radius : 15px;
                            border-bottom-right-radius : 15px;
                        }
                        
                        a:hover {
                            color : white;
                        }
                        
                        a:link {
                            color : white;
                        }
		</style>			
		";
	}	
	
	protected function generar_ayuda()
	{
		$mensaje = toba::mensajes()->get_operacion_actual();
		if (isset($mensaje)) {
			if (strpos($mensaje, ' ') !== false) {	//Detecta si es una url o un mensaje completo
				$desc = toba_parser_ayuda::parsear($mensaje);
				$ayuda = toba_recurso::ayuda(null, $desc, 'item-barra-ayuda', 0);
				echo "<div $ayuda>";
				echo toba_recurso::imagen_toba("ayuda_grande.gif", true);
				echo "</div>";
			} else {
				if (! toba_parser_ayuda::es_texto_plano($mensaje)) {
					$mensaje = toba_parser_ayuda::parsear($mensaje, true); //Version resumida
				}
				$js = "abrir_popup('ayuda', '$mensaje', {width: 800, height: 600, scrollbars: 1})";
				echo "<a class='barra-superior-ayuda' href='#' onclick=\"$js\" title='Abrir ayuda'>".toba_recurso::imagen_toba("ayuda_grande.gif", true)."</a>";
			}
		}	
	}
	
	/**
	 * Retorna el t�tulo de la opreaci�n actual, utilizado en la barra superior
	 */
//	protected function titulo_item()
//	{
//		return toba::solicitud()->get_datos_item('item_nombre');
//	}

	protected function info_version()
	{
		$version = toba::proyecto()->get_parametro('version');
		if( $version && ! (toba::proyecto()->get_id() == 'toba_editor') ) {
			$info = '';
			$version_fecha = toba::proyecto()->get_parametro('version_fecha');
			if($version_fecha) {
				$info .= "Lanzamiento: <strong>$version_fecha</strong> <br />";	
			}			
			$version_detalle = toba::proyecto()->get_parametro('version_detalle');
			if($version_detalle) {
				$info .= "<hr />$version_detalle<br>";	
			}
			$version_link = toba::proyecto()->get_parametro('version_link');
			if($version_link) {
				$info .= "<hr /><a href=\'http://$version_link\' target=\"_bank\">M�s informaci�n</a><br>";	
			}
			if($info) {
				$info = "Versi�n: <strong>$version</strong><br>" . $info;
				$info = toba_recurso::ayuda(null, $info, 'enc-version');
			}else{
				$info = "class='enc-version'";
			}
			echo "<div $info >";		
			echo 'Versi�n <strong>' . $version .'</strong>';
			echo '</div>';		
		}
	}	
		
	function pre_contenido()
	{
		echo "\n<center><div align='center' id='cuerpo'><div id='pre-contenido'></div><br><br>\n";		
	}
	
        function post_contenido()
	{
		echo "</div></center>";	
                
		echo "<center><div class='pie'>Desarrollado por <strong><a href='http://euclides.uncoma.edu.ar/' style='text-decoration: none' target='_blank'>EQUIPO SIU UNCOMA</a></strong></div></center>";
//		echo "<div>Desarrollado por <strong><a href='http://www.siu.edu.ar' style='text-decoration: none' target='_blank'>SIU</a></strong></div>
//			<div>2002-".date('Y')."</div>";
		//echo "</div>";
	}
}

?>