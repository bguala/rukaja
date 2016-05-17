<?php

require_once('3ros/phpmailer/class.phpmailer.php');
require_once('3ros/phpmailer/class.smtp.php');

/* 
 * Esta clase es un Wraper de PHPMailer.
 * Permite enviar emails a traves de la libreria PHPMailer.
 */
class Email {
    
    public function __construct() {
        
    }
    
    public function enviar_email ($destinatario, $asunto, $descripcion, $adjunto=null){
        //El objeto PHPMiler se utiliza para enviar correo electronico desde PHP
        $email=new PHPMailer();
        $email->IsSMTP();
        $email->SMTPAuth='true';
        $email->SMTPSecure='ssl';
        $email->Host='smtp.gmail.com';
        $email->Port=465;
        //Estos son datos asociados a la cuenta del emisor de correo
        $email->Username='sed.uncoma@gmail.com';
        $email->Password='n1s.toba15';
        $email->Timeout=100;
        //Aqui se debe especificar el correo electronico del emisor
        $email->SetFrom('sed.uncoma@gmail.com');

            try{
                //En esta seccion se especifica el destinatario, el asunto y la descripcion
                $email->AddAddress('sed.uncoma@gmail.com');
                //$email->AddAddress($destinatario);
                
                $email->Subject=$asunto;
                
                $email->Body=$descripcion;
                
                if(isset($adjunto)){
                    //$datos['dajunto']['tmp_name'], $datos['adjunto']['name']
                    $email->AddAttachment($adjunto['adjunto']['tmp_name'], $adjunto['adjunto']['name']);
                }
                
                //Send se utiliza para enviar enviar el correo electronico, su resultado es booleano
                return ($email->Send());
                
            }

            catch(phpmailerException $e){
                print_r($e);
            }
    }
        
}

?>

