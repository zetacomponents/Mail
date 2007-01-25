<?php
/**
 * File containing the ezcMailSmtpTransportOptions class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Class containing the options for SMTP transport.
 *
 * The options from ezcMailTransportOptions are inherited.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailSmtpTransportOptions extends ezcMailTransportOptions
{
    /**
     * Constructs an object with the specified values.
     *
     * @param array(string=>mixed) $options
     */
    public function __construct( array $options = array() )
    {
        parent::__construct( $options );
    }

    /**
     * Sets the option $name to $value.
     *
     * @throws ezcBaseValueException
     *         if $value is not correct for the property $name
     * @throws ezcBasePropertyNotFoundException
     *         if the propery $name is not defined
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            default:
                parent::__set( $name, $value );
        }
    }
}
?>
