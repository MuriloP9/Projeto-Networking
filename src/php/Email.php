<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email'])) {
    header("Location: ../php/index.php");
    exit();
}

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


include("../php/PHPMailer-master/src/PHPMailer.php"); 
include("../php/PHPMailer-master/src/SMTP.php"); 
include("../php/PHPMailer-master/src/Exception.php"); 

//Create an instance; passing `true` enables exceptions

        

class Email {

    function enviarEmail($userEmail, $assuntoEmail, $corpoEmail, $corpoAltEmail) {
        
        $mail = new PHPMailer(true);
        
        try {
            //Server settings
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'prolink.web.contact@gmail.com';                     //SMTP username
            $mail->Password   =  'qcvt muka nwxb omgp';                         //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption
            $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
        
            //Recipients
            $mail->setFrom('prolink.web.contact@gmail.com', 'Prolink');
            $mail->addAddress($userEmail);     //Add a recipient
            $mail->addReplyTo('prolink.web.contact@gmail.com', 'Prolink');
            //$mail->addCC('cc@example.com');
            //$mail->addBCC('bcc@example.com');
        
            
        
            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Subject = "$assuntoEmail";
            $mail->Body    =  <<<END
                $corpoEmail
            END;
            $mail->AltBody = <<<END
                $corpoAltEmail
            END;
        
            $mail->send();
            //echo 'Message has been sent';
        } catch (Exception $e) {
            //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }


}

?>