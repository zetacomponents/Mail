<?php
require_once 'tutorial_autoload.php';

// Create a new mail object
$mail = new ezcMail();

// Specify the "from" mail address
$mail->from = new ezcMailAddress( 'sender@example.com', 'Bernard Bernoulli' );

// Add one "to" mail address (multiple can be added)
$mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Wendy' ) );

// Specify the subject of the mail
$mail->subject = "This is the subject of the example mail";

// Create a text part to be added to the mail
$textPart = new ezcMailText( "This is the body of the example mail." );

// Create a file attachment to be added to the mail
$fileAttachment = new ezcMailFile( "~/myfile.jpg" );

// Specify the body of the mail as a multipart-mixed of the text part and the file attachment
$mail->body = new ezcMailMultipartMixed( $textPart, $fileAttachment );

// Create a new MTA transport object
$transport = new ezcMailMtaTransport();

// Use the MTA transport to send the created mail object
$transport->send( $mail );

?>
