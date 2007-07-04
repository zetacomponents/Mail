<?php
require_once 'tutorial_autoload.php';

// Create a new IMAP transport object by specifying the server name
$imap = new ezcMailImapTransport( "imap.example.com" );

// Authenticate to the IMAP server
$imap->authenticate( "user", "password" );

// Select the Inbox mailbox
$imap->selectMailbox( 'Inbox' );

// Get the number of messages on the server, combined size, number of recent
// messages and number of unseen messages
// in the variables $num, $size, $recent, $unseen
    $imap->status( $num, $size, $recent, $unseen );

// Get the list of message numbers on the server and their sizes
// the returned array is something like: array( 1 => 1500, 2 => 45200 )
// where the key is a message number and the value is the message size
    $messages = $imap->listMessages();

// Get the list of message unique ids on the server and their sizes
// the returned array is something like: array( 1 => '15', 2 => '16' )
// where the key is an message number and the value is the message unique id
    $messages = $imap->listUniqueIdentifiers();

// Usually you will call one of these fetch functions:

    // Fetch all messages on the server
    $set = $imap->fetchAll();

    // Fetch one message from the server (here: get the message no. 2)
    $set = $imap->fetchByMessageNr( 2 );

    // Fetch a range of messages from the server (here: get 4 messages starting from message no. 2)
    $set = $imap->fetchFromOffset( 2, 4 );

    // Fetch messages which have a certain flag
    // See the function description for a list of supported flags
    $set = $imap->fetchByFlag( "DELETED" );

    // Fetch a range of messages sorted by Date
    // Use this to page through a mailbox
    // See the function description for a list of criterias and for how to sort ascending or descending
    $set = $imap->sortFromOffset( 1, 10, "Date" );

    // Sort the specified messages by Date
    // See the function description for a list of criterias and for how to sort ascending or descending
    $set = $imap->sortMessages( "1,2,3,4,5", "Date" );

    // Fetch messages which match the specified criteria.
    // See the section 6.4.4. of RFC 1730 or 2060 for a list of criterias
    // (http://www.faqs.org/rfcs/rfc1730.html)
    // The following example returns the messages flagged as SEEN and with
    // 'release' in their Subject
    $set = $imap->searchMailbox( 'SEEN SUBJECT "release"' );

// Delete a message from the server (message is not physically deleted, but it's
// list of flags get the "Deleted" flag.
    $imap->delete( 1 );

// Use this to permanently delete the messages flagged with "Deleted"
    $imap->expunge();

// Use this to keep the connection alive
    $imap->noop();

// Create a new mail parser object
$parser = new ezcMailParser();

// Parse the set of messages retrieved from the server earlier
$mail = $parser->parseMail( $set );

?>
