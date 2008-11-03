<?php
require 'tutorial_autoload.php';

// Create a mail set from a file, a parser, and parse the e-mail.
$set = new ezcMailFileSet( array( 'test-mail.mail' ) );
$parser = new ezcMailParser();
$mail = $parser->parseMail( $set );

/**
 * This class is used in the callback for each part that is walked below. It
 * takes care of copying the files and registering both file parts and the HTML
 * text.
 */
class collector
{
    function saveMailPart( $context, $mailPart )
    {
        // if it's a file, we copy the attachment to a new location, and
        // register its CID with the class - attaching it to the location in
        // which the *web server* can find the file.
        if ( $mailPart instanceof ezcMailFile )
        {
            // copy files to tmp with random name
            $newFile = tempnam( $this->dir, 'mail' );
            copy( $mailPart->fileName, $newFile );

            // save location and setup ID array
            $this->cids[$mailPart->contentId] =
                $this->webDir . '/' . basename( $newFile );
        }

        // if we find a text part and if the sub-type is HTML (no plain text)
        // we store that in the classes' htmlText property.
        if ( $mailPart instanceof ezcMailText )
        {
            if ( $mailPart->subType == 'html' )
            {
                $this->htmlText = $mailPart->text;
            }
        }
    }
}

// create the collector class and set the filesystem path, and the webserver's
// path to find the attached files (images) in.
$collector = new collector();
$collector->dir = "/home/httpd/html/test/ezc";
$collector->webDir = '/test/ezc';

// We use the saveMailPart() method of the $collector object function as a
// callback in walkParts().
$context = new ezcMailPartWalkContext( array( $collector, 'saveMailPart' ) );

// only call the callback for file and text parts.
$context->filter = array( 'ezcMailFile', 'ezcMailText' );

// use walkParts() to iterate over all parts in the first parsed e-mail
// message.
$mail[0]->walkParts( $context, $mail[0] );

// display the html text with the content IDs replaced with references to the
// file in the webroot.
echo ezcMailTools::replaceContentIdRefs( $collector->htmlText, $collector->cids );
?>
