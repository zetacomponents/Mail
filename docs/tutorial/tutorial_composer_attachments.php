<?php
require_once 'tutorial_autoload.php';

// Create a new mail composer object
$mail = new ezcMailComposer();

// Specify the "from" mail address
$mail->from = new ezcMailAddress( 'john@example.com', 'John Doe' );

// Add one "to" mail address (multiple can be added)
$mail->addTo( new ezcMailAddress( 'cindy@example.com', 'Cindy Doe' ) );

// Specify the subject of the mail
$mail->subject = "Example of an HTML email with attachments";

// Specify the plain text of the mail
$mail->plainText = "Here is the text version of the mail. This is displayed if the client can not understand HTML";

// Specify the html text of the mail
$mail->htmlText = "<html>Here is the HTML version of your mail with an image: <img src='file://path_to_image.jpg' /></html>";

// Add an attachment to the mail
$mail->addAttachment( 'path_to_attachment.file' );

// Build the mail object
$mail->build();

// Create a new MTA transport object
$transport = new ezcMailMtaTransport();

// Use the MTA transport to send the created mail object
$transport->send( $mail ); 

?>
