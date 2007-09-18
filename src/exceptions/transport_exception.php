<?php
/**
 * File containing the ezcMailTransportException class
 *
 * @package Mail
 * @version 1.4alpha1
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Transport exceptions are thrown when either sending or receiving
 * mail transports fail to do their job properly.
 *
 * @package Mail
 * @version 1.4alpha1
 */
class ezcMailTransportException extends ezcMailException
{
    /**
     * Constructs an ezcMailTransportException with low level information $message.
     *
     * @param string $message
     */
    public function __construct( $message = '' )
    {
        parent::__construct( "An error occured while sending or receiving mail. " . $message );
    }
}
?>
