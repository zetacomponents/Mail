<?php
/**
 * File containing the ezcMailTransport class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Interface for classes that implement a mail transport.
 *
 * Subclasses must implement the send() method.
 *
 * @package Mail
 * @version //autogen//
 */
interface ezcMailTransport
{
    /**
     * Sends the contents of $mail.
     *
     * @param ezcMail $mail
     */
    public function send( ezcMail $mail );
}
?>
