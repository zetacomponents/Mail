<?php
include( "tutorial_autoload.php" );

$mail = new ezcMail();
$mail->from = new ezcMailAddress( 'sender@example.com', 'Largo LaGrande' );
$mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Wally B. Feed' ) );
$mail->subject = "This is the subject of the mail with a mail digest.";
$textPart = new ezcMailText( "This is the body of the mail with a mail digest." );

$mail->body = new ezcMailMultipartMixed( $textPart, new RFC822Digest( $digest ) );

$transport = new ezcMailTransportMta();
$transport->send( $mail );

?>
