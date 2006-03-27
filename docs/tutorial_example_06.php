<?php
include( "tutorial_autoload.php" );

$mail = new ezcMail();
$mail->from = new ezcMailAddress( 'sender@example.com', 'Norwegian characters: æøå', 'iso-8859-1' );
$mail->addTo( new ezcMailAddress( 'reciever@example.com', 'More norwegian characters: æøå', 'iso-8859-1' ) );
$mail->subject = 'Oslo ligger sør i Norge og har vært landets hovedstad i over 600 år.';
$mail->subjectCharset = 'iso-8859-1';

$mail->body = new ezcMailText( 'Oslo be grunnlagt rundt 1048 av Harald Hardråde.', 'iso-8859-1' );

$transport = new ezcMailMtaTransport();
$transport->send( $mail );

?>
