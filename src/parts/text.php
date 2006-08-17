<?php
/**
 * File containing the ezcMailText class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Mail part used for sending all forms of plain text.
 *
 * Example: ezcMailText in a plain text message
 * <code>
 * $textPart = new ezcMailText( "This is a text message" );
 * </code>
 *
 * Example: ezcMailText in a HTML message
 * <code>
 * $textPart = new ezcMailText( "<html>This is an <b>HTML</b> message"</html> );
 * $textPart->subType = 'html';
 * </code>
 *
 * @property string $charset
 *           The characterset used for this text part. Defaults to 'us-ascii'
 *           while creating mail, and is always 'utf-8' while parsing mail.
 * @property string $originalCharset
 *           The characterset in which a text part originally was before the
 *           conversion to UTF-8. (readonly)
 * @property string $subType
 *           The subtype of this text part. Defaults to 'plain' for plain text.
 *           Use 'html' for HTML messages.
 * @property string $encoding
 *           The encoding of the text. Defaults to eight bit.
 * @property string $text
 *           The main data of this text part.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailText extends ezcMailPart
{
    /**
     * Holds the properties of this class.
     *
     * @var array(string=>mixed)
     */
    private $properties = array();

    /**
     * Constructs a new TextPart with the given $text, $charset and $encoding.
     *
     * @param string $text
     * @param string $charset
     * @param int $encoding
     */
    public function __construct( $text, $charset = "us-ascii", $encoding = ezcMail::EIGHT_BIT, $originalCharset = 'us-ascii' )
    {
        parent::__construct();

        $this->text = $text;
        $this->charset = $charset;
        $this->encoding = $encoding;
        $this->subType = 'plain';
        // We need to set this directly in the array as it's a read-only property.
        $this->properties['originalCharset'] = $originalCharset;
    }

    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'charset':
                $this->properties['charset'] = $value;
                break;
            case 'subType':
                $this->properties['subType'] = $value;
                break;
            case 'encoding':
                $this->properties['encoding'] = $value;
                break;
            case 'text':
                $this->properties['text'] = $value;
                break;
            case 'originalCharset':
                throw new ezcBasePropertyPermissionException( $name, ezcBasePropertyPermissionException::READ );
                break;
            default:
                return parent::__set( $name, $value );
                break;
        }
    }

    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @param mixed $value
     * @return mixed
     * @ignore
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'charset':
                return $this->properties['charset'];
                break;
            case 'originalCharset':
                return $this->properties['originalCharset'];
                break;
            case 'subType':
                return $this->properties['subType'];
                break;
            case 'encoding':
                return $this->properties['encoding'];
                break;
            case 'text':
                return $this->properties['text'];
                break;
            default:
                return parent::__get( $name );
                break;
        }
    }

    /**
     * Returns the headers set for this part as a RFC822 compliant string.
     *
     * This method does not add the required two lines of space
     * to separate the headers from the body of the part.
     *
     * @see setHeader()
     * @return string
     */
    public function generateHeaders()
    {
        $this->setHeader( "Content-Type", "text/" . $this->subType . "; charset=" . $this->charset );
        $this->setHeader( "Content-Transfer-Encoding", $this->encoding );
        return parent::generateHeaders();
    }

    /**
     * Returns the generated text body of this part as a string.
     *
     * @return string
     */
    public function generateBody()
    {
        // convert linebreaks to the correct type.
        return preg_replace( "/\r\n|\r|\n/", ezcMailTools::lineBreak(), $this->text );
    }
}
?>
