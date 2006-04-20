<?php
require_once 'tutorial_autoload.php';

$mail = new ezcMail();
$mail->from = new ezcMailAddress( 'sender@example.com', 'Bernard Bernoulli' );
$mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Wendy' ) );
$mail->subject = "This is the subject of the example mail";
$textPart = new ezcMailText( "This is the body of the example mail." );
$fileAttachment = new ezcMailFile( "~/myfile.jpg" );
$mail->body = new ezcMailMultipartMixed( $textPart, $fileAttachment );

$transport = new ezcMailMtaTransport();
$transport->send( $mail );

?>
