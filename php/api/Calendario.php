<?php

require_once(toba_dir().'/php/nucleo/componentes/interface/toba_ei_calendario.php');
/*
 * Personalizacion de la clase toba_ei_calendario
 */
class calendario_aulas_comahue extends toba_ei_calendario {
    function ini (){
        $this->_calendario=new cal();
    }
    
//    public function __construct() {
//        $this->_calendario=new cal();
//    }
//    
//    function get_ (){
//        return $this->_calendario;
//    }
    
}

class cal extends calendario {
    
    protected $_meses=array(
        1 => 31, 2 => 0, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31
    );
    
    protected $_dias=array(
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
    );
    
    /**
     * Construye las celdas asociadas a los dias de ls semana. Se personalizo con css3.
     */
    function mkWeekDays()
    {
            $out = '';
            if ($this->startOnSun) {
                    if ($this->mostrar_semanas) {

                            $out .="<tr class=\"".$this->cssWeekDay."\"><td>"."Sem"."</td>";
                    }
                    $out.='<td>'.$this->getDayName(0).'</td>';
                    $out.='<td>'.$this->getDayName(1).'</td>';
                    $out.='<td>'.$this->getDayName(2).'</td>';
                    $out.='<td>'.$this->getDayName(3).'</td>';
                    $out.='<td>'.$this->getDayName(4).'</td>';
                    $out.='<td>'.$this->getDayName(5).'</td>';
                    $out.='<td>'.$this->getDayName(6)."</td></tr>\n";
            } else {
                    if ($this->mostrar_semanas) {
                            $out .="<tr class=\"".$this->cssWeekDay."\"><td>".'Sem'.'</td>';
                        //$out .="<tr style=\"".'background-color:rgba(0,51,255,0.75);color:white;'."\"><td>".'Sem'.'</td>';
                    }
                    $out.='<td style='.'background-color:rgba(0,51,255,0.75);color:white;border-collapse:groove;'.'>'.$this->getDayName(1).'</td>';
                    $out.='<td style='.'background-color:rgba(0,51,255,0.75);color:white;border-collapse:groove;'.'>'.$this->getDayName(2).'</td>';
                    $out.='<td style='.'background-color:rgba(0,51,255,0.75);color:white;border-collapse:groove;'.'>'.$this->getDayName(3).'</td>';
                    $out.='<td style='.'background-color:rgba(0,51,255,0.75);color:white;border-collapse:groove;'.'>'.$this->getDayName(4).'</td>';
                    $out.='<td style='.'background-color:rgba(0,51,255,0.75);color:white;border-collapse:groove;'.'>'.$this->getDayName(5).'</td>';
                    $out.='<td style='.'background-color:rgba(0,51,255,0.75);color:white;border-collapse:groove;'.'>'.$this->getDayName(6).'</td>';
                    $out.='<td style='.'background-color:rgba(0,51,255,0.75);color:white;border-collapse:groove;'.'>'.$this->getDayName(0)."</td></tr>\n";
                    $this->firstday=$this->firstday-1;
                    if ($this->firstday<0) {
                            $this->firstday=6;
                    }
            }
            return $out;
    }
    
    /**
     * Construye las celdas asociadas al numero de semana. Se personalizo con css3.
     */
    function mkWeek($date, $objeto_js=null, $eventos=array())
    {
            $week = $this->weekNumber($date);
            $year = $this->mkActiveDate('Y',$date);

            if (!$this->get_weekLinks()) {
                    if ($week == $this->getSelectedWeek() && $year == $this->getSelectedYear()) {
                            $out = "<td class=\"".$this->cssSelecDay."\">".$this->weekNumber($date)."</td>\n";
                    } else {
                            $out = "<td class=\"".$this->cssWeek."\">".$this->weekNumber($date)."</td>\n";
                    }
            } else {
                    if ($this->compare_week($this->weekNumber($date),$this->actyear) == 1) {
                            //$out = "<td class=\"".$this->cssWeekNoSelec."\">".$this->weekNumber($date)."</td>\n";
                            $out = "<td style=\"".'background-color:rgba(0,51,255,0.75);color:white;'."\">".$this->weekNumber($date)."</td>\n";
                    } else {	
                            $evento_js = toba_js::evento('seleccionar_semana', $eventos['seleccionar_semana'], "{$this->weekNumber($date)}||{$this->mkActiveDate('Y',$date)}");
                            $js = "{$objeto_js}.set_evento($evento_js);";

                            if ($week == $this->getSelectedWeek() && $year == $this->getSelectedYear()) {
                                    $out = "<td class=\"".$this->cssSelecDay."\" style='cursor: pointer;cursor:hand;' onclick=\"$js\">".$this->weekNumber($date)."</td>\n";	
                            } else { $out = "<td class=\"".$this->cssWeek."\" style='cursor: pointer;cursor:hand;background-color:rgba(0,51,255,0.75);' onclick=\"$js\">".$this->weekNumber($date)."</td>\n";
                                    //$out = "<td class=\"".$this->cssWeek."\" style='cursor: pointer;cursor:hand;' onclick=\"$js\">".$this->weekNumber($date)."</td>\n";	
                            }
                    }		
            }	
            return $out;
    }
    
    /**
     * Construye las celdas correspondientes a los dias del mes. Se personalizo con css3. La sentencia
     * !$this->get_dayLinks() es false.
     */
    function mkDay($var, $objeto_js=null, $eventos=array())
    {
            if ($var <= 9) {
                    $day = "0$var";
            } else {
                    $day = $var;	
            }
            $eventContent = $this->mkEventContent($var);
            $content = ($this->get_showEvents()) ? $eventContent : '';

            if (is_null($objeto_js)) {
                    $objeto_js = $this->get_id_objeto_js();
            }		

            $evento_js = toba_js::evento('seleccionar_dia', $eventos['seleccionar_dia'], "{$day}||{$this->actmonth}||{$this->actyear}");
            $js = "{$objeto_js}.set_evento($evento_js);";
            $day = $this->mkActiveTime(0,0,1,$this->actmonth,$var,$this->actyear);

            $resalta_hoy = ($this->siempre_resalta_dia_actual || $this->getSelectedDay() < 0);

            if ($this->solo_pasados && $this->compare_date($day) == 1) {
                    //Es una fecha futura y no se permite clickearla
                    $out="<td class=\"".$this->cssSunday."\">".$var.$content.'</td>';		
            } elseif (($this->get_dayLinks()) && ((!$this->get_enableSatSelection() && ($this->getWeekday($var) == 0)) || ((!$this->get_enableSunSelection() && $this->getWeekday($var) == 6)))) {
                    $out="<td class=\"".$this->cssSunday."\">".$var.'</td>';			
            } elseif ($var==$this->getSelectedDay() && $this->actmonth==$this->getSelectedMonth() && $this->actyear==$this->getSelectedYear()) {
                    if (!$this->get_dayLinks()) {
                            $out="<td class=\"".$this->cssSelecDay."\">".$var.$content.'</td>';
                    } else {
                            $out="<td class=\"".$this->cssSelecDay."\"style='cursor: pointer;cursor:hand;background-color:rgba(0,51,255,0.75);color:white;vertical-align:middle;border:inset;border-color:rgba(176,9,109,0.78);' onclick=\"$js\">".$var.$content.'</td>';
                    }
            } elseif ($var==$this->daytoday && $this->actmonth==$this->monthtoday && $this->actyear==$this->yeartoday && $resalta_hoy && $this->getSelectedMonth()==$this->monthtoday && $this->getSelectedWeek()<0) {
                    if (!$this->get_dayLinks()) {
                            $out="<td class=\"".$this->cssToday."\">".$var.$content.'</td>';
                    } else {
                            $out="<td class=\"".$this->cssToday."\"style='cursor: pointer;cursor:hand;background-color:rgba(0,51,255,0.75);color:white;vertical-align:middle;border:inset;border-color:rgba(176,9,109,0.78);' onclick=\"$js\">".$var.$content.'</td>';
                    }
            } elseif ($this->getWeekday($var) == 0 && $this->crSunClass){
                    if (!$this->get_dayLinks()) {
                            $out="<td class=\"".$this->cssSunday."\">".$var.$content.'</td>';
                    } else {
                            $out="<td class=\"".$this->cssSunday."\"style='cursor: pointer;cursor:hand;background-color:rgba(176,9,109,0.78);color:white;font-weight:bold;vertical-align:middle;' onclick=\"$js\">".$var.$content.'</td>';
                    }
            } elseif ($this->getWeekday($var) == 6 && $this->crSatClass) {
                    if (!$this->get_dayLinks()) {
                            $out="<td class=\"".$this->cssSaturday."\">".$var.$content.'</td>';
                    } else { 
                            $out="<td class=\"".$this->cssSaturday."\"style='cursor: pointer;cursor:hand;background-color:rgba(176,9,109,0.78);color:white;font-weight:bold;vertical-align:middle;' onclick=\"$js\">".$var.$content.'</td>';
                    }
            } else {
                    if (!$this->get_dayLinks()) { 
                            $out="<td class=\"".$this->cssMonthDay."\">".$var.$content.'</td>';
                    } else { $out="<td class=\"".$this->cssMonthDay."\"style='cursor: pointer;cursor:hand;background-color:rgba(176,9,109,0.78);color:white;vertical-align: middle;' onclick=\"$js\">".$var.$content.'</td>';
                            //$out="<td class=\"".$this->cssMonthDay."\"style='cursor: pointer;cursor:hand;' onclick=\"$js\">".$var.$content.'</td>';
                    }
            }		

            return $out;
    }
    
    /**
     * El DatePicker es la seccion del calendario que posee el nombre del mes y dos combos, uno para seleccionar 
     * el mes y otro para seleccionar el año.
     */
//    function mkDatePicker($objeto_js, $eventos=array())
//    {
//            $pickerSpan = 8;
//            if ($this->datePicker) {
//                    $evento_js = toba_js::evento('cambiar_mes', $eventos['cambiar_mes']);
//                    $js = "{$objeto_js}.set_evento($evento_js);";
//                    print_r("Entramos en la subclase");
//                    $out="<tr><td class=\"".$this->cssPicker."\" colspan=\"".$pickerSpan."\">\n";
//                    $out.="<select name=\"".$this->monthID."\" id=\"".$this->monthID."\" class=\"".$this->cssPickerMonth."\" onchange=\"$js\">\n";
//                    for ($z=1;$z<=12;$z++) {
//                            if ($z <= 9) {
//                                    $z = "0$z";
//                            }
//                            if ($z==$this->actmonth) {//$this->getMonthName($z)
//                                    $out.="<option value=\"".$z."\" selected=\"selected\" >".(parent::getMonthName($z))."</option>\n";
//                            } else {//$this->getMonthName($z)
//                                    $out.="<option value=\"".$z."\">".(parent::getMonthName($z))."</option>\n";
//                            }
//                    }
//                    $out.="</select>\n";
//                    $out.="<select name=\"".$this->yearID."\" id=\"".$this->yearID."\" class=\"".$this->cssPickerYear."\" onchange=\"$js\">\n";
//                    for ($z=$this->startYear;$z<=$this->endYear;$z++) {
//                            if ($z==$this->actyear) {
//                                    $out.="<option value=\"".$z."\" selected=\"selected\">".$z."</option>\n";
//                            } else {
//                                    $out.="<option value=\"".$z."\">".$z."</option>\n";
//                            }
//                    }
//                    $out.="</select>\n";
//                    $out.="</td></tr>\n";
//            }
//            return $out;
//    }
      
    function mkMonthHead()
    {
            $out = "<div align='center' >";
            $out .= "<table class=\"".$this->cssMonthTable."\" style='background-color:rgba(0,51,255,0.09);'>\n";
            
            return $out;
    }
    
    /*
     * Esta funcion devuelve los dias que pertenecen a un periodo, formado por fecha_inicio y fecha_fin.
     */
    public function get_dias ($fecha_inicio, $fecha_fin, $dias_seleccionados){
        //aca debemos tratar el caso del mes de febrero, que puede tener 29 dias si el anio es bisiesto
        $anio=date('Y');
        $febrero=(($anio%400==0) || (($anio%4==0)&&($anio%100 != 0))) ? 29 : 28;
        $this->_meses[2]=$febrero;
        
        //obtenemos dia (01 a 31) y mes (01 a 12) con representacion numerica
        $dia_inicio=date('d', strtotime($fecha_inicio));
        $mes_inicio=date('m', strtotime($fecha_inicio));
                
        $dia_fin=date('d', strtotime($fecha_fin));
        $mes_fin=date('m', strtotime($fecha_fin));
        
        if($mes_inicio == $mes_fin){
            //con mes_inicio y mes_fin obtenemos la cantidad de dias que forman a dichos meses
            return $this->generar_dias($dia_inicio, $dia_fin, $mes_inicio, $mes_fin, 'mm', $dias_seleccionados, NULL);
        }
        else{
            $diff=$mes_fin - $mes_inicio;
            if($diff >= 2){ //tenemos meses intermedios entre el periodo seleccionado
                return $this->generar_dias($dia_inicio, $dia_fin, $mes_inicio, $mes_fin, 'mnc', $dias_seleccionados, $this->obtener_meses_intermedios($mes_inicio, $diff));
            }
            else{ //en esta rama diff posee el valor 1, lo que implica que existen meses contiguos
                return $this->generar_dias($dia_inicio, $dia_fin, $mes_inicio, $mes_fin, 'mc', $dias_seleccionados, NULL);
            }
        }
        
    }
    
    /*
     * Esta funcion determina los meses intermedios entre un periodo. Se utiliza para representar a los meses
     * valores numericos de 1 a 12.
     */
    function obtener_meses_intermedios ($mes_inicio, $diff){
        $meses_intermedios=array();
        
        for($i=1; $i<=$diff; $i++){
            $mes_inicio += 1;
            $meses_intermedios[]=$mes_inicio;
        }
        
        return $meses_intermedios;
    }
    
    /*
     * La variable i puede contener :
     * mm = mismo mes. @meses_intermedios es NULL.
     * mc = mes contiguo. @meses_intermedios es NULL.
     * mnc = mes no contiguo.
     */
    function generar_dias ($dia_inicio, $dia_fin, $mes_inicio, $mes_fin, $i, $dias_seleccionados, $meses_intermedios){
        //guardamos los dias del periodo
        $dias=array();
        $anio=date('Y');
        switch($i){
            case 'mm' : while($dia_inicio <= $dia_fin){
                            $fecha=  strtotime("$dia_inicio-$mes_inicio-$anio");
                            if($this->es_dia_valido(date('N', $fecha), $dias_seleccionados)){
                                $dias[]=$fecha;
                            }
                            
                            $dia_inicio += 1;
                        }
                        
                        break;
                        
            case 'mc' : $this->obtener_dias($dia_inicio, $mes_inicio, $this->_meses[$mes_inicio], $dias_seleccionados, &$dias);
                        $this->obtener_dias($dia_fin, $mes_fin, $this->_meses[$mes_fin], $dias_seleccionados, &$dias);
                        
                        break;
            
            case 'mnc': //para los meses intermedios podemos obtener los dias sin problemas, avanzamos desde 1 
                        //hasta el ultimo dia del mes y realizamos el descarte adecuado.
                        foreach ($meses_intermedios as $clave=>$mes_i){
                            $this->obtener_dias($dia_inicio, $mes_i, $this->_meses[$mes_i], $dias_seleccionados, &$dias);
                        }
                        
                        //obtenemos los dias para dia_inicio y mes_inicio
                        $this->obtener_dias($dia_inicio, $mes_inicio, $this->_meses[$mes_inicio], $dias_seleccionados, &$dias);
                        
                        //obtenemos los dias para dia_fin y mes_fin
                        $this->obtener_dias($dia_inicio, $mes_inicio, $this->_meses[$mes_inicio], $dias_seleccionados, &$dias);
                        
                        break;
        }
        
        return $dias;
    }
    
    /*
     * @mes_inicial : contiene el numero de mes.
     * @mes : contiene la cantidad de dias de un mes.
     * 
     */
    function obtener_dias ($dia_inicial, $mes_inicial, $mes, $dias_seleccionados){
        $dias=array();
        $anio=date('Y');
        for($i=$dia_inicial; $i<=$mes; $i++){
            $fecha=  strtotime("$i-$mes_inicial-$anio");
            if($this->es_dia_valido($fecha, $dias_seleccionados)){
                $dias[]=$fecha;
            }
        }
        
        return $dias;
    }
    
    /*
     * @dia_inicio : contiene una representacion numerica de un dia de la semana, puede ser 1,....,7. Se obtiene
     * con date('N', fecha).
     */
    function es_dia_valido ($dia_inicio, $dias_seleccionados){
        $i=0;
        $n=count($dias_seleccionados);
        $fin=FALSE;
        while($i<$n && !$fin){
            //podemos obtener Lunes, Martes, .....
            $dia=$dias_seleccionados[$i];
            if(strcmp(utf8_decode($this->_dias[$dia_inicio]), $dia)==0){
                $fin=TRUE;
            }
            $i++;
        }
        return $fin;
    }
    
}

?>

