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
// The 5th parameter is the $options object which specifies a SSLV3 connection
// (default is ezcMailSmtpTransport::CONNECTION_PLAIN).
$options = new ezcMailSmtpTransportOptions();
$options->connectionType = ezcMailSmtpTransport::CONNECTION_SSLV3;

$transport = new ezcMailSmtpTransport( 'mailhost.example.com', '', '', null, $options );

// The option can also be specified via the option property:
$transport->options->connectionType = ezcMailSmtpTransport::CONNECTION_SSLV3;

// Use the SMTP transport to send the created mail object
$transport->send( $mail );

?>
