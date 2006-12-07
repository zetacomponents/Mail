<?php
require_once 'tutorial_autoload.php';

// Create a new mail object
$mail = new ezcMail();

// Specify the "from" mail address
$mail->from = new ezcMailAddress( 'sender@example.com', 'Largo LaGrande' );

// Add one "to" mail address (multiple can be added)
$mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Wally B. Feed' ) );

// Specify the subject of the mail
$mail->subject = "This is the subject of the mail with a mail digest.";

// Create a text part to be added to the mail
$textPart = new ezcMailText( "This is the body of the mail with a mail digest." );

// Specify the body of the mail as a multipart-mixed of the text part and a RFC822 digest object
// where $digest is an ezcMail object
// and RFC822Digest is the class from the previous example
$mail->body = new ezcMailMultipartMixed( $textPart, new RFC822Digest( $digest ) );

// Create a new MTA transport object
$transport = new ezcMailMtaTransport();

// Use the MTA transport to send the created mail object
$transport->send( $mail );

?>
