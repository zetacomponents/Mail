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
 * @package Mail
 * @version //autogen//
 */
class ezcMailTransportMta implements ezcMailTransport
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
?>
