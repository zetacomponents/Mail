<?php
require_once 'tutorial_autoload.php';

// Create a new mail parser object
$parser = new ezcMailParser();

// $set is a message set got from an IMAP, POP3 account or Mbox file
// like for example:
// $mbox = new ezcMailMboxTransport( "/path/file.mbox" );
// $set = $mbox->fetchAll();
$mail = $parser->parseMail( $set );

for ( $i = 0; $i < count( $mail ); $i++ )
{
    // Process $mail[$i] such as use $mail[$i]->subject, $mail[$i]->body
    echo "From: {$mail[$i]->from}, Subject: {$mail[$i]->subject}\n";

    // Save the attachments to another folder
    $parts = $mail[$i]->fetchParts();
    foreach ( $parts as $part )
    {
        if ( $part instanceof ezcMailFile )
        {
            rename( $part->fileName, '/path/to/save/to/' . basename( $part->displayFileName ) );
        }
    }
}

?>
