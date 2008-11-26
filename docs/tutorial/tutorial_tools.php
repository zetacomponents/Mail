<?php
require_once 'tutorial_autoload.php';

$mailAddresses = array(
                        new ezcMailAddress( 'john@example.com', 'Jøhn Doe', 'ISO-8859-1' ),
                        new ezcMailAddress( 'jane@example.com', 'Jane Doe' )
                      );
$addresses = '=?ISO-8859-1?B?SsO4aG4gRG9l?= <john@example.com>, Jane Doe <jane@example.com';

// Convert ezcMailAddress to string representation
var_dump( ezcMailTools::composeEmailAddress( $mailAddresses[0] ) );
var_dump( ezcMailTools::composeEmailAddresses( $mailAddresses ) );

// Convert string to ezcMailAddress
var_dump( ezcMailTools::parseEmailAddress( $addresses ) );
var_dump( ezcMailTools::parseEmailAddresses( $addresses ) );

// Validate an email address (with a regular expression, without checking for MX records)
$isValid = ezcMailTools::validateEmailAddress( 'john.doe@example.com' );

// Validate an email address with MX records check.

// MX record checking does not work on Windows due to the lack of getmxrr()
// and checkdnsrr() PHP functions. The ezcBaseFunctionalityNotSupportedException
// is thrown in this case.

// set this to your mail server, it is used in a
// 'HELO SMTP' command to validate against MX records
ezcMailTools::$mxValidateServer = 'your.mail.server';

// set this to a mail address such as 'postmaster@example.com', it is used in a
// 'MAIL FROM' SMTP command to validate against MX records
ezcMailTools::$mxValidateAddress = 'email.address@mail.server';

$isValid = ezcMailTools::validateEmailAddress( 'john.doe@example.com', true );

// Create a new mail object
$mail = new ezcMail();
$mail->from = $mailAddresses[1];
$mail->addTo( $mailAddresses[0] );
$mail->subject = "Top secret";

// Use the lineBreak() method
$mail->body = new ezcMailText( "Confidential" . ezcMailTools::lineBreak() . "DO NOT READ" );
$mail->generate();

// Create a reply message to the previous mail object
$reply = ezcMailTools::replyToMail( $mail, new ezcMailAddress( 'test@example.com', 'Reply Guy' ) );

?>
