<?php
require_once 'tutorial_autoload.php';

// Create a new IMAP transport object by specifying the server name
$imap = new ezcMailImapTransport( "imap.example.com" );

// Authenticate to the IMAP server
$imap->authenticate( "user", "password" );

// Select the Inbox mailbox
$imap->selectMailbox( 'Inbox' );

// List the capabilities of the IMAP server
    $capabilities = $imap->capability();

// List existing mailboxes
    $mailboxes = $imap->listMailboxes( "", "*" );

// Fetch the hierarchy delimiter character (usually "/")
    $delimiter = $imap->getHierarchyDelimiter();

// Create a new mailbox
    $imap->createMailbox( "Reports 2006" );

// Delete a mailbox
    $imap->deleteMailbox( "Reports 2005" );

// Rename a mailbox
    $imap->renameMailbox( "Reports 2006", "Reports" );

// Copy messages from the selected mailbox (here: Inbox) to another mailbox
    $imap->copyMessages( "1,2,4", "Reports" );

// Set a flag to messages
// See the function description for a list of supported flags
    $imap->setFlag( "1,2,4", "DELETED" );

// Clears a flag from messages
// See the function description for a list of supported flags
    $imap->clearFlag( "1,2,4", "SEEN" );

// Append a message to a mailbox. $mail must contain the mail as text
// Use this with a "Sent" or "Drafts" mailbox
    $imap->append( "Sent", $mail );

?>
