<?php
/**
 * File containing the ezcMailParserOption class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Class containing the basic options for the mail parser.
 *
 * Example of how to use the parser options:
 * <code>
 * $options = new ezcMailParserOptions();
 * $options->mailClass = 'ezcMail';
 *
 * $parser = new ezcMailParser( $options );
 * </code>
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
     * Sets the option $propertyName to $propertyValue.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property $propertyName is not defined
     * @throws ezcBaseValueException
     *         if $propertyValue is not correct for the property $propertyName
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
                // correct parent class.
                if ( 'ezcMail' !== $propertyValue && !in_array( 'ezcMail', class_parents( $propertyValue ) ) )
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
