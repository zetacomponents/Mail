<?php
require_once 'tutorial_autoload.php';

$set = ezcMailFileSet( "path_to_the_mail_file" );
$parser = new ezcMailParser();
$mail = $parser->parseMail( $set );

// The mail is a simple mail with a text and a file attachment.
// Hence the body is a ezcMailMultipartMixed object.
$parts = $mail->body->getParts();

// the first part is the text message, the second is the file attachment
$file = $parts[1];

// lets move the attachment
$newPlacement = '/path/to/my/files/' . basename( $file->fileName );
rename( $file->fileName, $newPlacement );

// if you still want to use the $file object remember to tell it that we changed the name
$file->fileName = $newPlacement;
?>
