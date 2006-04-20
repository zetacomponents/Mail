<?php
require_once 'tutorial_autoload.php';

$mail = new ezcMail();
$mail->from = new ezcMailAddress( 'sender@example.com', 'Boston Low' );
$mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Maggie Robbins' ) );
$mail->subject = "This is the subject of the example mail";
$mail->body = new ezcMailText( "This is the body of the example mail." );

$transport = new ezcMailMtaTransport();
$transport->send( $mail );

?>
