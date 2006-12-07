<?php
require_once 'tutorial_autoload.php';

// Create a new mail object
$mail = new ezcMail();

// Specify the "from" mail address
$mail->from = new ezcMailAddress( 'sender@example.com', 'Boston Low' );

// Add one "to" mail address (multiple can be added)
$mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Maggie Robbins' ) );

// Specify the subject of the mail
$mail->subject = "This is the subject of the example mail";

// Specify the body text of the mail as a ezcMailText object
$mail->body = new ezcMailText( "This is the body of the example mail." );

// Create a new MTA transport object
$transport = new ezcMailMtaTransport();

// Use the MTA transport to send the created mail object
$transport->send( $mail );

?>
