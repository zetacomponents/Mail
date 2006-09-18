<?php
/**
 * File containing the ezcMailTransportMta class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Implementation of the mail transport interface using the system MTA.
 *
 * The system MTA translates to sendmail on most Linux distributions.
 *
 * Qmail insists it should only have "\n" linebreaks and will send
 * garbled messages with the default "\r\n" setting.
 * Use ezcMailTools::setLineBreak( "\n" ) before sending mail to fix this issue.
 *
 * @package Mail
 * @version //autogen//
 * @mainclass
 */
class ezcMailMtaTransport implements ezcMailTransport
{
    /**
     * Constructs a new ezcMailTransportMta.
     */
    public function __construct(  )
    {
    }

    /**
     * Sends the mail $mail using the PHP mail method.
     *
     * Note that a message may not arrive at the destination even though
     * it was accepted for delivery.
     *
     * @throws ezcMailException if the mail was not accepted for delivery
     *         by the MTA.
     * @param ezcMail $mail
     * @return void
     */
    public function send( ezcMail $mail )
    {
        $mail->appendExcludeHeaders( array( 'to', 'subject' ) );
        $headers = rtrim( $mail->generateHeaders() ); // rtrim removes the linebreak at the end, mail doesn't want it.
        $success = mail( ezcMailTools::composeEmailAddresses( $mail->to ),
                         $mail->getHeader( 'Subject' ), $mail->generateBody(), $headers );
        if ( $success === false )
        {
            throw new ezcMailTransportException();
        }
    }
}

/**
 * This class is deprecated. Use ezcMailMtaTransport instead.
 * @package Mail
 */
class ezcMailTransportMta extends ezcMailMtaTransport
{
}
?>
