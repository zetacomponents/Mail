<?php
require_once 'tutorial_autoload.php';

$mail = new ezcMailComposer();
$mail->from = new ezcMailAddress( 'sender@example.com', 'Adrian Ripburger' );
$mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Maureen Corley' ) );
$mail->subject = "This is the subject of the example mail";
$mail->plainText = "This is the body of the example mail.";

$mail->build();

$transport = new ezcMailMtaTransport();
$transport->send( $mail );

?>
