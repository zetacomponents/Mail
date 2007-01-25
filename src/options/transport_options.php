<?php
/**
 * File containing the ezcMailTransportOption class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Class containing the basic options for mail transports.
 *
 * @property int $timeout
 *           Specifies the time in seconds until the connection is closed if
 *           there is no activity through the connection.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailTransportOptions extends ezcBaseOptions
{
    /**
     * Constructs an object with the specified values.
     *
     * @param array(string=>mixed) $options
     */
    public function __construct( array $options = array() )
    {
        $this->timeout = 5; // default value for timeout is 5 seconds

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
            case 'timeout':
                if ( !is_numeric( $value ) || ( $value < 1 ) ) 
                {
                    throw new ezcBaseValueException( $name, $value, 'int >= 1' );
                }
                $this->properties[$name] = (int) $value;
                break;

            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }
}
?>
