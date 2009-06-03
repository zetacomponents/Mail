<?php
require_once 'tutorial_autoload.php';

// Create a new POP3 transport with a plain connection (default port is 110,
// you can specify a different one using the second parameter of the constructor).
// A timeout option is specified to be 10 seconds (default is 5).
// Another option to be specified is the authenticationMethod as APOP (default is plain text)
$options = new ezcMailPop3TransportOptions();
$options->timeout = 10;
$options->authenticationMethod = ezcMailPop3Transport::AUTH_APOP;

$pop3 = new ezcMailPop3Transport( "pop3.example.com", null, $options );

// Authenticate to the POP3 server
$pop3->authenticate( "user", "password" );

?>
