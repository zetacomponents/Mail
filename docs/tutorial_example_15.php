<?php
require_once 'tutorial_autoload.php';

// Create a new POP3 transport with a plain connection (default port is 110,
// you can specify a different one using the second parameter of the constructor).
// A timeout option is specified to be 10 seconds (default is 5).
// Another option to be specified is the authenticationMethod as APOP (default is plain text)
$pop3 = new ezcMailPop3Transport( "imap.example.com", null,
            array( 'timeout' => 10,
                   'authenticationMethod' => ezcMailPop3Transport::AUTH_APOP
                 ) );

// Authenticate to the POP3 server
$pop3->authenticate( "user", "password" );

?>
