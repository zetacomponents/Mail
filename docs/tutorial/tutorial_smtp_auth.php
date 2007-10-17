<?php
require_once 'tutorial_autoload.php';

// Create an SMTP transport and demand NTLM authentication.
// Username and password must be specified, otherwise no authentication
// will be attempted.
// If NTLM authentication fails, an exception will be thrown.
// See the ezcMailSmtpTransport class for a list of supported methods.
$options = new ezcMailSmtpTransportOptions();
$options->preferredAuthMethod = ezcMailSmtpTransport::AUTH_NTLM;

$transport = new ezcMailSmtpTransport( 'mailhost.example.com', 'username', 'password', null, $options );

// The option can also be specified via the option property:
$transport->options->preferredAuthMethod = ezcMailSmtpTransport::AUTH_NTLM;

// Use the SMTP transport to send the created mail object
$transport->send( $mail );

?>
