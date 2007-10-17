<?php
require_once 'tutorial_autoload.php';

// Create a new IMAP transport with an SSL connection (default port is 993,
// you can specify a different one using the second parameter of the constructor).
$options = new ezcMailImapTransportOptions();
$options->ssl = true;

$imap = new ezcMailImapTransport( "imap.example.com", null, $options );

// Authenticate to the IMAP server
$imap->authenticate( "user", "password" );

// Select the Inbox mailbox
$imap->selectMailbox( 'Inbox' );

?>
