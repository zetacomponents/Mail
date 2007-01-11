<?php
/**
 * File containing the ezcMailPart class.
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Abstract base class for all mail MIME parts.
 *
 * This base class provides functionality to store headers and to generate
 * the mail part. Implementations of this class must handle the body of that
 * parts themselves. They must also implement {@link generateBody()} which is
 * called when the message part is generated.
 *
 * @property ezcMailContentDispositionHeader $contentDisposition
 *           Contains the information from the Content-Disposition field of
 *           this mail.  This useful especially when you are investigating
 *           retrieved mail to see if a part is an attachment or should be
 *           displayed inline.  However, it can also be used to set the same
 *           on outgoing mail. Note that the ezcMailFile part sets the
 *           Content-Disposition field itself based on it's own properties
 *           when sending mail.
 * @property-read ezcMailHeadersHolder $headers
 *                Contains the header holder object, taking care of the
 *                headers of this part. Can be retreived for reasons of
 *                extending this class and its derivals.
 *
 *
 * @package Mail
 * @version //autogen//
 */
abstract class ezcMailPart
{
    /**
     * An associative array containing all the headers set for this part.
     *
     * @var ezcMailHeadersHolder
     */
    private $headers = null;

    /**
     * An array of headers to exclude when generating the headers.
     *
     * @var array(string)
     */
    private $excludeHeaders = array();

    /**
     * Holds the properties of this class.
     *
     * @var array(string=>mixed)
     */
    private $properties = array();

    /**
     * Constructs a new mail part.
     */
    public function __construct()
    {
        $this->headers = new ezcMailHeadersHolder();
    }

    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property does not exist.
     * @throws ezcBasePropertyPermissionException
     *         if the property is read-only.
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'contentDisposition':
                $this->properties[$name] = $value;
                break;

            case 'headers':
                throw new ezcBasePropertyPermissionException( $name, ezcBasePropertyPermissionException::READ );

            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }

    }

    /**
     * Returns the property $name.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property does not exist.
     * @param string $name
     * @return mixed
     * @ignore
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'contentDisposition':
                return isset( $this->properties[$name] ) ? $this->properties[$name] : null;

            case "headers":
                return $this->headers;

            default:
                throw new ezcBasePropertyNotFoundException( $name );

        }
    }

    /**
     * Returns true if the property $name is set, otherwise false.
     *
     * @param string $name
     * @return bool
     * @ignore
     */
    public function __isset( $name )
    {
        switch ( $name )
        {
            case 'contentDisposition':
                return isset( $this->properties[$name] );

            case "headers":
                return isset( $this->headers );

            default:
                return false;
        }
    }

    /**
     * Returns the RAW value of the header $name.
     *
     * Returns an empty string if the header is not found.
     * Getting headers is case insensitive. Getting the header
     * 'Message-Id' will match both 'Message-ID' and 'MESSAGE-ID'
     * as well as 'Message-Id'.
     *
     * @param string $name
     * @return string
     */
    public function getHeader( $name )
    {
        if ( isset( $this->headers[$name] ) )
        {
            return $this->headers[$name];
        }
        return '';
    }

    /**
     * Sets the header $name to the value $value.
     *
     * If the header is already set it will override the old value.
     *
     * Headers set should be folded at 76 or 998 characters according to
     * the folding rules described in RFC 2822.
     *
     * Note: The header Content-Disposition will be overwritten by the
     * contents of the contentsDisposition property if set.
     *
     * @see generateHeaders()
     *
     * @param string $name
     * @param string $value
     */
    public function setHeader( $name, $value )
    {
        $this->headers[$name] = $value;
    }

    /**
     * Adds the headers $headers.
     *
     * The headers specified in the associative array of the
     * form array(headername=>value) will overwrite any existing
     * header values.
     *
     * Headers set should be folded at 76 or 998 characters according to
     * the folding rules described in RFC 2822.
     *
     * @param array(string=>string) $headers
     */
    public function setHeaders( array $headers )
    {
        foreach ( $headers as $key => $value )
        {
            $this->headers[$key] = $value;
        }
    }

    /**
     * Returns the headers set for this part as a RFC 822 string.
     *
     * Each header is separated by a line break.
     * This method does not add the required two lines of space
     * to separate the headers from the body of the part.
     *
     * This function is called automatically by generate() and
     * subclasses can override this method if they wish to set additional
     * headers when the mail is generated.
     *
     * @see setHeader()
     *
     * @return string
     */
    public function generateHeaders()
    {
        // set content disposition header
        if ( $this->contentDisposition !== null &&
            ( $this->contentDisposition instanceof ezcMailContentDispositionHeader ) )
        {
            $cdHeader = $this->contentDisposition;
            $cd = "{$cdHeader->disposition}";
            if ( $cdHeader->fileName !== null )
            {
                $cd .= "; filename=\"{$cdHeader->fileName}\"";
            }

            if ( $cdHeader->creationDate !== null )
            {
                $cd .= "; creation-date=\"{$cdHeader->creationDate}\"";
            }

            if ( $cdHeader->modificationDate !== null )
            {
                $cd .= "; modification-date=\"{$cdHeader->modificationDate}\"";
            }

            if ( $cdHeader->readDate !== null )
            {
                $cd .= "; read-date=\"{$cdHeader->readDate}\"";
            }

            if ( $cdHeader->size !== null )
            {
                $cd .= "; size={$cdHeader->size}";
            }

            foreach ( $cdHeader->additionalParameters as $addKey => $addValue )
            {
                $cd .="; {$addKey}=\"{$addValue}\"";
            }

            $this->setHeader( 'Content-Disposition', $cd );
        }

        // generate headers
        $text = "";
        foreach ( $this->headers->getCaseSensitiveArray() as $header => $value )
        {
            if ( in_array( strtolower( $header ), $this->excludeHeaders ) === false )
            {
                $text .= "$header: $value" . ezcMailTools::lineBreak();
            }
        }

        return $text;
    }

    /**
     * The array $headers will be excluded when the headers are generated.
     *
     * @see generateHeaders()
     *
     * @param array(string) $headers
     */
    public function appendExcludeHeaders( array $headers )
    {
        $lowerCaseHeaders = array();
        foreach ( $headers as $header )
        {
            $lowerCaseHeaders[] = strtolower( $header );
        }
        $this->excludeHeaders = array_merge( $this->excludeHeaders, $lowerCaseHeaders );
    }

    /**
     * Returns the body of this part as a string.
     *
     * This method is called automatically by generate() and subclasses must
     * implement it.
     *
     * @return string
     */
    abstract public function generateBody();

    /**
     * Returns the complete mail part including both the header and the body
     * as a string.
     *
     * @return string
     */
    public function generate()
    {
        return $this->generateHeaders() .ezcMailTools::lineBreak() . $this->generateBody();
    }
}
?>
