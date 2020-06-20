<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;


require './mailer/vendor/autoload.php';

// Recordar que, si se cambia en algún momento la pass... El contacto dejará de funcionar. OJO.
$senderMail = 'forba369@gmail.com';
$senderName = 'Forba.IO';
$senderPass = 'Sebas123!';


$to = "forba369@gmail.com";
$subject = "Contacto Web - " . $_REQUEST['topic'];
$message = 
'<b>Compañía:</b> ' . $_REQUEST['company'] . '<br/>' .
'<b>Nombre de contacto:</b> ' . $_REQUEST['name'] . '<br/>' .
'<b>Telefono de contacto:</b> ' . $_REQUEST['phone'] . '<br/>' .
'<b>Correo de contacto:</b> ' . $_REQUEST['email'] . '<br/>' .
'<b>Tópico/Asunto:</b> ' . $_REQUEST['topic'] . '<br/>' .
'<b>Mensaje:</b><br/><br/>' . nl2br($_REQUEST['message']) . '<br/><br/>';


$mail = new PHPMailer();
$mail->IsSMTP();
$mail->Mailer = "smtp";
$mail->SMTPDebug  = 0;  
$mail->SMTPAuth   = TRUE;
$mail->SMTPSecure = "tls";
$mail->Port       = 587;
$mail->Host       = "smtp.gmail.com";
$mail->Username = $senderMail;
$mail->Password = $senderPass;

$mail->IsHTML(true);
$mail->AddAddress($to);
$mail->SetFrom($senderMail, $senderName);
$mail->Subject = $subject;
$mail->MsgHTML($message);


$status = !$mail->send() ? 'error' : 'success';


header("Location: https://forba.io?contact_sended_status={$status}");
?>