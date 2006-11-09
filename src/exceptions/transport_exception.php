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
 * Transport exceptions are thrown when either sending or receiving
 * mail transports fail to do their job properly.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailTransportException extends ezcBaseException
{
    /**
     * Constructs an ezcMailTransportException with low level information $driverInfo.
     *
     * @param string $driverInfo
     */
    public function __construct( $driverInfo = '' )
    {
        parent::__construct( 'An error occured while sending or receiving mail. ' . $driverInfo , 0 );
    }
}
?>
