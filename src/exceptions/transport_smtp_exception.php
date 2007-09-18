<?php
/**
 * File containing the ezcMailTransportSmtpException class
 *
 * @package Mail
 * @version 1.4alpha1
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * ezcMailTransportSmtpException is thrown when an exceptional state
 * occures internally in the ezcMailSmtpTransport class. As it never enters
 * "userspace" the class is marked as private.
 *
 * @package Mail
 * @version 1.4alpha1
 * @access private
 */
class ezcMailTransportSmtpException extends ezcMailException
{
    /**
     * Constructs an ezcMailTransportSmtpException with the highlevel error
     * message $message.
     *
     * @param string $message
     */
    public function __construct( $message )
    {
        parent::__construct( $message );
    }
}
?>
