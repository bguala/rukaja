<?php
class form_ingreso_incidencia_ext extends toba_ei_formulario
{
    function extender_objeto_js() {
        echo "{$this->objeto_js}.evt__conductor__validar = function () {
            var estado=this.ef('conductor').get_estado();
            
            if(estado == 1){ //Si el check esta seleccionado.
                this.ef('cantidad_conductor').set_solo_lectura(false);
                this.ef('desc_conductor').set_solo_lectura(false);
            }else{
                this.ef('cantidad_conductor').set_solo_lectura(true);
                this.ef('cantidad_conductor').input().value='';
                this.ef('desc_conductor').set_solo_lectura(true);
                this.ef('desc_conductor').input().value='';
            }
            
            return true;
        } 
        
        {$this->objeto_js}.evt__interruptor__validar = function () {
            var estado=this.ef('interruptor').get_estado();
            
            if(estado == 1){
                this.ef('cantidad_interruptor').set_solo_lectura(false);
                this.ef('desc_interruptor').set_solo_lectura(false);
            }else{
                this.ef('cantidad_interruptor').set_solo_lectura(true);
                this.ef('cantidad_interruptor').input().value='';
                this.ef('desc_interruptor').set_solo_lectura(true);
                this.ef('desc_interruptor').input().value='';
            }
            
            return true;
        } 
        
        {$this->objeto_js}.evt__tubos__validar = function () {
            var estado=this.ef('tubos').get_estado();
            
            if(estado == 1){
                this.ef('cantidad_tubos').set_solo_lectura(false);
                this.ef('desc_tubos').set_solo_lectura(false);
            }else{
                this.ef('cantidad_tubos').set_solo_lectura(true);
                this.ef('cantidad_tubos').input().value='';
                this.ef('desc_tubos').set_solo_lectura(true);
                this.ef('desc_tubos').input().value='';
            }
            
            return true;
        } 
        
        {$this->objeto_js}.evt__tomacorriente__validar = function () {
            var estado=this.ef('tomacorriente').get_estado();
            
            if(estado == 1){
                this.ef('cantidad_tomacorriente').set_solo_lectura(false);
                this.ef('desc_tomacorriente').set_solo_lectura(false);
            }else{
                this.ef('cantidad_tomacorriente').set_solo_lectura(true);
                this.ef('cantidad_tomacorriente').input().value='';
                this.ef('desc_tomacorriente').set_solo_lectura(true);
                this.ef('desc_tomacorriente').input().value='';
            }
            
            return true;
        } 
        
        {$this->objeto_js}.evt__perdida_gas__validar = function () {
            var estado=this.ef('perdida_gas').get_estado();
            
            if(estado == 1){
                this.ef('desc_perdida_gas').set_solo_lectura(false);
            }else{
                this.ef('desc_perdida_gas').set_solo_lectura(true);
                this.ef('desc_perdida_gas').input().value='';
            }
            
            return true;
        } 
        
        {$this->objeto_js}.evt__sin_funcionamiento__validar = function () {
            var estado=this.ef('sin_funcionamiento').get_estado();
            
            if(estado == 1){
                this.ef('sin_funcionamiento').set_solo_lectura(false);
            }else{
                this.ef('sin_funcionamiento').set_solo_lectura(true);
                this.ef('sin_funcionamiento').input().value='';
            }
            
            return true;
        }";
    }
}
?>