<?php
require_once 'tutorial_autoload.php';

// Create a new mail composer object
$mail = new ezcMailComposer();

// Specify the "from" mail address
$mail->from = new ezcMailAddress( 'sender@example.com', 'Adrian Ripburger' );

// Add one "to" mail address (multiple can be added)
$mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Maureen Corley' ) );

// Specify the subject of the mail
$mail->subject = "This is the subject of the example mail";

// Specify the body text of the mail
$mail->plainText = "This is the body of the example mail.";

// Generate the mail
$mail->build();

// Create a new MTA transport object
$transport = new ezcMailMtaTransport();

// Use the MTA transport to send the created mail object
$transport->send( $mail );

?>
