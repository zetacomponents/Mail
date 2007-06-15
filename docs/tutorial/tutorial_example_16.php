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

// Create a new SMTP transport object with an SSLv3 connection.
// The port will be 465 by default, use the 4th argument to change it.
// Username and password (2nd and 3rd arguments) are left blank, which means
// the mail host does not need authentication.
// Omit the 5th parameter if you want to use a plain connection
// (or set connectionType to ezcMailSmtpTransport::CONNECTION_PLAIN).
$transport = new ezcMailSmtpTransport( 'mailhost.example.com', '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV3 ) );

// Use the SMTP transport to send the created mail object
$transport->send( $mail );

?>
