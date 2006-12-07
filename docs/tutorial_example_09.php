<?php
require_once 'tutorial_autoload.php';

// Create a new POP3 transport object by specifying the server name
$pop3 = new ezcMailPop3Transport( "pop3.example.com" );

// Authenticate to the POP3 server
$pop3->authenticate( "user", "password" );

// Get the number of messages on the server and their combined size
// in the variables $num and $size
    $pop3->status( $num, $size );

// Get the list of message numbers on the server and their sizes
// the returned array is something like: array( 1 => 1500, 2 => 45200 )
// where the key is a message number and the value is the message size
    $messages = $pop3->listMessages();

// Get the list of message unique ids on the server and their sizes
// the returned array is something like: array( 1 => '00000f543d324', 2 => '000010543d324' )
// where the key is an message number and the value is the message unique id
    $messages = $pop3->listUniqueIdentifiers();

// Usually you will call one of these 3 fetch functions:

    // Fetch all messages on the server
    $set = $pop3->fetchAll();

    // Fetch one message from the server (here: get the message no. 2)
    $set = $pop3->fetchByMessageNr( 2 );

    // Fetch a range of messages from the server (here: get 4 messages starting from message no. 2)
    $set = $pop3->fetchFromOffset( 2, 4 );

// Delete a message from the server
    $pop3->delete( 1 );

// Use this to keep the connection alive
    $pop3->noop();

// Create a new mail parser object
$parser = new ezcMailParser();

// Parse the set of messages retrieved from the server earlier
$mail = $parser->parseMail( $set );

?>
