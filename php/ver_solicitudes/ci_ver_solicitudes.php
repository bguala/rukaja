<?php

require_once(toba_dir().'/php/3ros/phpmailer/class.phpmailer.php');
require_once(toba_dir().'/php/3ros/ezpdf/class.ezpdf.php');
require_once(toba_dir().'/proyectos/rukaja/php/api/HorariosDisponibles.php');
require_once(toba_dir().'/proyectos/rukaja/php/api/Filtro.php');
require_once(toba_dir().'/proyectos/rukaja/php/api/Email.php');
/*
 * A esta operacion debemos introducirle los siguientes cambios:
 * a) El usuario logueado debe poder ver las solicitudes que le hicieron y las solicitudes que realizo*.
 * A estas ultimas debe poder editarlas, borrarlas y eliminarlas. Esta ultima accion es util porque libera un posible
 * espacio a ocupar.
 * b) Se deben listar las solicitudes cuya fecha_solicitud sea mayor a la fecha_actual y con estado pendiente.
 * c) El usuario logueado tambien debe poder ver las solicitudes que le hiecieron en su establecimiento.
 * Que pasa si el usuario real queiere agregar un calendario???. Adaptaremos esta nueva estructura a la operaciuon
 * y filtraremos las solicitudes usando la fecha seleccionada.
 */
class ci_ver_solicitudes extends toba_ci
{
        protected $s__contador=0;
        protected $s__contador_notificacion=0;
        protected $s__id_sede; 
        protected $s__horarios;
        protected $s__i=0;
        protected $s__horarios_disponibles=array();      //Contiene todos los horarios disponibles alternativos para el dia solicitado, no coinciden con el horario especificado en la solicitud
        protected $s__horarios_libres;                   //Guarda los horarios disponibles segun el requerimiento de hora de inicio y fin de la solicitud
        protected $s__id_aula;
        protected $s__emisor;
        protected $s__destinatario;      
        protected $s__sigla;
        protected $s__fecha_consulta;
        protected $s__capacidad;
        protected $s__nombre_sede;
        protected $s__nombre_facultad;
        protected $s__nombre_aula;
        protected $s__notificar=FALSE;
        protected $s__pantalla_actual;
        protected $s__dia_consulta;
        protected $s__datos_filtro;                    //Contiene un cjto. de datos filtrados.
        protected $s__sede_origen;
        protected $s__datos_responsable;
        
        protected $s__datos_solcitud;                  //Guardamos todos los datos relacionados a una solicitud. Es util para cambiarla de estado.
                      
        //Se cargan si hay que notificar horarios alternativos
        //Quizas se pueden eliminar.
        protected $s__nombre;
        protected $s__apellido;
        protected $s__finalidad;
        
        //Guardamos el tipo de solicitud seleccionada por el usuario. Esto sirve para saber a que metodo del 
        //datos_tabla debemos llamar, puede ser get_listado_solicitudes o get_listado_solicitudes_realizadas.
        //El primero se usa para obtener todas las solicitudes de aula en un establecimiento, el segundo se 
        //usa para obtener todas las solicitudes hechas a otras dependencias.
        protected $s__datos_form;
        
        //Guardamos un arreglo para cargar el combo tipo_solicitud del formulario FF001. Esto es util porque 
        //el texto a mostrar es bastante largo, ademas en el conf__cuadro necesitamos esta informacion para
        //asignarle un nombre al cuadro. No se pudo obtener esta informacion desde el objeto $form.
        protected $s__cargar_combo=array(
                array('clave' => 1, 'descripcion' => 'Solicitudes de aula realizadas a otras dependencias'),
                array('clave' => 2, 'descripcion' => 'Solicitudes realizadas en su dependencia')
        );
        
        protected $s__accion;           
        
        protected $s__filtro;
        
        //------------------------------------------------------------------------------------
        //---- Pant Edicion ------------------------------------------------------------------
        //------------------------------------------------------------------------------------
        
        function conf__pant_edicion (){
            $this->s__pantalla_actual="pant_edicion";
            $this->pantalla()->tab('pant_busqueda')->desactivar();
            $this->pantalla()->tab('pant_asignacion')->desactivar();            
        }
        
        //---- Form Solicitud ----------------------------------------------------------------
                
        function evt__FF001__aceptar ($datos){
            $this->s__datos_form=$datos;
        }
        
        /*
         * Esta funcion permite cargar el combo del formulario form_solicitud.
         */
        function cargar_combo_solicitud (){
            return $this->s__cargar_combo;
        }
        
        //---- Filtro Solicitudes ------------------------------------------------------------
        function conf__filtro_solicitudes (toba_ei_filtro $filtro){
            (isset($this->s__datos_form)) ? $filtro->descolapsar() : $filtro->colapsar();
        }
        
        function evt__filtro_solicitudes__filtrar (){
            
        }
        
        //---- Cuadros -----------------------------------------------------------------------
        
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{           
            //Se necesita id_sede para obtener las solicitudes que pertenecen al establecimiento del usuario
            //logueado.
            $this->s__id_sede=$this->dep('datos')->tabla('sede')->get_id_sede();
            
            if(isset($this->s__datos_form)){
                if($this->s__datos_form['tipo_solicitud'] == 1){
                    //Mostramos las solicitudes de aula realizadas a otros dependencias. En este caso debemos usar
                    //el id_sede del usuario logueado para comparar con el campo id_sede de la tabla solicitud. 
                    //id_sede guarda el id_ de la sede destino, que es a quien le hacemos el pedido de aula.
                    //Esto permite editar o eliminar pedidos de aula a otras dependencias.
                    //Si no vemos nada en el cuadro es porque la fecha de solicitud es mayor a la fecha actual.
                    $cuadro->set_datos($this->dep('datos')->tabla('solicitud')->get_solicitudes($this->s__id_sede, date('Y-m-d'), 1));
                    $cuadro->set_titulo(strtoupper($this->s__cargar_combo[0]['descripcion']));
                    $cuadro->eliminar_evento('seleccion');
                    
                }else{
                    //Mostramos las solicitudes de aula que estan hechas en el establecimeinto del usuario que se
                    //loguea. Estas se encuentran registradas en estado pendiente.
                    $cuadro->set_datos($this->dep('datos')->tabla('solicitud')->get_solicitudes($this->s__id_sede, date('Y-m-d'), 2));
                    $cuadro->set_titulo(strtoupper($this->s__cargar_combo[1]['descripcion']));
                    $cuadro->eliminar_evento('edicion_parcial');
                    $cuadro->eliminar_evento('edicion_total');
                    $cuadro->eliminar_evento('borrar');
                }
            }else{
                $cuadro->colapsar();
            }
		
	}
        
        /*
         * En esta funcion debemos empezar el calculo de horarios disponibles. Teniendo en cuenta que:
         * a) Como ya tenemos el aula, debemos empezar el calculo de horarios disponibles con las asignaciones
         *    de esa aula, verificar que existe espacio y conceder la solicitud.
         * b) Si no hay espacio disponible en el aula seleccionada debemos iniciar un calculo de horarios 
         *    disponibles en todas las aulas del establecimeinto destino, sin considerar el aula seleccionada.
         *    Posteriormente debemos mostrar los horarios disponibles que contengan la hora_inicio y hora_fin 
         *    de la solicitud. Y permitirle al usuario registrar la solicitud con otra aula.
         * c) Debemos tratar solicitudes para una fecha y multi_eventos. Estos ultimos consisten de una 
         *    fecha_inicio, fecha_fin y una lista de dias. Esta distincion se debe verificar en en evt y 
         *    disparar las funciones adecuadas para cada caso.
         * 
         * Para filtrar los espacios disponibles debemos tener en cuenta el horario y la capacidad especificada
         * en la solicitud. Y para calcular los espacios disponibles debemos usar la fecha de solicitud y descartar
         * asignaciones definitivas que puedan contener periodos.
         *    
         */
	function evt__cuadro__seleccion($datos){
            
            if(strcmp($datos['estado'], 'PENDIENTE') != 0){
                $mensaje=" Solamente se pueden analizar solicitudes en estado PENDIENTE. ";
                toba::notificacion()->agregar($mensaje, 'info');
                return ;
            }
	    //Necesitamos todos los datos de la solicitud para:
            //a) pasarla a estado finalizada.
            //b) registrar la solicitud si existe algun espacio disponible. 
            //Usamos la misma variable, s__datos_solicitud.
            $this->s__datos_solcitud=$datos;
            $this->s__contador += 1;
                        
            //Usamos el id_sede especificado en la solicitud. Debemos conceder o no las solicitudes que hicieron
            //en nuestra dependencia, para ello necesitamos conocer cuales son los espacios que tenemos 
            //disponibles en nuestro establecimiento.
            $this->s__id_sede=$datos['id_sede'];
            
            //Guardamos la fecha de solicitud en una variable aparte. Se utiliza en la funcion procesar_periodo 
            //para obtener todas las asignaciones, definitivas o periodicas, de la fecha de solicitud.
            //El valor de esta variable es usada dentro de los metodos del datos_tabla.
            $this->s__fecha_consulta=$datos['fecha'];
            
            $this->s__capacidad=$datos['capacidad'];
            
            //Verificamos si la solicitud es unica o multi_evento.
            if(strcmp($datos['tipo'], "UNICO")==0){
                //Empezamos calculando horarios disponibles en el aula seleccionada. El resultado de 
                //calcular_hd_en_aula_seleccionada es booleando. True si exsite el horario especificado en la 
                //solicitud.
                if($this->calcular_hd_en_aula_seleccionada()){
                    //Si existe espacio disponible en el aula solicitada, debemos mostrar los datos de la 
                    //solicitud y permitirle al usuario persistir una nueva asignacion.
                    $this->set_pantalla('pant_asignacion');
                }else{
                    //Si no existe el horario especificado en la solicitud iniciamos la busqueda de un horario 
                    //alternativo en otra aula.
                    $this->verificar_existencia_de_espacio();
                    $this->set_pantalla('pant_busqueda');
                }
            }else{
                $this->conceder_multi_evento($datos);
            }
            
//            //Se necesita para enviar una notificacion si la solicitud es exitosa.
//            $this->s__sede_origen=$datos['id_sede'];
//            $this->s__sigla=$datos['facultad'];
//            $this->s__datos_filtro=array();
//            //Se usa para cargar el formulario form_asignacion con datos por defecto. En este punto la solicitud 
//            //esta lista para ser concedida.
//            $this->s__datos_solicitud=$datos;
            
	}
        
        function conceder_multi_evento ($datos){
            
            $this->s__id_sede=$datos['id_sede'];
            //Obtenemos las lista de fechas pertenecientes al periodo. El formato de las fechas es:
            // Y-m-d.
            $lista_fechas=$this->dep('datos')->tabla('solicitud')->get_lista_fechas($datos['id_solicitud']);
            
            $hd_fechas=$this->horarios_disponibles_por_fecha($lista_fechas);
                        
            if($this->existe_hd_para_periodo($hd_fechas, $datos['id_aula'], $datos['hora_inicio'], $datos['hora_fin'])){
                //Si existe el mismo horario en cada fecha del periodo, podemos conceder el multi-evento. Para
                //ello reutilizamos la funcion registrar_solicitud.
                
                $this->set_pantalla('pant_asignacion');
                
                
            }else{
                
                $this->pasar_a_estado_finalizada('RECHAZADA');
                
                $mensaje=" No es posible conceder el período actual. No hay espacios disponibles. ";
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
                //Esta funcionalidad se puede implemetar mas adelante.
                
                //$aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($this->s__id_sede);
                //$repeticion=$this->establecer_repeticion($hd_fechas, $datos['hora_inicio'], $datos['hora_fin']);
                
                //Ordenamos las repeticiones de menor a mayor, usando el metodo de insercion.
                //$this->ordenar_repeticion(&$repeticion, count($repeticion));
                
                //$this->establecer_asignacion($repeticion);
            }
                        
        }
        
        /*
         * @$lista_fechas : el formato de esta estructura es : 
         * array( 0 => array(id_solicitud, fecha, nombre) ).
         */
        function horarios_disponibles_por_fecha ($lista_fechas){
            //Obtenemos las aulas una unica vez.
            $aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($this->s__id_sede);
            $hd_fecha=array();
            
            foreach($lista_fechas as $clave=>$fecha){
                $this->s__fecha_consulta=$fecha['fecha'];
                
                $this->hd_multi_evento($aulas_ua);
                
                //0 => fecha, 1 => s__horarios_disponibles.
                $hd_fecha[]=array($fecha['fecha'] , $this->s__horarios_disponibles);
                
                //Limpiamos el arreglo para no acumular resultados.
                $this->s__horarios_disponibles=array();
            }
            
            return $hd_fecha;
                      
        }
        
        /*
         * Esta funcion calcula horarios disponibles para cada fecha del periodo. Agrupa la funciones que 
         * empiezan el calculo de hd desde el controlador.
         */
        function hd_multi_evento ($aulas_ua){
            
            $anio_lectivo=date('Y', strtotime($this->s__fecha_consulta));
            //Configuramos el dia de consulta para que este disponible en la funcion procesar_periodo.
            $this->s__dia_consulta=$this->obtener_dia(date('N', strtotime($this->s__fecha_consulta)));
            
            //Obtenemos los periodos que pueden contener a la fecha de solicitud.
            $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($this->s__fecha_consulta, $anio_lectivo, $this->s__id_sede);
            //Usamos la cadena 'au' para extraer las asignaciones pertenecientes a un aula en particular. 
            //Es una condicion mas dentro de la funcion procesar_periodo.
            $asignaciones=$this->procesar_periodo($periodo, 'hd');
            
            $aulas=$this->obtener_aulas($asignaciones);
            //Guardamos en sesion el id_sede para agregar la capacidad de cada aula a un horario disponible.
            //En 0 se reserva para el id_sede en esta operacion y en Buscador de Aula.
            toba::memoria()->set_dato_instancia(0, $this->s__id_sede);
            
            $hd=new HorariosDisponibles();
            
            $this->s__horarios_disponibles=$hd->calcular_horarios_disponibles($aulas, $aulas_ua, $asignaciones);
                      
        }
        
        /*
         * Esta funcion verifica si el multi-evento se puede conceder, esto es ver si en cada fecha existe el 
         * horario disponible especificado en la solicitud.
         */
        function existe_hd_para_periodo ($hd_fechas, $id_aula, $hora_inicio, $hora_fin){
            $i=0;
            $j=0;
            $n=count($hd_fechas);
            $fin=TRUE;
            $fin_hd=FALSE;
            while($i<$n && $fin){
                $hd=$hd_fechas[$i];
                //Guardamos la longitud del arreglo que contiene todos los hd para una fecha.
                $m=count($hd[1]);
                $horarios=$hd[1];
                
                while($j<$m && !$fin_hd){
                    $aula=$horarios[$j];
                    if($aula['id_aula']==$id_aula && ($hora_inicio>=$aula['hora_inicio'] && $hora_inicio<=$aula['hora_fin'] && $hora_fin<=$aula['hora_fin'])){
                        //Si $fin_hd es true significa que para una fecha en particular encontramos un aula 
                        //disponible. Entonces no necesitamos hacer mas comparaciones.
                        $fin_hd=TRUE;
                                                
                    }
                    $j++;
                }
                
                //Si $fin_hd es true, necesitamos seguir verificando si existe disponibilidad horaria en las 
                //otras fechas del periodo, por lo tanto $fin_hd debe seguir siendo false.
                $fin=($fin_hd) ? TRUE : FALSE ;
                $fin_hd=FALSE;
                $j=0;
                $i++;
            }
            
            return $fin;
        }
        
        /*
         * Esta funcion crea una estructura, llamada repeticion, con el siguiente formato: 
         * (0 => array( 0 => aula, 1 => array(f1, f2, f3, ...., fn))).
         * Las fechas se filtran teniendo en cuenta la hora de inicio y fin especificadas en la solicitud.
         */
        function establecer_repeticion($hd_fechas, $hora_inicio, $hora_fin){
            $repeticion=array();
            foreach($hd_fechas as $clave=>$valor){
                $fecha=$valor[0];
                $hd=$valor[1];
                $i=0;
                $n=count($hd);
                $elto=array();
                $elto[]=$fecha;
                $horarios=array();
                while($i < $n){
                    $horario=$hd[$i];
                    
                    if($hora_inicio>=$horario['hora_inicio'] && $hora_inicio<=$horario['hora_fin'] && $hora_fin<=$horario['hora_fin']){
                        $horarios[]=$horario;
                    }
                    
                    $i++;
                }
                $elto[]=$horarios;
                $repeticion[]=$elto;
            }
        }
        
        /*
         * Esta funcion ordena un arreglo a partir del algoritmo de insercion.
         */
        function ordenar_repeticion ($repeticion, $n){
            for($p=1; $p<$n; $p++){
                
                $tmp=$repeticion[$p][1];
                $j=$p-1;
                
                while($j>=0 && count($tmp)<count($repeticion[$j][1])){
                    $repeticion[$j + 1]=$repeticion[$j];
                    $j--;
                }
                
                $repeticion[$j + 1]=$tmp;
            }
        }
        
        /*
         * Esta funcion determina una asignacion de aulas para el periodo seleccionado. El formato de repeticion:
         * array(0 => array( 0=>aula, 1=>array(f1, f2, ..., fn)) ).
         */
        function establecer_asignacion ($repeticion){
            $fechas=array();
            $n=count($repeticion);
            
            while($n>=0){
                $tmp=$repeticion[$n];
                //$this->existe_fechas_repetidas($fechas, &$tmp);
                unset($repeticion[$n]);
                $this->eliminar_fechas_repetidas($tmp, &$repeticion);
                $fechas[]=$tmp;
                $n--;
            }
            
            return $fechas;
        }
        
        /*
         * En ppio esta funcion elimina las fechas que se encuentran repetidas.
         * @$mayor_repeticion : contiene la fechas disponibles.
         * @$resto_tmp : contiene el resto de fechas que posiblemente sean eliminadas. Su formato es:
         * (0 => array( 0 => aula, 1 => array(f1, f2, f3, ...., fn))). Se pasa por referencia.
         * Y la eficiencia???? => O(n^3).
         */
        function eliminar_fechas_repetidas ($mayor_repeticion, $resto_tmp){
                       
            foreach ($resto_tmp as $key=>$tmp){
                $n=count($tmp[1]);
                $arreglo=$tmp[1];
                //Este indice lo usamos para recorrer las fechas que posiblemente sean eliminadas.
                $j=0;
                
                foreach($mayor_repeticion as $clave=>$valor){
                    
                    while($j<$n){
                        if(strcmp($valor, $arreglo[$i])==0){
                            unset($arreglo[$i]);
                        }
                        $j++;
                    }
                    
                }
            }
                
        }
        
        /*
         * Esta funcion devuelve true si existe fechas repetidas. Tambien las elimina.
         * (0 => array( 0 => aula, 1 => array(f1, f2, f3, ...., fn))).
         * @$tmp : se pasa por referencia.
         */
//        function existe_fechas_repetidas ($fechas, $tmp){
//            $i=0;
//            $n=count($fechas);
//            $fechas_tmp=$tmp[0][1];
//            $j=0;
//            $m=count($fechas_tmp);
//            while($i<$n){
//                $fechas_disponibles=$fechas[$i][1];
//                foreach ($fechas_disponibles as $clave=>$fecha){
//                    while($j<$m){
//                        if(strcmp($fecha, $fechas_tmp[$j])==0){
//                            unset($fechas_tmp[$j]);
//                        }
//                        $j++;
//                    }
//                }
//                
//                $i++;
//            }
//            
//        }
        
//        function extraer_repeticiones ($hd_fecha, $aula, $hora_inicio, $hora_fin){
//            $repeticiones=array();
//            $fechas=array();
//            foreach($hd_fecha as $fecha=>$hd){
//                foreach($hd as $aula=>$horario_disponible){
//                    $hora_inicio_d=$horario_disponible['hora_inicio'];
//                    $hora_fin_d=$horario_disponible['hora_fin'];
//                    if(($hora_inicio>=$hora_inicio_d && $hora_inicio<=$hora_fin_d) && ($hora_fin<=$hora_fin_d)){
//                        //Listamos la fecha.
//                        $fechas[]=$fecha;
//                    }
//                }
//                $repeticiones[]=array($aula['id_aula'] => $fechas);
//                $fechas=array();
//            }
//        }
        
        //repeticiones debe ser != de vacio
//        function seleccionar_mayor ($repeticiones){
//            $mayor=0;
//            $resultado=array();
//            foreach($repeticiones as $aula=>$fechas){
//                $cantidad=count($fechas);
//                if($cantidad > $mayor){
//                    $mayor=$cantidad;
//                    $resultado=$fechas;
//                }
//            }
//            
//            return array($cantidad, $fechas);
//        }
        
        /*
         * Esta funcion permite editar solicitudes en estado pendiente o finalizada. Intentaremos usar la 
         * operacion Solicitar Aula.
         */
        function evt__cuadro__edicion_parcial ($datos){
            //Si el estado de la solicitud es pendiente, podemos editar finalidad y responsable de aula.
            if(strcmp($datos['estado'], 'PENDIENTE')==0 || strcmp($datos['estado'], 'FINALIZADA ACEPTADA')==0){
                $datos['tipo_edicion']='edicion_parcial';
                toba::vinculador()->navegar_a('rukaja', 3571, $datos);
            }
        }
        
        /*
         * Esta funcion permite hacer una edicion completa de una solicitud. La idea de esta operacion es permitir
         * editar hi y hf, pero podemos mantener la finalidad, el responsable de aula etc.
         */
        function evt__cuadro__edicion_total ($datos){
            //Si el estado de la solicitud es pendiente, podemos editar finalidad, responsable de aula, hi y hf.
            if(strcmp($datos['estado'], 'PENDIENTE')==0){
                $datos['tipo_edicion']='edicion_total';
                toba::vinculador()->navegar_a('rukaja', 3571, $datos);
            }else{
                $mensaje="No es posible llevar a cabo una edición total de una solicitud en estado FINALIZADA. En este caso"
                        . " solamente se puede editar el responsable de aula y la finalidad, lo que constituye "
                        . "una edición parcial.";
                toba::notificacion()->agregar(utf8_decode($mensaje), 'info');
            }
        }
        
        /*
         * Esta funcion permite borrar solicitudes de aula, en estado pendiente o finalizada. Si la solicitud 
         * ya esta concedida liberamos un espacio.
         */
        function evt__cuadro__borrar ($datos){
            //Ocurre una situacion extrania. Porque se elimina el registro de la tabla solcitud pero se produce 
            //el siguiente error 'La tabla solicitud requiere al menos 1 registro/s se encontraron solo cero'.
            //La unica solucion que encontre a este problema consiste en usar un bloque try-catch, que capture 
            //la excepcion y no haga nada con ella. 
            try{
                //--Eliminamos la solictud de la tabla idem.
                $this->dep('datos')->tabla('solicitud')->cargar(array('id_solicitud'=>$datos['id_solicitud']));
                $solicitud=$this->dep('datos')->tabla('solicitud')->get();
                
                //--Obtenemos la asignacion derivada de una solicitud, si existe, teniendo en cuenta los 
                //--siguientes atributos: hora_inicio, hora_fin, id_aula, dia, fecha. 
                //--La asignacion se obtiene correctamente a partir del dia y la fecha. La asignacion en 
                //--cuestion es periodica.
                $fecha=$solicitud['fecha'];
                $dia=$this->obtener_dia(date('N', strtotime($fecha)));
                $asignacion=$this->dep('datos')->tabla('asignacion')->get_asignacion($solicitud['hora_inicio'],
                        $solicitud['hora_fin'], $solicitud['id_aula'], $dia, $fecha);
                 
                //--Eliminamos la solicitud. Tambien se produce una excepcion si usamos eliminar_fila.
                $this->dep('datos')->tabla('solicitud')->eliminar_todo();
                $this->dep('datos')->tabla('solicitud')->sincronizar();        
                
            }catch(toba_error $e){
                //No procesamos la excepcion porque se produce lo que nos interesa, que es eliminar una solicitud.
                try{
                //--Ahora falta eliminar la posible asignacion concedida. Pero hay problemas con el id_.
                //--Habria que utilizar otros datos para poder recuperar el mismo, ellos son:
                //--hora_inicio, hora_fin, id_aula, dia, fecha.
                    if(count($asignacion)>0){
                        $this->dep('datos')->tabla('asignacion')->cargar(array('id_asignacion'=>$asignacion[0]['id_asignacion']));
                        //$asignacion=$this->dep('datos')->tabla('asignacion')->get();

                        $this->dep('datos')->tabla('asignacion')->eliminar_todo();
                        $this->dep('datos')->tabla('asignacion')->sincronizar();
                    }
                } catch (toba_error $ex) {
                    //No procesamos la excepcion porque se produce lo que nos interesa, que es eliminar una asignacion.
                }
            }
        }
        
        /*
         * Esta funcion calcula horarios disponibles para el aula especificada en la solcitud.
         * Si existe espacio en el aula seleccionada devolvemos true. Caso contrario devolvemos false.
         * Si obtenemos false en el metodo llamador debemos iniciar un calculo de hd teniendo en cuenta casi todas 
         * las aulas del establecimiento destino. Vamos a hacer uso de la funcion procesar_periodo.
         */
        function calcular_hd_en_aula_seleccionada (){
            $anio_lectivo=date('Y', strtotime($this->s__fecha_consulta));
            //Configuramos el dia de consulta para que este disponible en la funcion procesar_periodo.
            $this->s__dia_consulta=$this->obtener_dia(date('N', strtotime($this->s__fecha_consulta)));
            
            //Obtenemos los periodos que pueden contener a la fecha de solicitud.
            $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($this->s__fecha_consulta, $anio_lectivo, $this->s__id_sede);
            //Usamos la cadena 'au' para extraer las asignaciones pertenecientes a un aula en particular. 
            //Es una condicion mas dentro de la funcion procesar_periodo.
            $asignaciones=$this->procesar_periodo($periodo, 'au');
            
            //Creamos una estructura para el aula seleccionada. Esto se debe a que la funcion calcular_horarios_
            //disponibles de la clase HorariosDisponibes recibe un arreglo de aulas_ua, cuyo formato es
            //(id_aula, aula). Si no usamos esta estructura array ( array() ), vemos una disponibilidad total
            //que no esta asociada a ningun aula, esto ultimo no debe ocurrir.
            $aulas_ua=array(
                                array(
                                     'id_aula' => $this->s__datos_solcitud['id_aula'],
                                     'aula' => $this->s__datos_solcitud['aula']
                                )
            );
            
            //Guardamos en sesion el id_sede para agregar la capacidad de cada aula a un horario disponible.
            toba::memoria()->set_dato_instancia(0, $this->s__id_sede);
            
            $hd=new HorariosDisponibles();
            //El primer parametro se corresponde con las aulas que actualmente estan siendo usadas. Como 
            //necesitamos hacer un calculo de hd especifico, usamos el aula seleccionada. Estas aulas se obtienen
            //a partir de las asignaciones y se utilzan para que el sistema pueda inferir disponibilidad total.
                        
            $this->s__horarios_disponibles=$hd->calcular_horarios_disponibles($aulas_ua, $aulas_ua, $asignaciones);
            
            return $this->existe_espacio_disponible();
        }
        
        /*
         * Esta funcion devuelve true si existe en el aula seleccionada un horario que incluya al horario 
         * de la solicitud. 
         */
        function existe_espacio_disponible (){
            $fin=FALSE;
            $i=0;
            $n=count($this->s__horarios_disponibles);
            while($i<$n && !$fin){
                $elto=$this->s__horarios_disponibles[$i];
                $hi=$this->s__datos_solcitud['hora_inicio'];
                $hf=$this->s__datos_solcitud['hora_fin'];
                
                //Verificamos inclusion.
                if($hi>=$elto['hora_inicio'] && $hi<=$elto['hora_fin'] && $hf<=$elto['hora_fin']){
                    //Cortamos el bucle y en el formulario por defecto usamos la informacion de s__datos_solicitud.
                    $fin=TRUE;
                }
                $i++;
            }
            
            return $fin;
        }
        
        /*
         * Esta funcion permite calcular horarios disponibles alternativos. Se usa cuando no existe el lugar 
         * especificado en la solicitud. Calculamos hd teniendo en cuenta todas las aulas sin importar que ya
         * se hizo un calculo especifico para el aula seleccionada.
         */
        function verificar_existencia_de_espacio (){
            
            $anio_lectivo=date('Y', strtotime($this->s__fecha_consulta));
            $this->s__dia_consulta=$this->obtener_dia(date('N', strtotime($this->s__fecha_consulta)));
            //Debemos usar la fecha seleccionada por el usuario. Necesitamos brindar una respuesta concreta
            //segun los espacios ocupados en ese dia.
            $periodo=$this->dep('datos')->tabla('periodo')->get_periodo_calendario(date('Y-m-d', strtotime($this->s__fecha_consulta)), $anio_lectivo, $this->s__id_sede);
            
            //Obtenemos las aulas del establecimiento.
            $aulas_ua=$this->dep('datos')->tabla('aula')->get_aulas_por_sede($this->s__id_sede);
            
            //Obtenemos las asignaciones para la fecha solicitada. En este caso necesitamos traer las 
            //asignaciones para todas las aulas del establecimiento.
            $asignaciones=$this->procesar_periodo($periodo, 'hd');
            
            //Obtenemos todas las aulas que actualmente estan siendo usadas. 
            $aulas=$this->obtener_aulas($asignaciones);
            
            //Guardamos el id_sede en sesion, para utilizar dentro de la clase HorariosDisponibles en la 
            //operacion agregar_capacidad.
            toba::memoria()->set_dato_instancia(0, $this->s__id_sede);
            
            $horarios_disponibles=new HorariosDisponibles();
            
            $this->s__horarios_disponibles=$horarios_disponibles->calcular_horarios_disponibles($aulas, $aulas_ua, $asignaciones);
            
            //Obtenemos los horarios que coinciden con el requerimiento registrado.
            $horarios=$this->calcular_horarios_disponibles_segun_req();
            
            //Si existe al menos 1 horario libre que coincide con el requerimiento, lo mostramos en el 
            //cuadro_espacio_ocupado :D, -_-
            if(count($horarios) > 0){
                $this->s__horarios_libres=$horarios; //contiene los horarios que conciden con el requerimiento
            }else{
                toba::notificacion()->agregar("No existen horarios disponibles alternativos", 'info');
                return ;
            }
            
        }
                
        /*
         * Con esta funcion eliminamos las asignaciones definitivas solapadas con las asignaciones por
         * periodo. Las asignaciones por periodo tiene prioridad.
         */
        function descartar_asignaciones_definitivas ($asig_periodo, $asig_definitiva){
            $longitud=count($asig_definitiva);
            $i=0;
            foreach ($asig_periodo as $periodo){
                while($i<$longitud){
                    
                    if($this->existe_inclusion($periodo,$asig_definitiva[$i])){
                        //borramos una asignacion definitiva si contiene a una por periodo.
                        //Las asignaciones por periodo tiene prioridad
                        $asig_definitiva[$i]=null;
                    }
                    $i += 1;
                }
                
                $i=0;
            }
        }
        
        /*
         * devuelve true si una asignacion por periodo esta incluida en una definitiva.
         */
        function existe_inclusion ($periodo, $definitiva){
            //(strcmp($periodo['aula'], $definitiva['aula'])==0)
            return ( ($periodo['id_aula'] == $definitiva['id_aula']) && 
                   (($periodo['hora_inicio'] >= $definitiva['hora_inicio']) && ($periodo['hora_inicio'] <= $definitiva['hora_fin'])) &&
                   ($periodo['hora_fin'] <= $definitiva['hora_fin']));
        }
        
        function unificar_asignaciones ($periodo, $definitiva){
            foreach ($definitiva as $clave=>$valor){
                if(isset($valor)){
                   $periodo[]=$valor; //agrega al final
                }
            }            
        }
        
        /*
         * en base a los horarios disponibles, verifica si existe un espacio segun la hora de inicio y fin 
         * especificadas por el usuario en la solicitud
         */
        function calcular_horarios_disponibles_segun_req (){
            $horarios_disponibles=array();
            foreach($this->s__horarios_disponibles as $horario){
                if($this->verificar_existencia_de_horario($horario)){
                    $horarios_disponibles[]=$horario;
                }
            }
            return $horarios_disponibles;
        }
        
        function verificar_existencia_de_horario ($horario){
            $resultado=false;
            
            if(($this->s__datos_solcitud['hora_inicio'] >= $horario['hora_inicio']) && ($this->s__datos_solcitud['hora_inicio'] <= $horario['hora_fin']) && ($this->s__datos_solcitud['hora_fin'] <= $horario['hora_fin'])){
                $resultado=true;
            }
            return $resultado;
            
        }
        
	//---- Formulario -------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if ($this->dep('datos')->esta_cargada()) {
			$form->set_datos($this->dep('datos')->tabla('solicitud')->get());
		}
	}

	function evt__formulario__alta($datos)
	{
		$this->dep('datos')->tabla('solicitud')->set($datos);
		$this->dep('datos')->sincronizar();
		$this->resetear();
	}

	function evt__formulario__modificacion($datos)
	{
		$this->dep('datos')->tabla('solicitud')->set($datos);
		$this->dep('datos')->sincronizar();
		$this->resetear();
	}

	function evt__formulario__baja()
	{
		$this->dep('datos')->eliminar_todo();
		$this->resetear();
	}

	function evt__formulario__cancelar()
	{
		$this->resetear();
	}

	function resetear()
	{
		$this->dep('datos')->resetear();
	}
                
        function obtener_dia ($dia_numerico){
            $dias=array( 
                         1 => 'Lunes', 
                         2 => 'Martes',
                         3 => 'Miércoles', 
                         4 => 'Jueves', 
                         5 => 'Viernes', 
                         6 => 'Sábado'
                );
                        
            return $dias[$dia_numerico];
        }
                
        /*
         * genera un arreglo con las aulas utilizadas en un dia especifico
         * @espacios_concedidos contiene todos los espacios concedidos en las aulas de una Unidad Academica en un dia especifico  
         */
        function obtener_aulas ($espacios_concedidos){
            $aulas=array();
            $indice=0;
            foreach($espacios_concedidos as $clave=>$valor){
                $aula=array(); // indice => (aula, id_aula)
                $aula['aula']=$valor['aula'];
                $aula['id_aula']=$valor['id_aula'];
                //$aula=$valor['aula'];
                $existe=$this->existe($aulas, $aula);
                if(!$existe){
                    $aulas[$indice]=$aula;
                    $indice += 1;
                }
            }
            return $aulas;
        }
        
        /*
         * verifica si un aula ya se encuentra presente en la estructura aulas
         * @aulas contiene un cjto de aulas
         * @aula se verifica que si exista en aulas.
         */
        function existe ($aulas, $aula){
            $existe=FALSE;
            
            if(count($aulas) != 0){
                $indice=0;
                $longitud=count($aulas);
                while(($indice < $longitud) && !$existe){
                    //strcmp($aulas[$indice]['id_aula'], $aula['id_aula'])==0
                    $existe=($aulas[$indice]['id_aula'] == $aula['id_aula']) ? TRUE : FALSE;
                    $indice += 1;
                }
            }
            return $existe;
        }
        
        //----------------------------------------------------------------------------------------
        //---- Pant Busqueda ---------------------------------------------------------------------
        //----------------------------------------------------------------------------------------
        
        function conf__pant_busqueda (){
            $this->s__pantalla_actual="pant_busqueda";
            $this->pantalla()->tab('pant_edicion')->desactivar();
            $this->pantalla()->tab('pant_asignacion')->desactivar();
        }
        
        function conf__filtro (toba_ei_filtro $filtro){
            //No pueden estar ambos arreglos al mismo tiempo con informacion.
            if(count($this->s__horarios_libres)>0){
                $this->s__filtro=new Filtro($this->s__horarios_libres);
                return ;
            }
            if(count($this->s__horarios_disponibles)>0){
                $this->s__filtro=new Filtro($this->s__horarios_disponibles);
                return ;
            }
        }
        
        function evt__filtro__filtrar ($datos){
            $this->s__datos_filtro=$this->s__filtro->filtrar($datos);
        }
        
                        
        function evt__volver (){
            switch($this->s__pantalla_actual){
                case "pant_busqueda"   : $this->set_pantalla('pant_edicion'); 
                                         break;
                case "pant_asignacion" : $this->set_pantalla('pant_edicion'); 
                                         break;
            }
        }
                
        /*
         * cuadro_espacio_ocupado contiene todos los horarios disponibles que coinciden con el requerimiento,
         * su  nombre es medio confuso *_*
         */
        function conf__cuadro_espacio_ocupado (toba_ei_cuadro $cuadro){

            if(count($this->s__horarios_libres) > 0){
                $cuadro->set_titulo("HORARIOS DISPONIBLES ALTERNATIVOS");
                $cuadro->set_datos($this->s__horarios_libres);
            }
            else{
                $cuadro->descolapsar();
            }
                    
        }
        
        function evt__cuadro_espacio_ocupado__asignar ($datos){
            //--Configuramos los datos de la solicitud inicial para autocompletar sin problemas el formulario.
            $this->s__datos_solcitud['id_aula']=$datos['id_aula'];
            $this->s__datos_solcitud['aula']=$datos['aula'];
                        
            $this->set_pantalla('pant_asignacion');
        }
        
        //----------------------------------------------------------------------------------------
        //---- Pant Asignacion -------------------------------------------------------------------
        //----------------------------------------------------------------------------------------
        
        function conf__pant_asignacion () {
            $this->s__pantalla_actual="pant_asignacion";
            $this->pantalla()->tab('pant_busqueda')->desactivar();
            $this->pantalla()->tab('pant_edicion')->desactivar();
        }
        
        //---- Form Datos ------------------------------------------------------------------------
        
        function conf__form_datos (toba_ei_formulario $form){
            if(($this->s__contador_notificacion % 2) == 0){
                $form->descolapsar();
                $form->set_titulo("Datos del responsable de aula");
                $this->obtener_datos_responsable_aula();
                         
                $form->set_datos($this->s__datos_responsable[0]);
            }
            else{
                $form->colapsar();
            }
        }
        
        function obtener_datos_responsable_aula (){
            //En id_responsable podemos tener:
            //a) el legajo de un docente. 
            //b) el id_organizacion.
            //Vamos a hacer uso de las funciones que ya tenemos implementadas en el datos_tabla persona.
            if(strcmp($this->s__datos_solcitud['tipo_agente'], 'Docente')==0){
                //Guardamos los datos del responsable de aula en sesion para poder freezarlos en la base de datos
                //rukaja.
                $this->s__datos_responsable=$this->dep('datos')->tabla('persona')->get_datos_docente($this->s__datos_solcitud['id_responsable']);
            }else{
                $datos_org=$this->dep('datos')->tabla('persona')->get_datos_organizacion($this->s__datos_solcitud['id_responsable']);
                $datos_org['apellido']="----------";
                $copy=$datos_org['id_organizacion'];
                //Renombramos el el id_organizacion para no tener que modificar el formulario en el toba_editor
                //o la consulta sql del datos_tabla.
                $datos_org['legajo']=$copy;
                //Guardamos los datos del responsable de aula en sesion para poder freezarlos en la base de datos
                //rukaja.
                $this->s__datos_responsable=$datos_org;
            }
        }

        //---- Form Asignacion -------------------------------------------------------------------
        
        /*
         * Este formulario se carga con informacion que esta lista para ser registrada en las tablas
         * asignacion, asignacion_periodo y esta_formada.
         */
        function conf__form_asignacion (toba_ei_formulario $form){
            
            
            if(($this->s__contador_notificacion % 2)==0){
              $form->descolapsar();             
                //obtenemos los datos del docente para registrar una asignacion por periodo
                //y enviar una notificacion
                $efs=array( 'tipo_asignacion',
                            'hora_inicio',
                            'hora_fin',
                            'finalidad',
                            'fecha',
                            'capacidad',
                            'aula',
                            'establecimiento',
                );
                $form->set_solo_lectura($efs);
                
                if(strcmp($this->s__datos_solcitud['tipo'], 'MULTI')==0){
                    $efs=array( 'fecha_fin',
                                'dias'                        
                    );
                    $form->set_solo_lectura($efs);
                }else{
                    $form->desactivar_efs(array('fecha_fin', 'dias'));
                }
                
                $form->set_titulo("Formulario para registrar Asignaciones por Periodo");
                                
                $form->set_datos($this->s__datos_solcitud);
                //$form->set_datos_defecto($this->dep('datos')->tabla('solicitud')->get_datos_solicitud($this->s__id_solicitud));
                //$form->ef('aula')->set_estado($this->s__nombre_aula);
                //$form->set_datos_defecto($datos_docente[0]);
           }
           else{
               $form->colapsar();
           }
        }
        
        /*
         * En esta funcion debemos guardar la solicitud en las tablas asignacion, asignacion_periodo y 
         * esta_formada. Ademas debemos pasar la solicitud a estado finalizada.
         */
        function evt__form_asignacion__aceptar ($datos){
            //Falta asociar datos del responsable de aula.
            
            if(strcmp($this->s__datos_solcitud['tipo'], "UNICO")==0){
                $fecha_fin=$datos['fecha'];
                //Creamos esta estructura para no alterar el comportamiento de la funcion registrar_solicitud.
                //Debe permitir registrar solicitudes unicas o multi.
                $dia=array( array(
                           'id_solicitud' => $this->s__datos_solcitud['id_solicitud'], 
                           'fecha' => $this->s__datos_solcitud['fecha'],
                           'nombre' => utf8_decode($this->obtener_dia(date('N', strtotime($this->s__datos_solcitud['fecha']))))
                    ));
            }else{
                //Obtenemos la fecha de fin que esta almacenada en la tabla solicitud_multi_evento.
                $fecha_fin=$this->dep('datos')->tabla('solicitud')->get_datos_multi($this->s__datos_solcitud['id_solicitud']);
                //Esto surge de una consulta en la bd. Su formato es: 
                //Array('id_solicitud', 'nombre', 'fecha').
                $dia=$this->dep('datos')->tabla('solicitud')->get_lista_fechas($this->s__datos_solcitud['id_solicitud']);
                
            }
            
            $this->registrar_solicitud($datos, $dia, $this->s__datos_solcitud['fecha'], $fecha_fin);
            
            //--Para no quedarnos en la misma pantalla con el formulario autocompletado.
            $this->set_pantalla("pant_edicion");
            
        }
        
        /*
         * Esta funcion permite registrar una solicitud unica o multi-evento. Se emplean las tablas asignacion,
         * asignacion_periodo y esta_formada.
         * @dia : si la solicitud es unica dia contiene un unico dia/fecha, caso contrario contiene una lista 
         * de dias/fechas. 
         */
        function registrar_solicitud ($datos, $dia, $fecha_inicio, $fecha_fin){
            
            $anio_lectivo=date('Y', strtotime($fecha_inicio));
            
            $periodos=$this->dep('datos')->tabla('periodo')->get_periodo_calendario($fecha_inicio, $anio_lectivo, $this->s__datos_solcitud['id_sede']);
            
            $id_=$this->obtener_periodo($periodos, $this->s__datos_solcitud['tipo_asignacion']);
                        
            if(strcmp($this->s__datos_solcitud['tipo_agente'], "Organizacion")==0){
                $apellido=' . ';
                $nro_doc=' - ';
                $tipo_doc=' - ';
            }else{
                $apellido=$this->s__datos_responsable[0]['apellido'];
                $nro_doc=$this->s__datos_responsable[0]['nro_docum'];
                $tipo_doc=$this->s__datos_responsable[0]['tipo_docum'];
            }
            //--Usamos ambos arreglos, $datos y $s__datos_solcitud.
            $asignacion=array(
                'finalidad' => $datos['finalidad'],
                'descripcion' => $datos['descripcion'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
                'cantidad_alumnos' => $datos['capacidad'],
                'facultad' => $this->s__datos_solcitud['facultad'],
                'nro_doc' => $nro_doc,
                'tipo_doc' => $tipo_doc,
                'id_aula' => $this->s__datos_solcitud['id_aula'],
                'modulo' => 1,
                'tipo_asignacion' => $this->s__datos_solcitud['tipo_asignacion'],
                'id_periodo' => $id_,
                'id_responsable_aula' => $this->s__datos_solcitud['id_responsable'],
                'nombre' => $this->s__datos_responsable[0]['nombre'],
                'apellido' => $apellido,
                'legajo' => $this->s__datos_responsable[0]['legajo'],
            );
                        
            //Este grupo de funciones se copia tal cual desde la operacion Cargar Asignaciones. En este caso
            //intentamos no alterar sus estructuras.
            $this->registrar_asignacion($asignacion);
            
            //Agregamos a $asignacion datos extra relacionados a un periodo, para no alterar la estructura 
            //de esta funcion. $dia tiene el mismo formato retornado por ef_multi_seleccion_check.
            $asignacion['dias']=$dia;
            $asignacion['fecha_inicio']=$fecha_inicio;//$this->s__datos_solcitud['fecha'];
            $asignacion['fecha_fin']=$fecha_fin;//$this->s__datos_solcitud['fecha'];
            
            $this->registrar_asignacion_periodo($asignacion);
                       
            //Pasamos la solicitud a estado finalizada. Para ello solamente necesitamos el id_solicitud guardado
            //en la variable s__datos_solcitud. Internamente vamos a utilizar datos_tabla.
            $this->pasar_a_estado_finalizada('ACEPTADA');
            
            //Enviamos una notificacion al interesado.
            //$this->notificar();
        }
        
        /*
         * Esta funcion devuelve un periodo academico adecuado al tipo de asignacion vinculado a la solicitud.
         * @$periodos: contiene 1 o mas periodos academicos.
         * @$tipo_asignacion: puede contener 'CURSADA', 'EVENTO', 'CONSULTA' etc.
         */
        function obtener_periodo ($periodos, $tipo_asignacion){
            //--Si obtenemos un unico periodo, lo utilizamos en cualquier tipo de asignacion. Esto no afecta el 
            //--proceso de calculo de hd.
            if(count($periodos)==1){
                return $periodos[0]['id_periodo'];
            }
            
            //--Si accedemos a esta rama es porque hay dos periodos que tienen incluida la fecha_inicio de la
            //--solicitud. Debemos buscar el periodo adecuado.
            $id=0;
            $fin=FALSE;
            $i=0;
            $n=count($periodos);
            while($i<$n && !$fin){
                $periodo=$periodos[$i]['tipo_periodo'];
                
                switch($tipo_asignacion){
                    case 'CURSADA'        :
                    case 'EXAMEN PARCIAL' :
                    case 'EVENTO'         :
                    case 'CONSULTA'       : if(strcmp($periodo, 'Cuatrimestre')==0){
                                                $fin=TRUE;
                                                $id=$periodo[$i]['id_periodo'];
                                            }
                                            break;

                    case 'EXAMEN FINAL'   : if(strcmp($periodo, 'Examen Final')==0){
                                                $fin=TRUE;
                                                $id=$periodo[$i]['id_periodo'];
                                            }
                                            break;
                }
                
                $i++;
            }
                       
            return $id;
        }
        
        function registrar_asignacion ($datos){
            $this->dep('datos')->tabla('asignacion')->nueva_fila($datos);
            $this->dep('datos')->tabla('asignacion')->sincronizar();
            $this->dep('datos')->tabla('asignacion')->resetear();
        }
        
        function registrar_asignacion_periodo ($datos){
            //Obtenemos el id de la asignacion registrada en el paso anterior.
            $secuencia=recuperar_secuencia('asignacion_id_asignacion_seq');
            
            //La secuencia de la tabla asignacion_periodo es: asignacion_periodo_id_asignacion_seq.
            
            $periodo=array(
                    'id_asignacion' => $secuencia,
                    'fecha_inicio' => $datos['fecha_inicio'],
                    'fecha_fin' => $datos['fecha_fin']
            );
            
            $this->dep('datos')->tabla('asignacion_periodo')->nueva_fila($periodo);
            $this->dep('datos')->tabla('asignacion_periodo')->sincronizar();
            $this->dep('datos')->tabla('asignacion_periodo')->resetear();
            
            //En esta seccion se guarda informacion en la tabla esta_formada. Podemos tener varios dias. Su formato es:
            //array( 0 => array(id_solictud, nombre, fecha), ..., )
            $dias=$datos['dias'];
            foreach ($dias as $clave=>$dia){
                
                $dato['nombre']= $dia['nombre'];
                $dato['id_asignacion']=$secuencia;
                $dato['fecha']=$dia['fecha'];
                $this->dep('datos')->tabla('esta_formada')->nueva_fila($dato);
                $this->dep('datos')->tabla('esta_formada')->sincronizar();
                $this->dep('datos')->tabla('esta_formada')->resetear();
            }
                        
        }
        
        /*
         * A partir de una fecha devolvemos el nombre del dia 
         */
        function recuperar_dia ($fecha){
            //Si usamos w obtenemos 0 para domingo y 6 para sabado.
            //Si usamos N obtenemos 1 para lunes y 7 para domingo.
            $dia_numerico=date('N', strtotime($fecha));
            
            //Devolvemos el dia en un arreglo para no modificar la funcion registrar_asignacion_periodo.
            return (array($this->obtener_dia($dia_numerico)));
        }
        
        /*
         * A partir de una fecha devolvemos el anio 
         */
        function recuperar_anio ($fecha){
            
            $anio=date('Y', strtotime($fecha));
            
            return $anio;
        }
        
        function notificar (){
            //Obtenemos datos personales del responsable de aula que escribio* la solicitud.
            //Esta informacion se utiliza para firmar la notificacion. Los emails se envian desde una cuenta
            //gmail perteneciente a rukaja. Esta cuenta es rukaja.uncoma@gmail.com. No necesitamos obtener datos
            //asociados a un emisor, esto se configura en la funcion enviar_email de la clase Email.
            
            //Si necesitamos el correo electronico del responsable de aula.
            $destinatario=$this->dep('datos')->tabla('administrador')->get_email(toba::usuario()->get_id());
            //Creamos un asunto por defecto.
            $asunto="SOLICITUD CONCEDIDA";
                        
            $firma=toba::usuario()->get_nombre();
            //Creamos una descripcion por defecto. Si usamos un aula distinta a la especificada en la solicitud
            //debemos cambiar la descripcion, indicando la nueva aula.  
            //*********
            //$descripcion="La SOLICITUD DE AULA en el Establecimiento {$emisor['establecimiento']}, para el dia {$this->s__fecha} en el horario {$this->s__hora_inicio} a {$this->s__hora_fin} hs ha sido registrada exitosamente. \n\n {$emisor['responsable']}";
            $descripcion="La SOLICITUD DE AULA en el Establecimiento Administracion Central, para el dia {$this->s__fecha_consulta} en el horario {$this->s__hora_inicio} - {$this->s__hora_fin} hs ha sido registrada exitosamente. \n\n $firma";
            
            $email=new Email();
            //Enviamos un email automaticamente. Su objetivo es notificar el resultado positivo de la solicitud.
            $email->enviar_email($destinatario, $asunto, $descripcion);
                                    
            //Volvemos a la pantalla inicial.
            $this->set_pantalla('pant_edicion');
        }    
                
        function vista_pdf (toba_vista_pdf $salida){
            $this->generar_pdf($salida);      
            
            $this->s__notificar=TRUE;
        }
        
        function generar_pdf (toba_vista_pdf $salida){
            //obtenemos un objeto Cezpdf
            $pdf=$salida->get_pdf();
            
            //configuramos los margenes del pdf (top, bottom, left, right)
            $pdf->ezSetMargins(40, 40, 33, 33);
            
            //definimos el formato del pie de pagina
            $pie_de_pagina="Página {PAGENUM} de {TOTALPAGENUM}";
            
            //agregamos el numero de pagina al pdf
            $pdf->ezStartPageNumbers(300, 20, 8, 'left', utf8_d_seguro($pie_de_pagina));
            
            //definimos el formato de la tabla que contiene el pdf
            $formato_cuadro=array(
                'splitRows' => 0,
                'rowGraph' => 1,
                'showHeadings' => true,
                'titleFontSize' => 9,
                'fontSize' => 10, //definimos el tamanio de fuente
                'shadeCol' => array(0.9,0.9,0.9), //especificamos el color de cada fila
                'xOrientation' => 'center',
                'width' => 500,
                'xPos' => 'center',
                'yPos' => 'center',
            );
            
            //definimos los nombres de las columnas de la tabla
            $formato_columna=array(
                'hora_inicio' => 'Hora de Inicio',
                'hora_fin' => 'Hora de Fin',
                'aula' => 'Aula',
            );
            
            //definimos el encabezado del documento
            $this->configurar_encabezado($pdf);
            
            //definimos el nombre del archivo
            $salida->set_nombre_archivo("Horarios Disponibles Alternativos.pdf");
                        
            //agregamos la tabla al pdf
            $pdf->ezTable($this->s__horarios_disponibles, $formato_columna, "", $formato_cuadro);
            
            //cerramos el buffer de salida. Si no se cierra dicho buffer el archivo pdf se genera dañado
            ob_end_clean(); 
            $pdf->ezOutput(0);
            
            //encabezados HTTP al cliente
            header('Cache-Control: private');
            header('Content-type: application/pdf');
            header('Content-Disposition: attachment; '); //filename="Archivo"
            header('Pragma: no-cache');
            header('Expires: 0');            
        }
        
        function configurar_encabezado (Cezpdf $pdf){
            //obtenemos los datos personales del responsable de aula
//            $sql="SELECT (t_a.nombre || t_a.apellido) as nombre_usuario, t_s.descripcion sede, t_ua.descripcion facultad
//                  FROM administrador t_a 
//                  JOIN sede t_s ON (t_a.id_sede=t_s.id_sede)
//                  JOIN unidad_academica t_ua ON (t_s.sigla=t_ua.sigla)
//                  WHERE t_s.id_sede={$this->s__id_sede}";
//            $datos_admin=toba::db('gestion_aulas')->consultar($sql);
//            $firma=$datos_admin[0]['nombre_usuario'];
//            $this->s__nombre_sede=$datos_admin[0]['sede'];
//            $this->s__nombre_facultad=$datos_admin[0]['facultad'];
            
            $this->s__nombre_facultad='Facultad de Ciencias del Ambiente y la Salud';
            $this->s__nombre_sede='Neuquén Capital';
            
            //obtenemos fecha y hora para arnar el encabezado inicial
            $fecha=date('d-m-y');
            $hora=date('H:m:s');
            //definimos una cadena origen para crear el resto del encabezado
            $origen="Fecha : $fecha--------------Hora : $hora";
            
            $encabezado_inicial=$this->armar_precontenido($origen, TRUE);
            
            $solicitud="DATOS DE LA SOLICITUD\n";
            
            $pdf->ezText($encabezado_inicial, 8, array('justification' => 'left'));
            $pdf->ezText($solicitud, 8, array('justification' => 'center'));
            
            $cuerpo=$this->armar_cuerpo($origen);
            $pdf->ezText(utf8_d_seguro($cuerpo), 8, array('justification' => 'left'));
            
            $horario="HORARIOS ALTERNATIVOS DISPONIBLES\n";
            $pdf->ezText($horario, 8, array('justification' => 'center'));
            
            $linea=$this->armar_precontenido($origen, FALSE);
            $pdf->ezText($linea, 8, array('justification' => 'left'));
            
            //definimos el ultimo segmento del encabezado    
            $fin="Estimado/a {$this->s__nombre} {$this->s__apellido} :\n\nLe informamos que en la fecha {$this->s__fecha_consulta} no tenemos aulas disponibles para satisfacer el requerimiento especificado en este documento.\nA continuación adjuntamos los horarios que tenemos disponibles.\n";
            $pdf->ezText(utf8_d_seguro($fin), 8, array('justification' => 'left'));
            
            $firma="Santiago Briceño.\n\n";
            $pdf->ezText(utf8_d_seguro($firma), 8, array('justification' => 'right'));
            
       }
        
        /*
         * Agrega las primeras tres lineas al documento.
         */
        function armar_precontenido ($origen, $resultado){
            $longitud=  strlen($origen) + 12;
            $guion='';
            $i=0;
            while($i<$longitud){
                $guion .= '-';
                $i += 1;
            }
            
            return ($resultado) ? ($origen . "\n\n" . $guion . "\n") : ($guion . "\n");
        }
        
        /*
         * Genera los datos de la solicitud alineados.
         */
        function armar_cuerpo ($origen){
            $solicitud=$this->dep('datos')->tabla('solicitud')->get_datos_basicos_solicitud($this->s__id_solicitud);      
            $this->s__nombre=$solicitud[0]['nombre'];
            $this->s__apellido=$solicitud[0]['apellido'];
            $codigo="Número de Solicitud : {$this->s__id_solicitud}";
            $emisor="Emisor : {$solicitud[0]['nombre']} {$solicitud[0]['apellido']}";
            $establecimiento="Establecimiento : {$this->s__nombre_facultad} ({$this->s__nombre_sede})";
            $hora_inicio="Hora de Inicio : {$this->s__hora_inicio}";
            $hora_fin="Hora de Fin : {$this->s__hora_fin}";
            $capacidad="Capacidad del aula : {$this->s__capacidad} Alumnos";
            $fecha_pedido="Fecha de Pedido : {$this->s__fecha_consulta}";
            $finalidad="Finalidad : {$solicitud[0]['finalidad']}";
            
            //definimos una longitud maxima para completar con espacios en blanco cada oracion del documento
            $longitud=  85; 
            
            $items=array(
                0 => array($codigo, $emisor),
                1 => array($establecimiento),
                2 => array($finalidad),
                3 => array($capacidad, $fecha_pedido),
                4 => array($hora_inicio,$hora_fin),
            );
            $cadena="";           
            foreach($items as $clave=>$valor){
                
                $cadena .= $this->generar_cadena($valor, $longitud);
                
            }
            
            return ($this->armar_precontenido($origen, FALSE) . "\n" .$cadena . $this->armar_precontenido($origen, FALSE));
        }
        
        /*
         * El objetivo de esta funcion es alinear los dos componentes que forman una oracion del encabezado.
         */
        function generar_cadena ($par, $longitud){
            
            $resultado="";
            if((count($par)) == 2){
                $iterar_hasta=$longitud - (strlen(ltrim($par[0])));
                $i=0;
                //definimos como separador a un espacio en blanco
                $separador="";
            
                while($i < $iterar_hasta){
                    $separador .= " ";
                    $i += 1;
                }
                
                $resultado=(($par[0]) . $separador . (ltrim($par[1])) . "\n\n");
            }
            else{
                $resultado=(($par[0]) . "\n\n");
            }
            
            return $resultado;
        }
        
        /*
         * Se utiliza para pasar una solicitud a estado finalizada.
         * @$resultado: contiene 'ACEPTADA' o 'RECHAZADA'.
         */
        function pasar_a_estado_finalizada ($resultado){
            $this->s__datos_solcitud['estado']="FINALIZADA $resultado";
            $this->dep('datos')->tabla('solicitud')->cargar(array('id_solicitud'=>$this->s__datos_solcitud['id_solicitud']));
            $this->dep('datos')->tabla('solicitud')->set($this->s__datos_solcitud);
            $this->dep('datos')->tabla('solicitud')->sincronizar();
        }
        
//        window.addEventListener('load', calculos, false);
//        function calculos() {
//            .........................
//        }
        
        //-----------------------------------------------------------------------------------------
        //---- Interfaz para procesar periodos ----------------------------------------------------
        //-----------------------------------------------------------------------------------------
        
        /*
         * Aqui agregamos toda la interfaz apropiada para devolver las asignaciones para una fecha en particular.
         * Si recibimos en la variable accion:
         * au : debemos obtener las asignaciones pertenecientes a un aula.
         * hd : obtenemos las asignaciones para calcular horarios disponibles en todas las aulas de un 
         *      establecimiento.
         * 
         * Esta funcion se copio desde la operacion Calendario Comahue, por lo tanto hay cosas que se pueden 
         * sacar definitivamente, como lo que esta comentado.
         */
        function procesar_periodo ($periodo, $accion){
            $cuatrimestre=array();
            $examen_final=array();
            foreach ($periodo as $clave=>$valor){
                
                switch ($valor['tipo_periodo']){
                    case 'Cuatrimestre' : if(strcmp($accion, 'hd')==0){
                                              //Obtenemos asignaciones definitivas y periodicas para empezar calculos de horarios
                                              //disponibles en todas las aulas de un establecimiento.
                                              $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_cuatrimestre($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                              
                                          }
                                          else{ //Si accion es 'au'.
                                              //Esta consulta nos permite obtener asignaciones definitivas y periodicas en un aula y fecha en particular.
                                              $cuatrimestre=$this->dep('datos')->tabla('asignacion')->get_asignaciones_por_aula_cuatrimestre(utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta, $this->s__datos_solcitud['id_aula']);
                                              
                                          }
                                          break;
                                      
                    case 'Examen Final' : if(strcmp($accion, 'hd')==0){
                                              //Obtenemos todas las asignaciones por periodo, que estan inluidas en un cuatrimestre,
                                              //pero que pertenecen a un examen_final
                                              $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_examen_final($this->s__id_sede, utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta);
                                          }
                                          else{ //Si accion es 'au'.
                                              //Obtenemos asignaciones periodicas que pertenecen a un turno de examen de un aula especifica.
                                              $examen_final=$this->dep('datos')->tabla('asignacion')->get_asignaciones_por_aula_examen_final(utf8_decode($this->s__dia_consulta), $valor['id_periodo'], $this->s__fecha_consulta, $this->s__datos_solcitud['id_aula']);
                                          }
                                          break;
                }
                
            }
            
            if((count($cuatrimestre)>0) && (count($examen_final)>0)){
                if(strcmp($accion, 'hd')==0 || strcmp($accion, 'au')==0){
                    //Debemos iniciar descarte y unificacion
                    //asig_definitivas & periodicas = cuatrimestre, asig_periodo = examen final.
                    $this->descartar_asignaciones_definitivas($examen_final, &$cuatrimestre);
                    
                    //Las asignaciones periodicas para examen final tienen prioridad.
                    $this->unificar_asignaciones(&$examen_final, $cuatrimestre);

                    return $examen_final;
                }
//                else{
//                    //Debemos unificar periodo con examen final.
//                    $this->unificar_asignaciones(&$periodo, $examen_final);
//                    $this->s__asignaciones_periodo=$periodo;
//                    return $cuatrimestre;
//                }
            }
            
            if((count($cuatrimestre)>0) && (count($examen_final)==0)){
                if(strcmp($accion, 'hd')==0 || strcmp($accion, 'au')==0){
                    //devolvemos solo cuatrimestre
                    return $cuatrimestre;
                }
//                else{
//                    //devolvemos cuatrimestre y guardamos en una variable de sesion el resultado obtenido en 
//                    //periodo
//                    $this->s__asignaciones_periodo=$periodo;
//                    return $cuatrimestre;
//                }
            }
            
            if((count($cuatrimestre)==0) && (count($examen_final)>0)){
                if(strcmp($accion, 'hd')==0 || strcmp($accion, 'au')==0){
                    //devolvemos solo examen final
                    return $examen_final;
                }
//                else{
//                    $this->s__asignaciones_periodo=$examen_final;
//                    return $cuatrimestre;
//                }
            }
            
            if((count($cuatrimestre)==0) && (count($examen_final)==0)){
                if(strcmp($accion, 'hr')==0){
                    $this->s__asignaciones_periodo=$periodo;
                }
                 
                //devolvemos vacio
                return array();
                               
            }
        }
        
        

}

?>