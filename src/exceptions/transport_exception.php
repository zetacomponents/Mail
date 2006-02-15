<?php
/**
 * File containing the ezcMailTransportException class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Transport exceptions are thrown by implementors of the ezcMailTransport
 * interface when mail sending fails.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailTransportException extends ezcBaseException
{
    /**
     * Constructs an ezcMailTransportException with low level information $driverInfo.
     *
     * @param string $message
     * @param string $additionalInfo
     */
    public function __construct( $driverInfo = '' )
    {
        parent::__construct( 'The mail could not be sent. ' . $driverInfo , 0 );
    }
}
?>
