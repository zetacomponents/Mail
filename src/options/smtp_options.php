<?php
/**
 * File containing the ezcMailSmtpTransportOptions class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Class containing the options for SMTP transport.
 *
 * The options from ezcMailTransportOptions are inherited.
 *
 * @property string $connectionType
 *           Specifies the protocol used to connect to the SMTP server. See the
 *           CONNECTION_* constants in the ezcMailSmtpTransport class.
 * @property array(mixed) $connectionOptions
 *           Specifies additional options for the connection. Must be in this format:
 *           array( 'wrapper_name' => array( 'option_name' => 'value' ) ).
 * @property bool ssl
 *           This option belongs to ezcMailTransportOptions, but it is not used in SMTP.
 *           When trying to set this to true the connectionType option will be set to
 *           CONNECTION_SSL value from ezcMailSmtpTransport.
 *           When trying to set this to false the connectionType option will be set to
 *           CONNECTION_PLAIN value from ezcMailSmtpTransport.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailSmtpTransportOptions extends ezcMailTransportOptions
{
    /**
     * Constructs an object with the specified values.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if $options contains a property not defined
     * @throws ezcBaseValueException
     *         if $options contains a property with a value not allowed
     * @param array(string=>mixed) $options
     */
    public function __construct( array $options = array() )
    {
        $this->connectionType = ezcMailSmtpTransport::CONNECTION_PLAIN; // default is plain connection
        $this->connectionOptions = array(); // default is no extra connection options

        parent::__construct( $options );
    }

    /**
     * Sets the option $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name is not defined
     * @throws ezcBaseValueException
     *         if $value is not correct for the property $name
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'connectionType':
                $this->properties[$name] = $value;
                break;

            case 'connectionOptions':
                if ( !is_array( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'array' );
                }
                $this->properties[$name] = $value;
                break;

            case 'ssl':
                if ( !is_bool( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'bool' );
                }
                $this->properties['connectionType'] = ( $value === true ) ? ezcMailSmtpTransport::CONNECTION_SSL : ezcMailSmtpTransport::CONNECTION_PLAIN;
                break;

            default:
                parent::__set( $name, $value );
        }
    }
}
?>
