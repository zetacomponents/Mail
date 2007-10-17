<?php
require_once 'tutorial_autoload.php';

// Create a new IMAP transport object by specifying the server name, default port
// and that the IMAP commands will work with unique IDs instead of message numbers
$options = new ezcMailImapTransportOptions();
$options->uidReferencing = true;

$imap = new ezcMailImapTransport( "imap.example.com", null, $options );

// Authenticate to the IMAP server
$imap->authenticate( "user", "password" );

// Select the Inbox mailbox
$imap->selectMailbox( 'Inbox' );

// The other IMAP examples apply here, with the distinction that unique IDs are
// used to refer to messages instead of message numbers

?>
