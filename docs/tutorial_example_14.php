<?php
require_once 'tutorial_autoload.php';

// Create a new IMAP transport with an SSL connection (default port is 993,
// you can specify a different one using the second parameter of the constructor).
$imap = new ezcMailImapTransport( "imap.example.com", null,
            array( 'ssl' => true ) );

// Authenticate to the IMAP server
$imap->authenticate( "user", "password" );

// Select the Inbox mailbox
$imap->selectMailbox( 'Inbox' );

?>
