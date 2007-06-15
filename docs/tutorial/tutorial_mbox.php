<?php
require_once 'tutorial_autoload.php';

// Create a new Mbox transport object by specifiying an existing mbox file name
$mbox = new ezcMailMboxTransport( "/path/file.mbox" );

// Fetch all messages from the mbox file
$set = $mbox->fetchAll();

// Create a new mail parser object
$parser = new ezcMailParser();

// Parse the set of messages retrieved from the mbox file earlier
$mail = $parser->parseMail( $set );

?>
