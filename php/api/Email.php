<?php

require_once('3ros/phpmailer/class.phpmailer.php');
require_once('3ros/phpmailer/class.smtp.php');

/* 
 * Esta clase es un Wraper de PHPMailer.
 * Permite enviar emails a traves de la libreria PHPMailer.
 */
class Email {
    
    protected $s__email;
    
    /*
     * Creamos una instancia de un objeto phpmailer y lo configuramos para que pueda enviar emails.
     */
    public function __construct() {
        
        //El objeto PHPMiler se utiliza para enviar correo electronico desde PHP
        $this->s__email=new PHPMailer();
        $this->s__email->IsSMTP();
        $this->s__email->SMTPAuth='true';
        $this->s__email->SMTPSecure='ssl';
        $this->s__email->Host='smtp.gmail.com';
        $this->s__email->Port=465;
        //Estos son datos asociados a la cuenta del emisor de correo
        $this->s__email->Username='rukaja.uncoma@gmail.com'; //n1s.toba15
        $this->s__email->Password='rukaja2016';
        $this->s__email->Timeout=100;
        //Aqui se debe especificar el correo electronico del emisor
        $this->s__email->SetFrom('rukaja.uncoma@gmail.com');
        
    }
    
    public function enviar_email ($destinatario, $asunto, $descripcion, $adjunto=null){
        
        try{
            //En esta seccion se especifica el destinatario, el asunto y la descripcion
            $this->s__email->AddAddress('rukaja.uncoma@gmail.com');
            //$email->AddAddress($destinatario);

            $this->s__email->Subject=$asunto;

            $this->s__email->Body=$descripcion;

            if(isset($adjunto)){
                //$datos['dajunto']['tmp_name'], $datos['adjunto']['name']
                $this->s__email->AddAttachment($adjunto['adjunto']['tmp_name'], $adjunto['adjunto']['name']);
            }

            //Send se utiliza para enviar  el correo electronico, su resultado es booleano
            return ($this->s__email->Send());

        }

        catch(phpmailerException $e){
            print_r($e);
        }
            
    }
        
}

?>

