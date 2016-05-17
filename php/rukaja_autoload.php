<?php
/**
 * Esta clase fue y será generada automáticamente. NO EDITAR A MANO.
 * @ignore
 */
class rukaja_autoload 
{
	static function existe_clase($nombre)
	{
		return isset(self::$clases[$nombre]);
	}

	static function cargar($nombre)
	{
		if (self::existe_clase($nombre)) { 
			 require_once(dirname(__FILE__) .'/'. self::$clases[$nombre]); 
		}
	}

	static protected $clases = array(
		'rukaja_ci' => 'extension_toba/componentes/rukaja_ci.php',
		'rukaja_cn' => 'extension_toba/componentes/rukaja_cn.php',
		'rukaja_datos_relacion' => 'extension_toba/componentes/rukaja_datos_relacion.php',
		'rukaja_datos_tabla' => 'extension_toba/componentes/rukaja_datos_tabla.php',
		'rukaja_ei_arbol' => 'extension_toba/componentes/rukaja_ei_arbol.php',
		'rukaja_ei_archivos' => 'extension_toba/componentes/rukaja_ei_archivos.php',
		'rukaja_ei_calendario' => 'extension_toba/componentes/rukaja_ei_calendario.php',
		'rukaja_ei_codigo' => 'extension_toba/componentes/rukaja_ei_codigo.php',
		'rukaja_ei_cuadro' => 'extension_toba/componentes/rukaja_ei_cuadro.php',
		'rukaja_ei_esquema' => 'extension_toba/componentes/rukaja_ei_esquema.php',
		'rukaja_ei_filtro' => 'extension_toba/componentes/rukaja_ei_filtro.php',
		'rukaja_ei_firma' => 'extension_toba/componentes/rukaja_ei_firma.php',
		'rukaja_ei_formulario' => 'extension_toba/componentes/rukaja_ei_formulario.php',
		'rukaja_ei_formulario_ml' => 'extension_toba/componentes/rukaja_ei_formulario_ml.php',
		'rukaja_ei_grafico' => 'extension_toba/componentes/rukaja_ei_grafico.php',
		'rukaja_ei_mapa' => 'extension_toba/componentes/rukaja_ei_mapa.php',
		'rukaja_servicio_web' => 'extension_toba/componentes/rukaja_servicio_web.php',
		'rukaja_comando' => 'extension_toba/rukaja_comando.php',
		'rukaja_modelo' => 'extension_toba/rukaja_modelo.php',
	);
}
?>