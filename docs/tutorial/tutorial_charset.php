<?php
require_once 'tutorial_autoload.php';

// Create a new mail object
$mail = new ezcMail();

// Specify the "from" mail address
$mail->from = new ezcMailAddress( 'sender@example.com', 'Norwegian characters: æøå', 'iso-8859-1' );

// Add one "to" mail address (multiple can be added)
$mail->addTo( new ezcMailAddress( 'reciever@example.com', 'More norwegian characters: æøå', 'iso-8859-1' ) );

// Specify the subject of the mail
$mail->subject = 'Oslo ligger sør i Norge og har vært landets hovedstad i over 600 år.';

// Specify the charset of the subject
$mail->subjectCharset = 'iso-8859-1';

// Add a header with the default charset (us-ascii)
$mail->setHeader( 'X-Related-City', 'Moscow' );

// Add a header with a custom charset (iso-8859-5)
$mail->setHeader( 'X-Related-Movie', 'James Bond - Å leve og la dø', 'iso-8859-1' );

// Specify the body as a text part, also specifying it's charset
$mail->body = new ezcMailText( 'Oslo be grunnlagt rundt 1048 av Harald Hardråde.', 'iso-8859-1' );

// Create a new MTA transport object
$transport = new ezcMailMtaTransport();

// Use the MTA transport to send the created mail object
$transport->send( $mail );

?>
