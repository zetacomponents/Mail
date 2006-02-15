<?php
/**
 * Autoloader definition for the Mail component.
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
return array(
    'ezcMailAddress'                => 'Mail/structs/mail_address.php',
    'ezcMailPart'                   => 'Mail/interfaces/part.php',
    'ezcMailTransport'              => 'Mail/interfaces/transport.php',
    'ezcMail'                       => 'Mail/mail.php',
    'ezcMailComposer'               => 'Mail/composer.php',
    'ezcMailFile'                   => 'Mail/parts/file.php',
    'ezcMailText'                   => 'Mail/parts/text.php',
    'ezcMailMultipart'              => 'Mail/parts/multipart.php',
    'ezcMailMultipartAlternative'   => 'Mail/parts/multiparts/multipart_alternative.php',
    'ezcMailMultipartMixed'         => 'Mail/parts/multiparts/multipart_mixed.php',
    'ezcMailMultipartRelated'       => 'Mail/parts/multiparts/multipart_related.php',
    'ezcMailTransportMta'           => 'Mail/transports/transport_mta.php',
    'ezcMailTransportSmtp'          => 'Mail/transports/transport_smtp.php',
    'ezcMailException'              => 'Mail/exceptions/mail_exception.php',
    'ezcMailTransportException'     => 'Mail/exceptions/transport_exception.php',
    'ezcMailTransportSmtpException' => 'Mail/exceptions/transport_smtp_exception.php',
    'ezcMailTools'                  => 'Mail/tools.php'
);
?>
