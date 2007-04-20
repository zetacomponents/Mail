<?php
/**
 * File containing the ezcMailParserOption class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Class containing the basic options for the mail parser.
 *
 * @property int $mailClass
 *           Specifies a class descending from ezcMail which can be returned by the
 *           parser if you plan to use another class instead of ezcMail.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailParserOptions extends ezcBaseOptions
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
        $this->mailClass = 'ezcMail'; // default value for mail class is 'ezcMail'

        parent::__construct( $options );
    }

    /**
     * Sets the option $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name is not defined
     * @throws ezcBaseValueException
     *         if $value is not correct for the property $name
     * @throws ezcBaseInvalidParentClassException
     *         if the class name passed as replacement mailClass does not
     *         inherit from ezcMail.
     * @param string $propertyName
     * @param mixed  $propertyValue
     * @ignore
     */
    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'mailClass':
                if ( !is_string( $propertyValue ) )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'string that contains a class name' );
                }

                // Check if the passed classname actually implements the
                // correct parent class. We have to do that with reflection
                // here unfortunately
                $parentClass = new ReflectionClass( 'ezcMail' );
                $handlerClass = new ReflectionClass( $propertyValue );
                if ( 'ezcMail' !== $propertyValue && !$handlerClass->isSubclassOf( $parentClass ) )
                {
                    throw new ezcBaseInvalidParentClassException( 'ezcMail', $propertyValue );
                }
                $this->properties[$propertyName] = $propertyValue;
                break;

            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
        }
    }
}
?>
