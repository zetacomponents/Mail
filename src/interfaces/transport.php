<?php
/**
 * File containing the ezcMailTransport class
 *
 * @package Mail
 * @version 1.5.1
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Interface for classes that implement a mail transport.
 *
 * Subclasses must implement the send() method.
 *
 * @package Mail
 * @version 1.5.1
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
